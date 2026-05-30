<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { money, useI18n } from '@/i18n';

defineProps({
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
</script>

<template>
    <Head :title="t('dashboard')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ t('dashboard') }}</h2>
                <Link :href="route('subscriptions.index')" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white dark:bg-indigo-600">
                    {{ t('createSubscription') }}
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-lg border bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('activeSubscriptions') }}</div>
                        <div class="mt-2 text-2xl font-semibold">{{ stats.activeSubscriptions }}</div>
                    </div>
                    <div class="rounded-lg border bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('monthlySubscriptions') }}</div>
                        <div class="mt-2 text-xl font-semibold leading-8">{{ moneyTotals(stats.monthlySubscriptionsByCurrency) }}</div>
                    </div>
                    <div class="rounded-lg border bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('yearlySubscriptions') }}</div>
                        <div class="mt-2 text-xl font-semibold leading-8">{{ moneyTotals(stats.yearlySubscriptionsByCurrency) }}</div>
                    </div>
                    <div class="rounded-lg border bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('pausedSubscriptions') }}</div>
                        <div class="mt-2 text-2xl font-semibold">{{ stats.pausedSubscriptions }}</div>
                    </div>
                </div>

                <section class="rounded-lg border bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b px-5 py-4 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ t('upcomingSubscriptions') }}</h3>
                    </div>
                    <div class="divide-y dark:divide-gray-800">
                        <div v-if="!upcomingSubscriptions.length" class="px-5 py-8 text-sm text-gray-500 dark:text-gray-400">
                            {{ t('emptyDashboardHint') }}
                        </div>
                        <div v-for="item in upcomingSubscriptions" :key="item.id" class="flex items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ dateValue(item.next_due_on) }} · {{ item.category?.name || t('noCategory') }}</div>
                            </div>
                            <div class="text-right font-semibold">{{ money(item.amount_cents, item.currency) }}</div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
