<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { currentLocale, setLocale, t } = useI18n();
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100 sm:justify-center sm:pt-0"
    >
        <div class="fixed right-4 top-4 flex items-center gap-2">
            <select
                :value="currentLocale"
                class="rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                :aria-label="t('language')"
                @change="setLocale($event.target.value)"
            >
                <option value="zh">{{ t('chinese') }}</option>
                <option value="en">{{ t('english') }}</option>
            </select>
            <ThemeToggle />
        </div>

        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500 dark:text-gray-300" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md dark:bg-gray-900 sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
