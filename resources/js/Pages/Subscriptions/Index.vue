<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { centsToAmount, money, useI18n } from '@/i18n';
import { useTheme } from '@/theme';
import { CalculatorOutlined, DeleteOutlined, EditOutlined, LinkOutlined, ReloadOutlined } from '@ant-design/icons-vue';
import { Alert, Button, ConfigProvider, DatePicker, Input, InputNumber, Modal, Popconfirm, Select, Space, Switch, Textarea, Tooltip, theme as antTheme } from 'ant-design-vue';
import enUS from 'ant-design-vue/es/locale/en_US';
import zhCN from 'ant-design-vue/es/locale/zh_CN';
import { computed, ref } from 'vue';
import 'ant-design-vue/dist/reset.css';

const props = defineProps({
    subscriptions: Array,
    categories: Array,
});

const { currentLocale, t } = useI18n();
const { isDark } = useTheme();
const page = usePage();
const editing = ref(null);
const showModal = ref(false);
const logoPreview = ref(null);
const fetchingLogo = ref(false);
const search = ref('');
const statusFilter = ref('all');
const categoryFilter = ref('all');
const sortMode = ref('next_due_on_asc');
const importInput = ref(null);
const importForm = useForm({
    file: null,
});

const currencies = [
    ['USD', 'US Dollar'],
    ['CNY', 'Chinese Yuan'],
    ['EUR', 'Euro'],
    ['GBP', 'British Pound'],
    ['JPY', 'Japanese Yen'],
    ['HKD', 'Hong Kong Dollar'],
    ['TWD', 'New Taiwan Dollar'],
    ['CAD', 'Canadian Dollar'],
    ['AUD', 'Australian Dollar'],
    ['SGD', 'Singapore Dollar'],
];
const cycles = ['day', 'week', 'month', 'year'];
const paymentMethods = ['PayPal', 'Credit Card', 'Bank Transfer', 'Apple Pay', 'Google Pay', 'Cash', 'Other'];
const notificationOptions = Array.from({ length: 16 }, (_, index) => index + 1);
const currencyOptions = currencies.map(([value, label]) => ({ value, label: `${value} - ${label}` }));
const intervalOptions = Array.from({ length: 24 }, (_, index) => ({ value: index + 1, label: String(index + 1) }));
const cycleOptions = computed(() => cycles.map((value) => ({ value, label: t(value) })));
const paymentMethodOptions = paymentMethods.map((value) => ({ value, label: value }));
const categoryOptions = computed(() => [
    { value: '', label: t('noCategory') },
    ...props.categories.map((category) => ({ value: category.id, label: category.name })),
]);
const notificationLeadTimeOptions = computed(() => [
    { value: '', label: t('defaultFromSettings') },
    { value: 0, label: t('dueDate') },
    ...notificationOptions.map((day) => ({ value: day, label: `${day} ${t('daysSuffix')}` })),
]);

const form = useForm({
    _method: '',
    name: '',
    logo_url: '',
    remove_logo: false,
    amount: '',
    currency: page.props.site.defaultCurrency || 'CNY',
    billing_interval: 1,
    billing_cycle: 'month',
    start_on: new Date().toISOString().slice(0, 10),
    next_due_on: '',
    last_charged_on: '',
    payment_method: 'PayPal',
    payer_name: '',
    category_id: '',
    auto_renew: true,
    notification_enabled: false,
    notification_days_before: '',
    cancellation_notice_on: '',
    link_url: '',
    is_active: true,
    notes: '',
});

const modalTitle = computed(() => (editing.value ? t('edit') : t('createSubscription')));
const antLocale = computed(() => (currentLocale.value === 'zh' ? zhCN : enUS));
const antThemeConfig = computed(() => ({
    algorithm: isDark.value ? antTheme.darkAlgorithm : antTheme.defaultAlgorithm,
    token: {
        borderRadius: 6,
        colorPrimary: '#4f46e5',
    },
}));
const filteredSubscriptions = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    return [...props.subscriptions]
        .filter((item) => {
            const matchesSearch = !keyword
                || item.name.toLowerCase().includes(keyword)
                || (item.payment_method || '').toLowerCase().includes(keyword)
                || (item.category?.name || '').toLowerCase().includes(keyword);
            const matchesStatus = statusFilter.value === 'all'
                || (statusFilter.value === 'active' && item.is_active)
                || (statusFilter.value === 'inactive' && !item.is_active);
            const matchesCategory = categoryFilter.value === 'all'
                || String(item.category_id || '') === categoryFilter.value;

            return matchesSearch && matchesStatus && matchesCategory;
        })
        .sort((first, second) => {
            if (sortMode.value === 'next_due_on_desc') {
                return dateSortValue(second.next_due_on) - dateSortValue(first.next_due_on);
            }

            if (sortMode.value === 'amount_asc') {
                return first.amount_cents - second.amount_cents;
            }

            if (sortMode.value === 'amount_desc') {
                return second.amount_cents - first.amount_cents;
            }

            if (sortMode.value === 'newest') {
                return dateSortValue(second.created_at) - dateSortValue(first.created_at);
            }

            return dateSortValue(first.next_due_on) - dateSortValue(second.next_due_on);
        });
});

function reset() {
    editing.value = null;
    logoPreview.value = null;
    form.reset();
    form.clearErrors();
    form._method = '';
    form.logo_url = '';
    form.remove_logo = false;
    form.currency = page.props.site.defaultCurrency || 'CNY';
    form.billing_interval = 1;
    form.billing_cycle = 'month';
    form.start_on = new Date().toISOString().slice(0, 10);
    form.next_due_on = '';
    form.payment_method = 'PayPal';
    form.auto_renew = true;
    form.notification_enabled = false;
    form.is_active = true;
}

function openCreate() {
    reset();
    showModal.value = true;
}

function edit(item) {
    reset();
    editing.value = item.id;
    showModal.value = true;
    logoPreview.value = item.logo_url;
    form.logo_url = item.logo_input || '';
    form.name = item.name;
    form.amount = centsToAmount(item.amount_cents);
    form.currency = item.currency;
    form.billing_interval = item.billing_interval || 1;
    form.billing_cycle = normalizeCycle(item.billing_cycle);
    form.start_on = dateValue(item.start_on || item.next_due_on);
    form.next_due_on = dateValue(item.next_due_on);
    form.last_charged_on = dateValue(item.last_charged_on);
    form.payment_method = item.payment_method || 'PayPal';
    form.payer_name = item.payer_name || '';
    form.category_id = item.category_id || '';
    form.auto_renew = item.auto_renew ?? true;
    form.notification_enabled = item.notification_enabled;
    form.notification_days_before = item.notification_days_before ?? '';
    form.cancellation_notice_on = dateValue(item.cancellation_notice_on);
    form.link_url = item.link_url || '';
    form.is_active = item.is_active;
    form.notes = item.notes || '';
}

function dateValue(value) {
    return value ? String(value).slice(0, 10) : '';
}

function dateSortValue(value) {
    return value ? new Date(value).getTime() : Number.MAX_SAFE_INTEGER;
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

    return '';
}

function isRenewalDue(value) {
    if (!value) return false;

    const today = new Date();
    const dueDate = new Date(`${dateValue(value)}T00:00:00`);
    const startOfToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    return Math.ceil((dueDate.getTime() - startOfToday.getTime()) / 86400000) <= 10;
}

function renewalUrl(value) {
    if (!value) return null;

    try {
        const url = new URL(value);

        return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
    } catch {
        return null;
    }
}

function normalizeCycle(cycle) {
    return {
        weekly: 'week',
        monthly: 'month',
        quarterly: 'month',
        yearly: 'year',
    }[cycle] || cycle || 'month';
}

function clearLogo() {
    form.logo_url = '';
    form.remove_logo = true;
    logoPreview.value = null;
}

function previewLogo() {
    form.remove_logo = false;
    logoPreview.value = logoPreviewUrl(form.logo_url);
}

async function fetchLogoFromLink() {
    if (!form.link_url || fetchingLogo.value) return;

    fetchingLogo.value = true;
    form.clearErrors('link_url');

    try {
        const response = await window.axios.post(route('subscriptions.fetch-logo', undefined, false), {
            url: form.link_url,
        });

        form.logo_url = response.data.logo_url;
        form.remove_logo = false;
        logoPreview.value = logoPreviewUrl(form.logo_url);
    } catch (error) {
        form.setError('link_url', error.response?.data?.errors?.url?.[0] || t('fetchLogoFailed'));
    } finally {
        fetchingLogo.value = false;
    }
}

function logoPreviewUrl(value) {
    const input = value.trim();

    if (!input) return null;

    if (input.startsWith('<svg') && input.endsWith('</svg>')) {
        return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(input)}`;
    }

    return input;
}

function calculateNextPayment() {
    const source = form.start_on || new Date().toISOString().slice(0, 10);
    const date = new Date(`${source}T00:00:00`);
    const interval = Number(form.billing_interval || 1);

    if (form.billing_cycle === 'day') date.setDate(date.getDate() + interval);
    if (form.billing_cycle === 'week') date.setDate(date.getDate() + interval * 7);
    if (form.billing_cycle === 'month') date.setMonth(date.getMonth() + interval);
    if (form.billing_cycle === 'year') date.setFullYear(date.getFullYear() + interval);

    form.next_due_on = date.toISOString().slice(0, 10);
}

function submit() {
    form._method = editing.value ? 'put' : '';

    if (!form.next_due_on) {
        calculateNextPayment();
    }

    form.transform((data) => ({
        ...data,
        auto_renew: data.auto_renew ? 1 : 0,
        notification_enabled: data.notification_enabled ? 1 : 0,
        is_active: data.is_active ? 1 : 0,
        remove_logo: data.remove_logo ? 1 : 0,
    })).post(editing.value ? route('subscriptions.update', editing.value, false) : route('subscriptions.store', undefined, false), {
        onSuccess: () => {
            showModal.value = false;
            reset();
        },
    });
}

function destroy(item) {
    router.delete(route('subscriptions.destroy', item.id, false));
}

function openImport() {
    importInput.value?.click();
}

function importSubscriptions(event) {
    const [file] = event.target.files;

    if (!file) return;

    importForm.file = file;
    importForm.post(route('subscriptions.import', undefined, false), {
        forceFormData: true,
        onFinish: () => {
            importForm.reset();
            event.target.value = '';
        },
    });
}
</script>

<template>
    <Head :title="t('subscriptions')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ t('subscriptions') }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <input ref="importInput" type="file" accept=".json,application/json" class="hidden" @change="importSubscriptions" />
                    <button type="button" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800" :disabled="importForm.processing" @click="openImport">
                        <AppIcon name="upload" />
                        {{ t('importSubscriptions') }}
                    </button>
                    <a :href="route('subscriptions.export', undefined, false)" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <AppIcon name="download" />
                        {{ t('exportSubscriptions') }}
                    </a>
                    <button class="rounded-md bg-gray-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black dark:bg-black dark:hover:bg-gray-800" @click="openCreate">
                        {{ t('createSubscription') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-lg border bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div v-if="page.props.errors?.file" class="border-b border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">
                        {{ page.props.errors.file }}
                    </div>
                    <div class="grid gap-3 border-b bg-gray-50 px-5 py-4 dark:border-gray-800 dark:bg-gray-800 lg:grid-cols-[1fr_160px_180px_220px]">
                        <label class="block">
                            <span class="sr-only">{{ t('search') }}</span>
                            <input
                                v-model="search"
                                type="search"
                                :placeholder="`${t('search')} ${t('subscriptions')}`"
                                class="h-10 w-full rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                            />
                        </label>
                        <label class="block">
                            <span class="sr-only">{{ t('status') }}</span>
                            <select v-model="statusFilter" class="h-10 w-full rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <option value="all">{{ t('all') }}</option>
                                <option value="active">{{ t('active') }}</option>
                                <option value="inactive">{{ t('inactive') }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="sr-only">{{ t('category') }}</span>
                            <select v-model="categoryFilter" class="h-10 w-full rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <option value="all">{{ t('all') }} {{ t('categories') }}</option>
                                <option value="">{{ t('noCategory') }}</option>
                                <option v-for="category in props.categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="sr-only">{{ t('sortBy') }}</span>
                            <select v-model="sortMode" class="h-10 w-full rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <option value="next_due_on_asc">{{ t('nextPaymentDateAsc') }}</option>
                                <option value="next_due_on_desc">{{ t('nextPaymentDateDesc') }}</option>
                                <option value="amount_asc">{{ t('amountAsc') }}</option>
                                <option value="amount_desc">{{ t('amountDesc') }}</option>
                                <option value="newest">{{ t('newest') }}</option>
                            </select>
                        </label>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 text-left text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-5 py-3">{{ t('name') }}</th>
                                    <th class="px-5 py-3">{{ t('amount') }}</th>
                                    <th class="px-5 py-3">{{ t('paymentFrequency') }}</th>
                                    <th class="px-5 py-3">{{ t('nextPaymentDate') }}</th>
                                    <th class="px-5 py-3">{{ t('category') }}</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-if="!filteredSubscriptions.length">
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">{{ t('noData') }}</td>
                                </tr>
                                <tr v-for="item in filteredSubscriptions" :key="item.id">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <img v-if="item.logo_url" :src="item.logo_url" alt="" class="h-9 w-9 rounded-md object-cover" />
                                            <div v-else class="flex h-9 w-9 items-center justify-center rounded-md bg-gray-100 font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                                {{ item.name.slice(0, 1).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</div>
                                                <div v-if="item.notes" class="text-xs text-gray-500 dark:text-gray-400">{{ item.notes }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">{{ money(item.amount_cents, item.currency) }}</td>
                                    <td class="px-5 py-4">{{ item.billing_interval || 1 }} {{ t(normalizeCycle(item.billing_cycle)) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded px-2 py-1" :class="dueDateClass(item.next_due_on) ? `${dueDateClass(item.next_due_on)} ring-1 ring-inset` : ''">
                                            {{ dateValue(item.next_due_on) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div v-if="item.category" class="flex items-center gap-2">
                                            <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: item.category.color || '#9ca3af' }"></span>
                                            <span>{{ item.category.name }}</span>
                                        </div>
                                        <span v-else>{{ t('noCategory') }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <ConfigProvider :theme="antThemeConfig">
                                            <Space :size="4">
                                                <Tooltip v-if="isRenewalDue(item.next_due_on) && renewalUrl(item.link_url)" :title="t('renew')">
                                                    <Button type="text" :aria-label="t('renew')" :href="renewalUrl(item.link_url)" target="_blank" class="text-emerald-700 dark:text-emerald-300">
                                                        <template #icon><LinkOutlined /></template>
                                                    </Button>
                                                </Tooltip>
                                                <Tooltip :title="t('edit')">
                                                    <Button type="text" :aria-label="t('edit')" class="text-indigo-700 dark:text-indigo-300" @click="edit(item)">
                                                        <template #icon><EditOutlined /></template>
                                                    </Button>
                                                </Tooltip>
                                                <Popconfirm :title="t('confirmDelete')" :ok-text="t('delete')" :cancel-text="t('cancel')" @confirm="destroy(item)">
                                                    <Tooltip :title="t('delete')">
                                                        <Button type="text" danger :aria-label="t('delete')">
                                                            <template #icon><DeleteOutlined /></template>
                                                        </Button>
                                                    </Tooltip>
                                                </Popconfirm>
                                            </Space>
                                        </ConfigProvider>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <ConfigProvider :locale="antLocale" :theme="antThemeConfig">
            <Modal v-model:open="showModal" :title="modalTitle" :footer="null" :width="790" :body-style="{ maxHeight: 'calc(100vh - 180px)', overflowY: 'auto' }">
                <form @submit.prevent="submit">
                    <div class="grid gap-5">
                        <Alert v-if="form.hasErrors" type="error" show-icon>
                            <template #message>
                                <div v-for="(message, field) in form.errors" :key="field">{{ message }}</div>
                            </template>
                        </Alert>

                        <div class="grid gap-5 md:grid-cols-[1fr_270px] md:items-start">
                            <label class="block">
                                <Input v-model:value="form.name" :placeholder="t('name')" size="large" />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </label>
                            <div class="flex items-start gap-3">
                                <label class="min-w-0 flex-1">
                                    <Input v-model:value="form.logo_url" :placeholder="t('logoUrl')" size="large" @blur="previewLogo" @input="form.remove_logo = false" />
                                    <InputError :message="form.errors.logo_url" class="mt-1" />
                                </label>
                                <Tooltip :title="t('logoPreview')">
                                    <Button type="dashed" size="large" class="h-10 w-10 p-0" @click="clearLogo">
                                        <img v-if="logoPreview" :src="logoPreview" :alt="t('logoPreview')" class="h-full w-full rounded object-cover" />
                                        <span v-else>◌</span>
                                    </Button>
                                </Tooltip>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <InputNumber v-model:value="form.amount" :min="0" :step="0.01" :placeholder="t('amount')" size="large" class="w-full" />
                                <InputError :message="form.errors.amount" class="mt-1" />
                            </label>
                            <label>
                                <Select v-model:value="form.currency" :options="currencyOptions" size="large" class="w-full" />
                                <InputError :message="form.errors.currency" class="mt-1" />
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="mb-2 font-semibold">{{ t('paymentFrequency') }}</div>
                                <div class="grid grid-cols-2 gap-3">
                                    <Select v-model:value="form.billing_interval" :options="intervalOptions" size="large" />
                                    <Select v-model:value="form.billing_cycle" :options="cycleOptions" size="large" />
                                </div>
                            </div>
                            <div>
                                <div class="mb-2 font-semibold">{{ t('autoRenew') }}</div>
                                <label class="flex h-10 items-center gap-3">
                                    <Switch v-model:checked="form.auto_renew" />
                                    <span>{{ t('autoRenew') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-[1fr_52px_1fr] md:items-end">
                            <label>
                                <span class="mb-2 block font-semibold">{{ t('startDate') }}</span>
                                <DatePicker v-model:value="form.start_on" value-format="YYYY-MM-DD" format="YYYY-MM-DD" size="large" class="w-full" />
                            </label>
                            <Tooltip :title="t('nextPaymentDate')">
                                <Button size="large" class="w-full" @click="calculateNextPayment"><CalculatorOutlined /></Button>
                            </Tooltip>
                            <label>
                                <span class="mb-2 block font-semibold">{{ t('nextPaymentDate') }}</span>
                                <DatePicker v-model:value="form.next_due_on" value-format="YYYY-MM-DD" format="YYYY-MM-DD" size="large" class="w-full" />
                                <InputError :message="form.errors.next_due_on" class="mt-1" />
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="mb-2 block font-semibold">{{ t('paymentMethod') }}</span>
                                <Select v-model:value="form.payment_method" :options="paymentMethodOptions" size="large" class="w-full" />
                            </label>
                            <label>
                                <span class="mb-2 block font-semibold">{{ t('payer') }}</span>
                                <Input v-model:value="form.payer_name" placeholder="Bob" size="large" />
                            </label>
                        </div>

                        <label>
                            <span class="mb-2 block font-semibold">{{ t('category') }}</span>
                            <Select v-model:value="form.category_id" :options="categoryOptions" size="large" class="w-full" />
                        </label>

                        <label class="flex items-center gap-3 font-semibold">
                            <Switch v-model:checked="form.notification_enabled" />
                            <span>{{ t('enableNotifications') }}</span>
                        </label>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="mb-2 block font-semibold">{{ t('notificationLeadTime') }}</span>
                                <Select v-model:value="form.notification_days_before" :options="notificationLeadTimeOptions" size="large" class="w-full" />
                            </label>
                            <label>
                                <span class="mb-2 block font-semibold">{{ t('cancellationNotice') }}</span>
                                <DatePicker v-model:value="form.cancellation_notice_on" value-format="YYYY-MM-DD" format="YYYY-MM-DD" size="large" class="w-full" />
                            </label>
                        </div>

                        <div>
                            <Space.Compact class="w-full">
                                <Input v-model:value="form.link_url" :placeholder="t('link')" size="large" />
                                <Tooltip :title="t('fetchLogoFromLink')">
                                    <Button size="large" :loading="fetchingLogo" :disabled="!form.link_url" @click="fetchLogoFromLink"><ReloadOutlined /></Button>
                                </Tooltip>
                            </Space.Compact>
                            <InputError :message="form.errors.link_url" class="mt-1" />
                        </div>
                        <Textarea v-model:value="form.notes" :rows="3" :placeholder="t('notes')" />

                        <label class="flex items-center gap-3 font-semibold">
                            <Switch :checked="!form.is_active" @change="form.is_active = !$event" />
                            <span>{{ t('pauseSubscription') }}</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <Button size="large" @click="showModal = false">{{ t('cancel') }}</Button>
                        <Button type="primary" html-type="submit" size="large" :loading="form.processing">{{ t('save') }}</Button>
                    </div>
                </form>
            </Modal>
        </ConfigProvider>
    </AuthenticatedLayout>
</template>
