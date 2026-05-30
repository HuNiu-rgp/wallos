<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $request->user()
                ->subscriptions()
                ->with(['category:id,name,color'])
                ->orderByDesc('is_active')
                ->orderBy('next_due_on')
                ->get(),
            'categories' => $request->user()->categories()->where('type', 'expense')->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSubscription($request);
        $data = Arr::except($validated, ['amount', 'logo_url', 'remove_logo']);

        $request->user()->subscriptions()->create([
            ...$data,
            'logo_path' => isset($validated['logo_url']) ? trim($validated['logo_url']) : null,
            'amount_cents' => $this->moneyToCents($validated['amount']),
            'currency' => strtoupper($validated['currency']),
        ]);

        return back()->with('success', __('Subscription created.'));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 404);

        $validated = $this->validateSubscription($request);
        $logoPath = $subscription->logo_path;

        if ($request->boolean('remove_logo') && $logoPath) {
            $this->deleteStoredLogo($logoPath);
            $logoPath = null;
        }

        if (array_key_exists('logo_url', $validated)) {
            $newLogoPath = isset($validated['logo_url']) ? trim($validated['logo_url']) : null;

            if ($logoPath && $logoPath !== $newLogoPath) {
                $this->deleteStoredLogo($logoPath);
            }

            $logoPath = $newLogoPath;
        }
        $data = Arr::except($validated, ['amount', 'logo_url', 'remove_logo']);

        $subscription->update([
            ...$data,
            'logo_path' => $logoPath,
            'amount_cents' => $this->moneyToCents($validated['amount']),
            'currency' => strtoupper($validated['currency']),
        ]);

        return back()->with('success', __('Subscription updated.'));
    }

    public function destroy(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 404);

        if ($subscription->logo_path) {
            $this->deleteStoredLogo($subscription->logo_path);
        }

        $subscription->delete();

        return back()->with('success', __('Subscription deleted.'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
        ]);

        try {
            $items = json_decode($request->file('file')->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['file' => __('The uploaded JSON file is invalid.')]);
        }

        if (! is_array($items) || ! array_is_list($items)) {
            throw ValidationException::withMessages(['file' => __('The uploaded JSON must contain a list of subscriptions.')]);
        }

        DB::transaction(function () use ($items, $request): void {
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    throw ValidationException::withMessages(['file' => __('Subscription row :row is invalid.', ['row' => $index + 1])]);
                }

                $request->user()->subscriptions()->create($this->importData($request, $item, $index));
            }
        });

        return back()->with('success', __('Imported :count subscriptions.', ['count' => count($items)]));
    }

    public function export(Request $request): StreamedResponse
    {
        $subscriptions = $request->user()
            ->subscriptions()
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Subscription $subscription): array => [
                'Name' => $subscription->name,
                'Payment Cycle' => ucfirst($this->normalizeExportCycle($subscription->billing_cycle)),
                'Next Payment' => $subscription->next_due_on?->format('Y-m-d') ?? '',
                'Renewal' => $subscription->auto_renew ? 'Automatic' : 'Manual',
                'Category' => $subscription->category?->name ?? 'No category',
                'Payment Method' => $subscription->payment_method ?? '',
                'Paid By' => $subscription->payer_name ?? '',
                'Price' => $this->exportPrice($subscription),
                'Notes' => $subscription->notes ?? '',
                'URL' => $subscription->link_url ?? '',
                'State' => $subscription->is_active ? 'Enabled' : 'Disabled',
                'Notifications' => $subscription->notification_enabled ? 'Enabled' : 'Disabled',
                'Cancellation Date' => $subscription->cancellation_notice_on?->format('Y-m-d') ?? '',
                'Active' => $subscription->is_active ? 'Yes' : 'No',
            ]);

        return response()->streamDownload(function () use ($subscriptions): void {
            echo json_encode($subscriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, 'subscriptions.json', ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function fetchLogo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url:http,https', 'max:2048'],
        ]);
        $url = $validated['url'];

        if (! $this->isPublicUrl($url)) {
            throw ValidationException::withMessages(['url' => __('The website URL must point to a public address.')]);
        }

        [$html, $finalUrl] = $this->fetchWebsite($url);
        $logoUrl = $this->extractLogoUrl($html, $finalUrl);

        if (! $logoUrl || ! $this->isPublicUrl($logoUrl)) {
            throw ValidationException::withMessages(['url' => __('No public website icon was found in the page head.')]);
        }

        return response()->json(['logo_url' => $logoUrl]);
    }

    private function validateSubscription(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)],
            'name' => ['required', 'string', 'max:255'],
            'logo_url' => [
                'nullable',
                'string',
                'max:10000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isValidLogoInput($value)) {
                        $fail(__('The logo must be an HTTP URL or safe SVG code.'));
                    }
                },
            ],
            'remove_logo' => ['boolean'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval' => ['required', 'integer', 'min:1', 'max:999'],
            'billing_cycle' => ['required', 'in:day,week,month,year,weekly,monthly,quarterly,yearly,custom'],
            'start_on' => ['nullable', 'date'],
            'next_due_on' => ['required', 'date'],
            'last_charged_on' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'auto_renew' => ['boolean'],
            'reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notification_enabled' => ['boolean'],
            'notification_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
            'cancellation_notice_on' => ['nullable', 'date'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function moneyToCents(null|string|int|float $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function importData(Request $request, array $item, int $index): array
    {
        $name = trim((string) ($item['Name'] ?? ''));
        $nextDueOn = trim((string) ($item['Next Payment'] ?? ''));
        $cycle = strtolower(trim((string) ($item['Payment Cycle'] ?? '')));
        $price = $this->parseImportedPrice((string) ($item['Price'] ?? ''), $index);

        if ($name === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextDueOn) || ! in_array($cycle, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'], true)) {
            throw ValidationException::withMessages(['file' => __('Subscription row :row has invalid required fields.', ['row' => $index + 1])]);
        }

        $categoryName = trim((string) ($item['Category'] ?? ''));
        $category = null;

        if ($categoryName !== '' && strcasecmp($categoryName, 'No category') !== 0) {
            $category = Category::firstOrCreate(
                ['user_id' => $request->user()->id, 'name' => $categoryName, 'type' => 'expense'],
                ['color' => '#64748b']
            );
        }

        return [
            'category_id' => $category?->id,
            'name' => $name,
            'amount_cents' => $price['amount_cents'],
            'currency' => $price['currency'],
            'billing_interval' => $cycle === 'quarterly' ? 3 : 1,
            'billing_cycle' => match ($cycle) {
                'daily' => 'day',
                'weekly' => 'week',
                'quarterly', 'monthly' => 'month',
                'yearly' => 'year',
            },
            'next_due_on' => $nextDueOn,
            'payment_method' => trim((string) ($item['Payment Method'] ?? '')) ?: null,
            'payer_name' => trim((string) ($item['Paid By'] ?? '')) ?: null,
            'auto_renew' => strcasecmp((string) ($item['Renewal'] ?? ''), 'Automatic') === 0,
            'notification_enabled' => strcasecmp((string) ($item['Notifications'] ?? ''), 'Enabled') === 0,
            'cancellation_notice_on' => trim((string) ($item['Cancellation Date'] ?? '')) ?: null,
            'link_url' => html_entity_decode(trim((string) ($item['URL'] ?? '')), ENT_QUOTES | ENT_HTML5),
            'is_active' => strcasecmp((string) ($item['Active'] ?? $item['State'] ?? ''), 'Yes') === 0
                || strcasecmp((string) ($item['State'] ?? ''), 'Enabled') === 0,
            'notes' => trim((string) ($item['Notes'] ?? '')) ?: null,
        ];
    }

    private function parseImportedPrice(string $price, int $index): array
    {
        $price = trim($price);
        $currency = match (true) {
            str_contains($price, '€') => 'EUR',
            str_contains($price, '£') => 'GBP',
            str_contains($price, '￥'), str_contains($price, '¥') => 'CNY',
            default => 'USD',
        };
        $amount = preg_replace('/[^0-9.\-]/', '', $price);

        if ($amount === '' || ! is_numeric($amount) || (float) $amount < 0) {
            throw ValidationException::withMessages(['file' => __('Subscription row :row has an invalid price.', ['row' => $index + 1])]);
        }

        return ['amount_cents' => $this->moneyToCents($amount), 'currency' => $currency];
    }

    private function normalizeExportCycle(string $cycle): string
    {
        return match ($cycle) {
            'day' => 'daily',
            'week' => 'weekly',
            'year' => 'yearly',
            default => 'monthly',
        };
    }

    private function exportPrice(Subscription $subscription): string
    {
        $symbol = match ($subscription->currency) {
            'EUR' => '€',
            'GBP' => '£',
            'CNY' => '￥',
            default => '$',
        };
        $amount = rtrim(rtrim(number_format($subscription->amount_cents / 100, 2, '.', ''), '0'), '.');

        return $symbol.$amount;
    }

    private function deleteStoredLogo(string $logoPath): void
    {
        if (! str_starts_with($logoPath, 'http://') && ! str_starts_with($logoPath, 'https://') && ! str_starts_with($logoPath, '<svg')) {
            Storage::disk('public')->delete($logoPath);
        }
    }

    private function isValidLogoInput(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
        }

        if (! str_starts_with($value, '<svg') || ! str_ends_with($value, '</svg>')) {
            return false;
        }

        return ! preg_match('/<(script|foreignObject|iframe|object|embed|link|style)\b|on[a-z]+\s*=|javascript:|data:text\/html|(href|src)\s*=\s*["\']\s*https?:\/\//i', $value);
    }

    private function extractLogoUrl(string $html, string $pageUrl): ?string
    {
        $document = new \DOMDocument;

        if (! @$document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return null;
        }

        $xpath = new \DOMXPath($document);
        $icons = $xpath->query('//head/link[contains(concat(" ", translate(normalize-space(@rel), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " "), " icon ")]');

        foreach ($icons ?: [] as $icon) {
            $href = trim($icon->getAttribute('href'));

            if ($href !== '') {
                return $this->absoluteUrl($pageUrl, $href);
            }
        }

        return null;
    }

    private function fetchWebsite(string $url): array
    {
        for ($redirects = 0; $redirects <= 5; $redirects++) {
            if (! $this->isPublicUrl($url)) {
                throw ValidationException::withMessages(['url' => __('The website URL must point to a public address.')]);
            }

            $response = Http::timeout(8)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml',
                    'User-Agent' => 'Mozilla/5.0 Wallos Logo Fetcher',
                ])
                ->get($url);

            if ($response->successful()) {
                return [$response->body(), $url];
            }

            if ($response->redirect() && $response->header('Location')) {
                $url = $this->absoluteUrl($url, $response->header('Location'));

                if ($url) {
                    continue;
                }
            }

            throw ValidationException::withMessages(['url' => __('Unable to read the website.')]);
        }

        throw ValidationException::withMessages(['url' => __('The website redirected too many times.')]);
    }

    private function absoluteUrl(string $pageUrl, string $href): ?string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        $page = parse_url($pageUrl);

        if (! isset($page['scheme'], $page['host'])) {
            return null;
        }

        if (str_starts_with($href, '//')) {
            return $page['scheme'].':'.$href;
        }

        $origin = $page['scheme'].'://'.$page['host'].(isset($page['port']) ? ':'.$page['port'] : '');
        $path = str_starts_with($href, '/')
            ? $href
            : preg_replace('#/[^/]*$#', '/', $page['path'] ?? '/').$href;

        return $origin.$path;
    }

    private function isPublicUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return false;
        }

        $addresses = filter_var($parts['host'], FILTER_VALIDATE_IP)
            ? [$parts['host']]
            : array_values(array_unique([
                ...array_map(fn (array $record): string => $record['ip'], dns_get_record($parts['host'], DNS_A) ?: []),
                ...array_map(fn (array $record): string => $record['ipv6'], dns_get_record($parts['host'], DNS_AAAA) ?: []),
            ]));

        return $addresses !== [] && collect($addresses)->every(fn (string $address): bool => filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false);
    }
}
