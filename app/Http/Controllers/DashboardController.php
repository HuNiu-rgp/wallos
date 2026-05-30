<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $today = now()->toDateString();

        $activeSubscriptions = $user->subscriptions()->where('is_active', true);

        return Inertia::render('Dashboard', [
            'stats' => [
                'activeSubscriptions' => (clone $activeSubscriptions)->count(),
                'monthlySubscriptionsByCurrency' => $this->totalsByCurrency(clone $activeSubscriptions, 'month'),
                'yearlySubscriptionsByCurrency' => $this->totalsByCurrency(clone $activeSubscriptions, 'year'),
                'pausedSubscriptions' => $user->subscriptions()->where('is_active', false)->count(),
            ],
            'upcomingSubscriptions' => $user->subscriptions()
                ->with(['category:id,name,color'])
                ->where('is_active', true)
                ->whereDate('next_due_on', '>=', $today)
                ->orderBy('next_due_on')
                ->limit(8)
                ->get(),
        ]);
    }

    private function totalsByCurrency($subscriptions, string $billingCycle): array
    {
        return $subscriptions
            ->where('billing_cycle', $billingCycle)
            ->selectRaw('currency, SUM(amount_cents) as amount_cents')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get()
            ->map(fn ($total): array => [
                'currency' => $total->currency,
                'amount_cents' => (int) $total->amount_cents,
            ])
            ->all();
    }
}
