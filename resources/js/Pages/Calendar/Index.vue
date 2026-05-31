<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { money, useI18n } from '@/i18n';
import { useTheme } from '@/theme';
import { Head, router } from '@inertiajs/vue3';
import { Calendar, ConfigProvider, theme as antTheme } from 'ant-design-vue';
import enUS from 'ant-design-vue/es/locale/en_US';
import zhCN from 'ant-design-vue/es/locale/zh_CN';
import dayjs from 'dayjs';
import 'dayjs/locale/zh-cn';
import { computed } from 'vue';
import 'ant-design-vue/dist/reset.css';

const props = defineProps({
    month: String,
    subscriptions: Array,
    stats: Object,
});

const { currentLocale, t } = useI18n();
const { isDark } = useTheme();
const calendarValue = computed(() => dayjs(`${props.month}-01`));
const antLocale = computed(() => (currentLocale.value === 'zh' ? zhCN : enUS));
const antThemeConfig = computed(() => ({
    algorithm: isDark.value ? antTheme.darkAlgorithm : antTheme.defaultAlgorithm,
    token: {
        borderRadius: 6,
        colorPrimary: '#4f46e5',
    },
}));
const subscriptionsByDate = computed(() => props.subscriptions.reduce((dates, subscription) => {
    const date = String(subscription.next_due_on).slice(0, 10);
    dates[date] ??= [];
    dates[date].push(subscription);

    return dates;
}, {}));

const subscriptionsByMonth = computed(() => props.subscriptions.reduce((months, subscription) => {
    const month = String(subscription.next_due_on).slice(0, 7);
    months[month] ??= [];
    months[month].push(subscription);

    return months;
}, {}));

function dateCellRender(date) {
    return subscriptionsByDate.value[date.format('YYYY-MM-DD')] || [];
}

function monthCellRender(date) {
    const subscriptions = subscriptionsByMonth.value[date.format('YYYY-MM')] || [];
    const totals = Object.values(subscriptions.reduce((currencies, subscription) => {
        currencies[subscription.currency] ??= {
            currency: subscription.currency,
            amount_cents: 0,
        };
        currencies[subscription.currency].amount_cents += Number(subscription.amount_cents);

        return currencies;
    }, {}));

    return {
        count: subscriptions.length,
        totals,
    };
}

function changeMonth(date) {
    const month = date.format('YYYY-MM');

    if (month !== props.month) {
        router.get(route('calendar', undefined, false), { month }, {
            preserveScroll: true,
            preserveState: true,
        });
    }
}

function moneyTotals(totals) {
    return totals.length
        ? totals.map((total) => money(total.amount_cents, total.currency)).join(' / ')
        : money(0);
}
</script>

<template>
    <Head :title="t('calendar')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ t('calendar') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="overflow-x-auto rounded-lg border bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <ConfigProvider :locale="antLocale" :theme="antThemeConfig">
                        <Calendar :value="calendarValue" class="min-w-[760px]" @panel-change="changeMonth">
                            <template #dateCellRender="{ current }">
                                <div class="space-y-1">
                                    <div
                                        v-for="subscription in dateCellRender(current)"
                                        :key="subscription.id"
                                        class="truncate rounded border-l-4 bg-gray-50 px-1.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                        :style="{ borderColor: subscription.category?.color || '#4f46e5' }"
                                        :title="subscription.name"
                                    >
                                        {{ subscription.name }}
                                    </div>
                                </div>
                            </template>
                            <template #monthCellRender="{ current }">
                                <div v-if="monthCellRender(current).count" class="mt-2 rounded-md bg-gray-50 px-2 py-1.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                    <div class="font-semibold">{{ monthCellRender(current).count }} {{ t('subscriptions') }}</div>
                                    <div class="mt-1 truncate">{{ moneyTotals(monthCellRender(current).totals) }}</div>
                                </div>
                            </template>
                        </Calendar>
                    </ConfigProvider>
                </section>

                <section>
                    <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ t('statistics') }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('activeSubscriptions') }}</div>
                            <div class="mt-2 text-2xl font-semibold">{{ stats.activeSubscriptions }}</div>
                        </div>
                        <div class="rounded-lg border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ t('monthlyTotal') }}</div>
                            <div class="mt-2 text-xl font-semibold leading-8">{{ moneyTotals(stats.totalsByCurrency) }}</div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
