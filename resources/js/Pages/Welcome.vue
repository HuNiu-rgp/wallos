<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const { t } = useI18n();
</script>

<template>
    <Head :title="t('appName')" />

    <main class="min-h-screen bg-gray-50 text-gray-900">
        <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6">
            <div class="flex items-center gap-2 text-lg font-semibold">
                <img v-if="$page.props.site.logoUrl" :src="$page.props.site.logoUrl" alt="" class="h-8 w-8 rounded object-contain" />
                {{ $page.props.site.name }}
            </div>
            <nav v-if="canLogin" class="flex items-center gap-3 text-sm">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white"
                >
                    {{ t('dashboard') }}
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="rounded-md border border-gray-300 px-4 py-2 font-medium">
                        {{ t('login') }}
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white"
                    >
                        {{ t('register') }}
                    </Link>
                </template>
            </nav>
        </header>

        <section class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[1fr_420px] lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">{{ t('subscriptions') }} + {{ t('categories') }}</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl">
                    {{ $page.props.site.name }}
                </h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-600">
                    默认中文的开源个人订阅追踪工具，帮你记录分类、订阅扣费周期、提醒和付款信息。
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="rounded-md bg-gray-900 px-5 py-3 text-sm font-medium text-white"
                    >
                        {{ t('register') }}
                    </Link>
                    <Link
                        :href="$page.props.auth.user ? route('dashboard') : route('login')"
                        class="rounded-md border border-gray-300 bg-white px-5 py-3 text-sm font-medium text-gray-800"
                    >
                        {{ $page.props.auth.user ? t('dashboard') : t('login') }}
                    </Link>
                </div>
            </div>

            <div class="rounded-lg border bg-white p-5 shadow-sm">
                <div class="grid gap-3">
                    <div class="rounded-md bg-gray-50 p-4">
                        <div class="text-sm text-gray-500">{{ t('monthlySubscriptions') }}</div>
                        <div class="mt-2 text-2xl font-semibold text-rose-700">$128.90</div>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <div class="text-sm text-gray-500">{{ t('upcomingSubscriptions') }}</div>
                        <div class="mt-2 space-y-2 text-sm">
                            <div class="flex justify-between"><span>Netflix</span><strong>$15.49</strong></div>
                            <div class="flex justify-between"><span>GitHub</span><strong>$4.00</strong></div>
                            <div class="flex justify-between"><span>iCloud</span><strong>$2.99</strong></div>
                        </div>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <div class="text-sm text-gray-500">{{ t('categories') }}</div>
                        <div class="mt-2 text-sm text-gray-700">分类、订阅、提醒和付款信息已经可用。</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
