<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { centsToAmount, money, useI18n } from '@/i18n';
import { computed, ref } from 'vue';

const props = defineProps({
    subscriptions: Array,
    categories: Array,
});

const { t } = useI18n();
const page = usePage();
const editing = ref(null);
const showModal = ref(false);
const logoPreview = ref(null);
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
    })).post(editing.value ? route('subscriptions.update', editing.value) : route('subscriptions.store'), {
        onSuccess: () => {
            showModal.value = false;
            reset();
        },
    });
}

function destroy(item) {
    if (window.confirm(t('confirmDelete'))) {
        router.delete(route('subscriptions.destroy', item.id));
    }
}

function openImport() {
    importInput.value?.click();
}

function importSubscriptions(event) {
    const [file] = event.target.files;

    if (!file) return;

    importForm.file = file;
    importForm.post(route('subscriptions.import'), {
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
                    <a :href="route('subscriptions.export')" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
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
                                    <td class="space-x-3 px-5 py-4 text-right">
                                        <a v-if="isRenewalDue(item.next_due_on) && renewalUrl(item.link_url)" :href="renewalUrl(item.link_url)" target="_blank" rel="noopener noreferrer" class="text-emerald-700 dark:text-emerald-300">
                                            {{ t('renew') }}
                                        </a>
                                        <button class="text-indigo-700" @click="edit(item)">{{ t('edit') }}</button>
                                        <button class="text-rose-700" @click="destroy(item)">{{ t('delete') }}</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
            <form class="max-h-[92vh] w-full max-w-[790px] overflow-y-auto rounded-lg border bg-white p-5 text-gray-900 shadow-2xl dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100" @submit.prevent="submit">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ modalTitle }}</h3>
                    <button type="button" class="text-3xl leading-none text-gray-400 hover:text-gray-700 dark:hover:text-gray-100" @click="showModal = false">&times;</button>
                </div>

                <div class="mt-5 grid gap-5">
                    <div v-if="form.hasErrors" class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <div v-for="(message, field) in form.errors" :key="field">{{ message }}</div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-[1fr_270px] md:items-start">
                        <label class="block">
                            <input v-model="form.name" :placeholder="t('name')" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500" />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </label>
                        <div class="flex items-start gap-3">
                            <label class="min-w-0 flex-1">
                                <input
                                    v-model="form.logo_url"
                                    :placeholder="t('logoUrl')"
                                    class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                                    @blur="previewLogo"
                                    @input="form.remove_logo = false"
                                />
                                <InputError :message="form.errors.logo_url" class="mt-1" />
                            </label>
                            <button type="button" class="flex h-10 w-10 items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 text-gray-400" @click="clearLogo">
                                <img v-if="logoPreview" :src="logoPreview" :alt="t('logoPreview')" class="h-full w-full rounded-md object-cover" />
                                <span v-else class="text-xl">◌</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label>
                            <input v-model="form.amount" type="number" min="0" step="0.01" :placeholder="t('amount')" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500" />
                            <InputError :message="form.errors.amount" class="mt-1" />
                        </label>
                        <label>
                            <select v-model="form.currency" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="[code, label] in currencies" :key="code" :value="code">{{ code }} - {{ label }}</option>
                            </select>
                            <InputError :message="form.errors.currency" class="mt-1" />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <div class="mb-2 font-semibold text-gray-800">{{ t('paymentFrequency') }}</div>
                            <div class="grid grid-cols-2 gap-3">
                                <select v-model="form.billing_interval" class="h-12 rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="number in 24" :key="number" :value="number">{{ number }}</option>
                                </select>
                                <select v-model="form.billing_cycle" class="h-12 rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="cycle in cycles" :key="cycle" :value="cycle">{{ t(cycle) }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="mb-2 font-semibold text-gray-800">{{ t('autoRenew') }}</div>
                            <label class="flex h-12 items-center gap-3">
                                <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.auto_renew ? 'bg-indigo-600' : 'bg-gray-300'" @click="form.auto_renew = !form.auto_renew">
                                    <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.auto_renew ? 'left-6' : 'left-1'"></span>
                                </button>
                                <span>{{ t('autoRenew') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[1fr_52px_1fr] md:items-end">
                        <label>
                            <span class="mb-2 block font-semibold text-gray-800">{{ t('startDate') }}</span>
                            <input v-model="form.start_on" type="date" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </label>
                        <button type="button" class="h-12 rounded-md border border-indigo-300 text-xl text-indigo-600 hover:bg-indigo-50" @click="calculateNextPayment">✣</button>
                        <label>
                            <span class="mb-2 block font-semibold text-gray-800">{{ t('nextPaymentDate') }}</span>
                            <input v-model="form.next_due_on" type="date" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <InputError :message="form.errors.next_due_on" class="mt-1" />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label>
                            <span class="mb-2 block font-semibold text-gray-800">{{ t('paymentMethod') }}</span>
                            <select v-model="form.payment_method" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="method in paymentMethods" :key="method" :value="method">{{ method }}</option>
                            </select>
                        </label>
                        <label>
                            <span class="mb-2 block font-semibold text-gray-800">{{ t('payer') }}</span>
                            <input v-model="form.payer_name" placeholder="Bob" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500" />
                        </label>
                    </div>

                    <label>
                        <span class="mb-2 block font-semibold text-gray-800">{{ t('category') }}</span>
                        <select v-model="form.category_id" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ t('noCategory') }}</option>
                            <option v-for="category in props.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </label>

                    <label class="flex items-center gap-3 font-semibold text-gray-800">
                        <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.notification_enabled ? 'bg-indigo-600' : 'bg-gray-300'" @click="form.notification_enabled = !form.notification_enabled">
                            <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.notification_enabled ? 'left-6' : 'left-1'"></span>
                        </button>
                        <span>{{ t('enableNotifications') }}</span>
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label>
                            <span class="mb-2 block font-semibold text-gray-800">{{ t('notificationLeadTime') }}</span>
                            <select v-model="form.notification_days_before" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ t('defaultFromSettings') }}</option>
                                <option value="0">{{ t('dueDate') }}</option>
                                <option v-for="day in notificationOptions" :key="day" :value="day">{{ day }} {{ t('daysSuffix') }}</option>
                            </select>
                        </label>
                        <label>
                            <span class="mb-2 block font-semibold text-gray-800">{{ t('cancellationNotice') }}</span>
                            <input v-model="form.cancellation_notice_on" type="date" class="h-12 w-full rounded-md border-gray-300 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </label>
                    </div>

                    <input v-model="form.link_url" :placeholder="t('link')" class="h-12 rounded-md border-gray-300 px-4 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500" />
                    <textarea v-model="form.notes" rows="3" :placeholder="t('notes')" class="rounded-md border-gray-300 px-4 py-3 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"></textarea>

                    <label class="flex items-center gap-3 font-semibold text-gray-800">
                        <button type="button" class="relative h-7 w-12 rounded-full transition" :class="!form.is_active ? 'bg-indigo-600' : 'bg-gray-300'" @click="form.is_active = !form.is_active">
                            <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="!form.is_active ? 'left-6' : 'left-1'"></span>
                        </button>
                        <span>{{ t('pauseSubscription') }}</span>
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <button type="button" class="rounded-md border border-gray-300 bg-white px-8 py-3 font-semibold text-gray-700 hover:bg-gray-50" @click="showModal = false">
                        {{ t('cancel') }}
                    </button>
                    <button class="rounded-md bg-indigo-600 px-8 py-3 font-semibold text-white hover:bg-indigo-500" :disabled="form.processing">
                        {{ t('save') }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
