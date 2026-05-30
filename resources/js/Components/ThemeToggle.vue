<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';
import { useTheme } from '@/theme';

const { t } = useI18n();
const { currentTheme, toggleTheme } = useTheme();
const nextThemeLabel = computed(() => (currentTheme.value === 'dark' ? t('lightMode') : t('darkMode')));
</script>

<template>
    <button
        type="button"
        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900"
        :aria-label="nextThemeLabel"
        :title="nextThemeLabel"
        @click="toggleTheme"
    >
        <svg
            v-if="currentTheme === 'dark'"
            class="h-5 w-5"
            aria-hidden="true"
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            viewBox="0 0 24 24"
        >
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
        </svg>
        <svg
            v-else
            class="h-5 w-5"
            aria-hidden="true"
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            viewBox="0 0 24 24"
        >
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
        </svg>
    </button>
</template>
