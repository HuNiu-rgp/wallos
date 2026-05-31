<script setup>
import InputError from '@/Components/InputError.vue';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const props = defineProps({
    settings: Object,
    channels: Object,
});
const { t } = useI18n();
const form = useForm({
    ...props.settings,
    webhook_enabled: props.settings.webhook_enabled === '1',
    webhook_ignore_ssl_errors: props.settings.webhook_ignore_ssl_errors === '1',
});
const testEmailForm = useForm({});
const testTelegramForm = useForm({});
const testWebhookForm = useForm({});
const telegramBotLink = computed(() => props.channels.telegramBotName
    ? `https://t.me/${props.channels.telegramBotName.replace(/^@/, '')}`
    : null);

function payload(data) {
    return {
        ...data,
        webhook_enabled: data.webhook_enabled ? 1 : 0,
        webhook_ignore_ssl_errors: data.webhook_ignore_ssl_errors ? 1 : 0,
    };
}

function submit() {
    form.transform(payload).patch(route('profile.notifications.update', undefined, false));
}

function sendTestEmail() {
    testEmailForm
        .transform(() => ({ smtp_notification_email: form.smtp_notification_email }))
        .post(route('profile.notifications.test-email', undefined, false));
}

function sendTestTelegram() {
    testTelegramForm
        .transform(() => ({ telegram_chat_id: form.telegram_chat_id }))
        .post(route('profile.notifications.test-telegram', undefined, false));
}

function sendTestWebhook() {
    testWebhookForm
        .transform(() => payload(form.data()))
        .post(route('profile.notifications.test-webhook', undefined, false));
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ t('notificationSettings') }}</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ t('notificationSettingsDescription') }}</p>
        </header>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('smtpNotificationEmail') }}</label>
                <div class="mt-1 flex flex-col gap-3 sm:flex-row">
                    <input v-model="form.smtp_notification_email" type="email" class="min-w-0 flex-1 rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                    <button v-if="channels.smtpEnabled" type="button" class="rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 disabled:opacity-50 dark:text-indigo-300" :disabled="testEmailForm.processing" @click="sendTestEmail">
                        {{ t('sendTestEmail') }}
                    </button>
                </div>
                <InputError :message="form.errors.smtp_notification_email || testEmailForm.errors.smtp_notification_email" class="mt-1" />
                <p v-if="!channels.smtpEnabled" class="mt-1 text-xs text-gray-500">{{ t('smtpUnavailable') }}</p>
            </div>

            <div>
                <div class="mb-3 rounded-md border border-indigo-100 bg-indigo-50 px-4 py-3 dark:border-indigo-900 dark:bg-indigo-950/40">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ t('telegramBindTitle') }}</p>
                            <p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-400">
                                {{ telegramBotLink ? t('telegramBindDescription') : t('telegramBotUnavailable') }}
                            </p>
                        </div>
                        <a v-if="channels.telegramEnabled && telegramBotLink" :href="telegramBotLink" target="_blank" rel="noopener noreferrer" class="shrink-0 rounded-md bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                            {{ t('bindTelegramBot') }} @{{ channels.telegramBotName.replace(/^@/, '') }}
                        </a>
                    </div>
                </div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('telegramChatId') }}</label>
                <div class="mt-1 flex flex-col gap-3 sm:flex-row">
                    <input v-model="form.telegram_chat_id" class="min-w-0 flex-1 rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                    <button v-if="channels.telegramEnabled" type="button" class="rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 disabled:opacity-50 dark:text-indigo-300" :disabled="testTelegramForm.processing" @click="sendTestTelegram">
                        {{ t('sendTestTelegram') }}
                    </button>
                </div>
                <InputError :message="form.errors.telegram_chat_id || testTelegramForm.errors.telegram_chat_id" class="mt-1" />
                <p v-if="!channels.telegramEnabled" class="mt-1 text-xs text-gray-500">{{ t('telegramUnavailable') }}</p>
            </div>

            <div class="border-t border-gray-200 pt-5 dark:border-gray-800">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ t('webhookSettings') }}</h3>
                    <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                        <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.webhook_enabled ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700'" @click="form.webhook_enabled = !form.webhook_enabled">
                            <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.webhook_enabled ? 'left-6' : 'left-1'"></span>
                        </button>
                        {{ form.webhook_enabled ? t('enabled') : t('disabled') }}
                    </label>
                </div>

                <div v-if="form.webhook_enabled" class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-[140px_minmax(0,1fr)]">
                        <select v-model="form.webhook_method" class="rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="PATCH">PATCH</option>
                        </select>
                        <input v-model="form.webhook_url" type="url" :placeholder="t('webhookUrl')" class="rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                    </div>
                    <textarea v-model="form.webhook_headers" rows="3" :placeholder="t('webhookHeaders')" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                    <textarea v-model="form.webhook_payload" rows="8" :placeholder="t('webhookPayload')" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                    <textarea v-model="form.webhook_cancellation_payload" rows="4" :placeholder="t('webhookCancellationPayload')" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                    <input v-model="form.webhook_secret" type="password" :placeholder="t('webhookSecret')" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <button type="button" class="relative h-7 w-12 rounded-full transition" :class="form.webhook_ignore_ssl_errors ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700'" @click="form.webhook_ignore_ssl_errors = !form.webhook_ignore_ssl_errors">
                                <span class="absolute top-1 h-5 w-5 rounded-full bg-white transition" :class="form.webhook_ignore_ssl_errors ? 'left-6' : 'left-1'"></span>
                            </button>
                            {{ t('webhookIgnoreSslErrors') }}
                        </label>
                        <button type="button" class="rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 disabled:opacity-50 dark:text-indigo-300" :disabled="testWebhookForm.processing" @click="sendTestWebhook">
                            {{ t('sendTestWebhook') }}
                        </button>
                    </div>
                    <p class="rounded-md bg-gray-50 px-3 py-2 text-xs leading-5 text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ t('webhookVariables') }}</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" :disabled="form.processing">
                    {{ t('save') }}
                </button>
            </div>
        </form>
    </section>
</template>
