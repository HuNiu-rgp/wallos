<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { money, useI18n } from '@/i18n';

const props = defineProps({
    stats: Object,
    upcomingSubscriptions: Array,
});

const { t } = useI18n();

function dateValue(value) {
    return value ? String(value).slice(0, 10) : '';
}

function moneyTotals(totals) {
    return totals.length
        ? totals.map((total) => money(total.amount_cents, total.currency)).join(' / ')
        : money(0);
}

function dueDateClass(value) {
    if (!value) return '';

    const today = new Date();
    const dueDate = new Date(`${dateValue(value)}T00:00:00`);
    const startOfToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const daysUntilDue = Math.ceil((dueDate.getTime() - startOfToday.getTime()) / 86400000);

    if (daysUntilDue <= 5) {
        return 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950 dark:text-rose-200 dark:ring-rose-900';
    }

    if (daysUntilDue <= 10) {
        return 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-900';
    }

    return 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700';
}

const statCards = [
    ['activeSubscriptions', 'activeSubscriptions', 'check-circle'],
    ['monthlySubscriptions', 'monthlySubscriptionsByCurrency', 'calendar'],
    ['yearlySubscriptions', 'yearlySubscriptionsByCurrency', 'wallet'],
    ['pausedSubscriptions', 'pausedSubscriptions', 'pause-circle'],
];

function statValue(key) {
    const value = props.stats[key];

    return Array.isArray(value) ? moneyTotals(value) : value;
}
</script>

<template>
    <Head :title="t('dashboard')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ t('dashboard') }}</h2>
                <Link :href="route('subscriptions.index', undefined, false)" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white dark:bg-indigo-600">
                    {{ t('createSubscription') }}
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 md:grid-cols-4">
                    <div v-for="[label, key, icon] in statCards" :key="key" class="rounded-lg border bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t(label) }}</div>
                            <div class="rounded-md bg-gray-100 p-2 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <AppIcon :name="icon" />
                            </div>
                        </div>
                        <div class="mt-3 text-xl font-semibold leading-8 text-gray-900 dark:text-gray-100">{{ statValue(key) }}</div>
                    </div>
                </div>

                <section class="rounded-lg border bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-4 border-b px-5 py-4 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ t('upcomingSubscriptions') }}</h3>
                        <Link :href="route('subscriptions.index', undefined, false)" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-300">
                            {{ t('subscriptions') }}
                        </Link>
                    </div>
                    <div class="divide-y dark:divide-gray-800">
                        <div v-if="!upcomingSubscriptions.length" class="px-5 py-8 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('emptyDashboardHint') }}
                        </div>
                        <div v-for="item in upcomingSubscriptions" :key="item.id" class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <img v-if="item.logo_url" :src="item.logo_url" alt="" class="h-9 w-9 shrink-0 rounded-md object-cover" />
                                <div v-else class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-gray-100 text-sm font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                    {{ item.name.slice(0, 1).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                                    <div class="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex rounded px-2 py-0.5 ring-1 ring-inset" :class="dueDateClass(item.next_due_on)">{{ dateValue(item.next_due_on) }}</span>
                                        <span v-if="item.category" class="inline-flex min-w-0 items-center gap-1.5">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: item.category.color || '#9ca3af' }"></span>
                                            <span class="truncate">{{ item.category.name }}</span>
                                        </span>
                                        <span v-else>{{ t('noCategory') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0 text-right font-semibold">{{ money(item.amount_cents, item.currency) }}</div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
