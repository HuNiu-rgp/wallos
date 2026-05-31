<script setup>
import { ref, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import { useI18n } from '@/i18n';

const showingNavigationDropdown = ref(false);
const { currentLocale, setLocale, t } = useI18n();
const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            message.success(flash.success);
        }

        if (flash?.error) {
            message.error(flash.error);
        }
    },
    { deep: true, immediate: true },
);

const navItems = [
    ['dashboard', 'dashboard'],
    ['categories.index', 'categories'],
    ['subscriptions.index', 'subscriptions'],
    ['calendar', 'calendar'],
];
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
            <nav
                class="border-b border-gray-100 bg-white transition-colors dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard', undefined, false)">
                                    <div class="flex items-center gap-2">
                                        <img
                                            v-if="$page.props.site.logoUrl"
                                            :src="$page.props.site.logoUrl"
                                            alt=""
                                            class="h-9 w-9 rounded-md object-contain"
                                        />
                                        <ApplicationLogo
                                            v-else
                                            class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-100"
                                        />
                                        <span class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $page.props.site.name }}</span>
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    v-for="[routeName, label] in navItems"
                                    :key="routeName"
                                    :href="route(routeName, undefined, false)"
                                    :active="route().current(routeName)"
                                >
                                    {{ t(label) }}
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-3">
                            <select
                                :value="currentLocale"
                                class="rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                @change="setLocale($event.target.value)"
                            >
                                <option value="zh">{{ t('chinese') }}</option>
                                <option value="en">{{ t('english') }}</option>
                            </select>

                            <ThemeToggle />

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-900 dark:text-gray-300 dark:hover:text-white"
                                            >
                                                <AppIcon name="user" class="me-2" />
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit', undefined, false)"
                                        >
                                            <span class="flex items-center gap-2">
                                                <AppIcon name="user" />
                                            {{ t('profile') }}
                                            </span>
                                        </DropdownLink>
                                        <DropdownLink v-if="$page.props.auth.isAdmin" :href="route('settings.edit', undefined, false)">
                                            <span class="flex items-center gap-2">
                                                <AppIcon name="settings" />
                                                {{ t('settings') }}
                                            </span>
                                        </DropdownLink>
                                        <DropdownLink
                                            v-if="$page.props.auth.isAdmin"
                                            :href="route('users.index', undefined, false)"
                                        >
                                            <span class="flex items-center gap-2">
                                                <AppIcon name="users" />
                                                {{ t('userManagement') }}
                                            </span>
                                        </DropdownLink>
                                        <a
                                            href="https://github.com/HuNiu-rgp/wallos"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-100 dark:hover:bg-gray-700 dark:focus:bg-gray-700"
                                        >
                                            <span class="flex items-center gap-2">
                                                <AppIcon name="docs" />
                                                {{ t('viewDocumentation') }}
                                            </span>
                                        </a>
                                        <DropdownLink
                                            :href="route('logout', undefined, false)"
                                            method="post"
                                            as="button"
                                        >
                                            <span class="flex items-center gap-2">
                                                <AppIcon name="logout" />
                                                {{ t('logout') }}
                                            </span>
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:hover:bg-gray-800 dark:hover:text-gray-200 dark:focus:bg-gray-800 dark:focus:text-gray-200"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            v-for="[routeName, label] in navItems"
                            :key="routeName"
                            :href="route(routeName, undefined, false)"
                            :active="route().current(routeName)"
                        >
                            {{ t(label) }}
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4 dark:border-gray-800"
                    >
                        <div class="px-4">
                            <div class="flex items-center gap-2 text-base font-medium text-gray-800 dark:text-gray-100">
                                <AppIcon name="user" />
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit', undefined, false)">
                                <span class="flex items-center gap-2"><AppIcon name="user" />{{ t('profile') }}</span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink v-if="$page.props.auth.isAdmin" :href="route('settings.edit', undefined, false)">
                                <span class="flex items-center gap-2"><AppIcon name="settings" />{{ t('settings') }}</span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink v-if="$page.props.auth.isAdmin" :href="route('users.index', undefined, false)">
                                <span class="flex items-center gap-2"><AppIcon name="users" />{{ t('userManagement') }}</span>
                            </ResponsiveNavLink>
                            <a
                                href="https://github.com/HuNiu-rgp/wallos"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                            >
                                <span class="flex items-center gap-2"><AppIcon name="docs" />{{ t('viewDocumentation') }}</span>
                            </a>
                            <ResponsiveNavLink
                                :href="route('logout', undefined, false)"
                                method="post"
                                as="button"
                            >
                                <span class="flex items-center gap-2"><AppIcon name="logout" />{{ t('logout') }}</span>
                            </ResponsiveNavLink>
                            <div class="px-4 py-2">
                                <select
                                    :value="currentLocale"
                                    class="w-full rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                    @change="setLocale($event.target.value)"
                                >
                                    <option value="zh">{{ t('chinese') }}</option>
                                    <option value="en">{{ t('english') }}</option>
                                </select>
                            </div>
                            <div class="px-4 py-2">
                                <ThemeToggle />
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow transition-colors dark:bg-gray-900 dark:shadow-gray-950"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
