<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AppIcon from '@/Components/AppIcon.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const { currentLocale, setLocale, t } = useI18n();
const dashboardUrl = route('dashboard', undefined, false);
const loginUrl = route('login', undefined, false);
const registerUrl = route('register', undefined, false);
const repositoryUrl = 'https://github.com/HuNiu-rgp/wallos';
const features = [
    ['calendar', 'landingFeatureTracking', 'landingFeatureTrackingDescription'],
    ['check-circle', 'landingFeatureReminder', 'landingFeatureReminderDescription'],
    ['wallet', 'landingFeatureSelfHosted', 'landingFeatureSelfHostedDescription'],
];
</script>

<template>
    <Head :title="$page.props.site.name" />

    <main class="min-h-screen bg-white text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
        <header class="border-b border-gray-200 bg-white/95 dark:border-gray-800 dark:bg-gray-950/95">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-2">
                    <img v-if="$page.props.site.logoUrl" :src="$page.props.site.logoUrl" alt="" class="h-9 w-9 shrink-0 rounded-md object-contain" />
                    <ApplicationLogo v-else class="h-9 w-9 shrink-0 fill-current text-gray-800 dark:text-gray-100" />
                    <span class="truncate text-base font-semibold">{{ $page.props.site.name }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        :value="currentLocale"
                        class="w-16 rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:w-auto"
                        :aria-label="t('language')"
                        @change="setLocale($event.target.value)"
                    >
                        <option value="zh">{{ t('chinese') }}</option>
                        <option value="en">{{ t('english') }}</option>
                    </select>
                    <ThemeToggle />
                    <a :href="repositoryUrl" target="_blank" rel="noopener noreferrer" class="hidden rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 md:inline-flex">
                        {{ t('viewDocumentation') }}
                    </a>
                    <nav v-if="canLogin" class="flex items-center gap-2 text-sm">
                        <Link v-if="$page.props.auth.user" :href="dashboardUrl" class="rounded-md bg-gray-950 px-4 py-2 font-medium text-white hover:bg-black dark:bg-indigo-600 dark:hover:bg-indigo-500">
                            {{ t('dashboard') }}
                        </Link>
                        <template v-else>
                            <Link :href="loginUrl" class="rounded-md border border-gray-300 bg-white px-4 py-2 font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                                {{ t('login') }}
                            </Link>
                            <Link v-if="canRegister" :href="registerUrl" class="hidden rounded-md bg-gray-950 px-4 py-2 font-medium text-white hover:bg-black dark:bg-indigo-600 dark:hover:bg-indigo-500 sm:inline-flex">
                                {{ t('register') }}
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <section class="relative isolate flex min-h-[calc(100svh-7rem)] items-end overflow-hidden bg-gray-950">
            <img src="/images/dashboard.png" alt="" class="absolute inset-0 h-full w-full object-cover object-top opacity-35" />
            <div class="absolute inset-0 bg-gray-950/60"></div>
            <div class="relative mx-auto w-full max-w-7xl px-6 pb-16 pt-24 sm:px-8 sm:pb-20 lg:px-10">
                <p class="text-sm font-semibold text-indigo-200">{{ t('landingEyebrow') }}</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight text-white sm:text-6xl">
                    {{ $page.props.site.name }}
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-gray-200 sm:text-lg">
                    {{ t('landingDescription') }}
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        :href="$page.props.auth.user ? dashboardUrl : loginUrl"
                        class="rounded-md bg-white px-5 py-3 text-sm font-semibold text-gray-950 shadow-sm hover:bg-gray-100"
                    >
                        {{ $page.props.auth.user ? t('dashboard') : t('login') }}
                    </Link>
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="registerUrl"
                        class="rounded-md border border-white/40 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10"
                    >
                        {{ t('register') }}
                    </Link>
                    <a :href="repositoryUrl" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-md border border-white/40 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">
                        <AppIcon name="docs" />
                        {{ t('viewDocumentation') }}
                    </a>
                </div>
            </div>
        </section>

        <section class="border-b border-gray-200 bg-white py-14 dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <div class="grid gap-8 md:grid-cols-3">
                    <div v-for="[icon, title, description] in features" :key="title" class="flex gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <AppIcon :name="icon" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-950 dark:text-white">{{ t(title) }}</h2>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">{{ t(description) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-gray-50 py-16 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">{{ t('landingProductPreview') }}</p>
                    <h2 class="mt-3 text-2xl font-semibold text-gray-950 dark:text-white sm:text-3xl">{{ t('landingPreviewTitle') }}</h2>
                    <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-400">{{ t('landingPreviewDescription') }}</p>
                </div>
                <div class="mt-8 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-950">
                    <img src="/images/subscriptions.png" :alt="t('subscriptions')" class="h-auto w-full" />
                </div>
            </div>
        </section>

        <footer class="border-t border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
                <span>{{ $page.props.site.name }} · {{ t('landingOpenSource') }}</span>
                <a :href="repositoryUrl" target="_blank" rel="noopener noreferrer" class="font-medium text-gray-700 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white">
                    GitHub
                </a>
            </div>
        </footer>
    </main>
</template>
