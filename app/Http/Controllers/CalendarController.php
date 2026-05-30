<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $month = $request->string('month')->toString();

        try {
            $currentMonth = $month
                ? Carbon::createFromFormat('!Y-m', $month)
                : now()->startOfMonth();
        } catch (\Throwable) {
            $currentMonth = now()->startOfMonth();
        }

        $calendarSubscriptions = $request->user()
            ->subscriptions()
            ->with(['category:id,name,color'])
            ->where('is_active', true)
            ->whereBetween('next_due_on', [
                $currentMonth->copy()->startOfYear()->toDateString(),
                $currentMonth->copy()->endOfYear()->toDateString(),
            ])
            ->orderBy('next_due_on')
            ->orderBy('name')
            ->get();

        $subscriptions = $calendarSubscriptions
            ->filter(fn ($subscription): bool => $subscription->next_due_on->isSameMonth($currentMonth))
            ->values();

        return Inertia::render('Calendar/Index', [
            'month' => $currentMonth->format('Y-m'),
            'subscriptions' => $calendarSubscriptions,
            'stats' => [
                'activeSubscriptions' => $subscriptions->count(),
                'totalsByCurrency' => $subscriptions
                    ->groupBy('currency')
                    ->map(fn ($items, string $currency): array => [
                        'currency' => $currency,
                        'amount_cents' => $items->sum('amount_cents'),
                    ])
                    ->sortBy('currency')
                    ->values()
                    ->all(),
            ],
        ]);
    }
}
