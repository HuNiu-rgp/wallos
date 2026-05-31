<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const props = defineProps({
    settings: Object,
    isAdmin: Boolean,
});

const { t } = useI18n();
const currencies = ['CNY', 'USD', 'EUR', 'GBP', 'JPY', 'HKD', 'TWD', 'CAD', 'AUD', 'SGD'];
const timezones = ['Asia/Shanghai', 'Asia/Hong_Kong', 'Asia/Tokyo', 'Europe/Madrid', 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'UTC'];

const form = useForm({
    ...props.settings,
    registration_enabled: props.settings.registration_enabled === '1',
    smtp_enabled: props.settings.smtp_enabled === '1',
    telegram_enabled: props.settings.telegram_enabled === '1',
    webhook_enabled: props.settings.webhook_enabled === '1',
    webhook_ignore_ssl_errors: props.settings.webhook_ignore_ssl_errors === '1',
});
const testEmailForm = useForm({});
const testTelegramForm = useForm({});
const testWebhookForm = useForm({});

function submit() {
    form.transform((data) => ({
        ...data,
        registration_enabled: data.registration_enabled ? 1 : 0,
        smtp_enabled: data.smtp_enabled ? 1 : 0,
        telegram_enabled: data.telegram_enabled ? 1 : 0,
        webhook_enabled: data.webhook_enabled ? 1 : 0,
        webhook_ignore_ssl_errors: data.webhook_ignore_ssl_errors ? 1 : 0,
    })).patch(route('settings.update', undefined, false));
}

function sendTestTelegram() {
    testTelegramForm
        .transform(() => ({
            telegram_bot_token: form.telegram_bot_token,
            telegram_chat_id: form.telegram_chat_id,
        }))
        .post(route('settings.test-telegram', undefined, false));
}

function sendTestWebhook() {
    testWebhookForm
        .transform(() => ({
            webhook_url: form.webhook_url,
            webhook_method: form.webhook_method,
            webhook_headers: form.webhook_headers,
            webhook_payload: form.webhook_payload,
            webhook_ignore_ssl_errors: form.webhook_ignore_ssl_errors ? 1 : 0,
            webhook_secret: form.webhook_secret,
        }))
        .post(route('settings.test-webhook', undefined, false));
}

function sendTestEmail() {
    testEmailForm
        .transform(() => ({
            smtp_host: form.smtp_host,
            smtp_port: form.smtp_port,
            smtp_username: form.smtp_username,
            smtp_password: form.smtp_password,
            smtp_encryption: form.smtp_encryption,
            smtp_from_address: form.smtp_from_address,
            smtp_from_name: form.smtp_from_name,
            smtp_notification_email: form.smtp_notification_email,
        }))
        .post(route('settings.test-email', undefined, false));
}
</script>

<template>
    <Head :title="t('settings')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ t('settings') }}</h2>
        </template>

        <div class="py-8">
            <form class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <section v-if="props.isAdmin" class="rounded-lg border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="mb-5 font-semibold text-gray-900 dark:text-gray-100">{{ t('generalSettings') }}</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('siteName') }}</span>
                            <input v-model="form.site_name" class="w-full rounded-md border-gray-300 shadow-sm" />
                            <InputError :message="form.errors.site_name" class="mt-1" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('siteLogoUrl') }}</span>
                            <input v-model="form.site_logo_url" class="w-full rounded-md border-gray-300 shadow-sm" />
                            <InputError :message="form.errors.site_logo_url" class="mt-1" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('defaultCurrency') }}</span>
                            <select v-model="form.default_currency" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option v-for="currency in currencies" :key="currency" :value="currency">{{ currency }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('timezone') }}</span>
                            <select v-model="form.timezone" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option v-for="timezone in timezones" :key="timezone" :value="timezone">{{ timezone }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('defaultNotificationDays') }}</span>
                            <input v-model="form.default_notification_days" type="number" min="0" max="365" class="w-full rounded-md border-gray-300 shadow-sm" />
                        </label>
                        <label class="flex items-end gap-3 pb-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.registration_enabled ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700'" @click="form.registration_enabled = !form.registration_enabled">
                                <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.registration_enabled ? 'left-6' : 'left-1'"></span>
                            </button>
                            {{ t('enableRegistration') }}
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ t('smtpSettings') }}</h3>
                        <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.smtp_enabled ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700'" @click="form.smtp_enabled = !form.smtp_enabled">
                                <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.smtp_enabled ? 'left-6' : 'left-1'"></span>
                            </button>
                            {{ form.smtp_enabled ? t('enabled') : t('disabled') }}
                        </label>
                    </div>
                    <div v-if="form.smtp_enabled" class="grid gap-4 md:grid-cols-2">
                        <input v-model="form.smtp_host" :placeholder="t('smtpHost')" class="rounded-md border-gray-300 shadow-sm" />
                        <input v-model="form.smtp_port" type="number" :placeholder="t('smtpPort')" class="rounded-md border-gray-300 shadow-sm" />
                        <input v-model="form.smtp_username" :placeholder="t('smtpUsername')" class="rounded-md border-gray-300 shadow-sm" />
                        <input v-model="form.smtp_password" type="password" :placeholder="t('smtpPassword')" class="rounded-md border-gray-300 shadow-sm" />
                        <select v-model="form.smtp_encryption" class="rounded-md border-gray-300 shadow-sm">
                            <option value="none">{{ t('noEncryption') }}</option>
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                        </select>
                        <input v-model="form.smtp_from_address" type="email" :placeholder="t('smtpFromAddress')" class="rounded-md border-gray-300 shadow-sm" />
                        <input v-model="form.smtp_from_name" :placeholder="t('smtpFromName')" class="rounded-md border-gray-300 shadow-sm md:col-span-2" />
                        <label class="block md:col-span-2">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('smtpNotificationEmail') }}</span>
                            <input v-model="form.smtp_notification_email" type="email" class="w-full rounded-md border-gray-300 shadow-sm" />
                            <InputError :message="form.errors.smtp_notification_email || testEmailForm.errors.smtp_notification_email" class="mt-1" />
                        </label>
                        <div class="flex justify-end md:col-span-2">
                            <button
                                type="button"
                                class="rounded-md border border-indigo-600 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 dark:bg-gray-900 dark:text-indigo-300 dark:hover:bg-gray-800"
                                :disabled="testEmailForm.processing"
                                @click="sendTestEmail"
                            >
                                {{ t('sendTestEmail') }}
                            </button>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ t('telegramSettings') }}</h3>
                        <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.telegram_enabled ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700'" @click="form.telegram_enabled = !form.telegram_enabled">
                                <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.telegram_enabled ? 'left-6' : 'left-1'"></span>
                            </button>
                            {{ form.telegram_enabled ? t('enabled') : t('disabled') }}
                        </label>
                    </div>
                    <div v-if="form.telegram_enabled" class="grid gap-4 md:grid-cols-2">
                        <input v-model="form.telegram_bot_token" :placeholder="t('telegramBotToken')" class="rounded-md border-gray-300 shadow-sm" />
                        <input v-model="form.telegram_chat_id" :placeholder="t('telegramChatId')" class="rounded-md border-gray-300 shadow-sm" />
                        <div class="flex justify-end md:col-span-2">
                            <button type="button" class="rounded-md border border-indigo-600 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 dark:bg-gray-900 dark:text-indigo-300 dark:hover:bg-gray-800" :disabled="testTelegramForm.processing" @click="sendTestTelegram">
                                {{ t('sendTestTelegram') }}
                            </button>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ t('webhookSettings') }}</h3>
                        <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.webhook_enabled ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700'" @click="form.webhook_enabled = !form.webhook_enabled">
                                <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.webhook_enabled ? 'left-6' : 'left-1'"></span>
                            </button>
                            {{ form.webhook_enabled ? t('enabled') : t('disabled') }}
                        </label>
                    </div>
                    <div v-if="form.webhook_enabled" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-[180px_minmax(0,1fr)]">
                            <label class="block">
                                <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('webhookMethod') }}</span>
                                <select v-model="form.webhook_method" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="PATCH">PATCH</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('webhookUrl') }}</span>
                                <input v-model="form.webhook_url" type="url" :placeholder="t('webhookUrl')" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                            </label>
                        </div>

                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('webhookHeaders') }}</span>
                            <textarea v-model="form.webhook_headers" rows="4" :placeholder="t('webhookHeaders')" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('webhookPayload') }}</span>
                            <textarea v-model="form.webhook_payload" rows="11" :placeholder="t('webhookPayload')" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('webhookCancellationPayload') }}</span>
                            <textarea v-model="form.webhook_cancellation_payload" rows="6" :placeholder="t('webhookCancellationPayload')" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('webhookSecret') }}</span>
                            <input v-model="form.webhook_secret" type="password" :placeholder="t('webhookSecret')" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                        </label>

                        <div class="flex flex-col gap-4 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                            <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.webhook_ignore_ssl_errors ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700'" @click="form.webhook_ignore_ssl_errors = !form.webhook_ignore_ssl_errors">
                                    <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.webhook_ignore_ssl_errors ? 'left-6' : 'left-1'"></span>
                                </button>
                                {{ t('webhookIgnoreSslErrors') }}
                            </label>
                            <button type="button" class="self-start rounded-md border border-indigo-600 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 sm:self-auto dark:bg-gray-900 dark:text-indigo-300 dark:hover:bg-gray-800" :disabled="testWebhookForm.processing" @click="sendTestWebhook">
                                {{ t('sendTestWebhook') }}
                            </button>
                        </div>

                        <p class="rounded-md bg-gray-50 px-3 py-2 text-xs leading-5 text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ t('webhookVariables') }}</p>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button class="rounded-md bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" :disabled="form.processing">
                        {{ t('save') }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
