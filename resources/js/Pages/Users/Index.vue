<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from '@/i18n';

const props = defineProps({
    users: Array,
});

const { t } = useI18n();
const editing = ref(null);
const showModal = ref(false);
const search = ref('');
const form = useForm({
    _method: '',
    name: '',
    email: '',
    role: 'user',
    password: '',
    password_confirmation: '',
});

const filteredUsers = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    return props.users.filter((user) => !keyword
        || user.name.toLowerCase().includes(keyword)
        || user.email.toLowerCase().includes(keyword));
});

function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form._method = '';
}

function openCreate() {
    reset();
    showModal.value = true;
}

function edit(user) {
    reset();
    editing.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    showModal.value = true;
}

function submit() {
    form._method = editing.value ? 'put' : '';
    form.post(editing.value ? route('users.update', editing.value.id) : route('users.store'), {
        onSuccess: () => {
            showModal.value = false;
            reset();
        },
    });
}

function destroy(user) {
    if (window.confirm(t('confirmDeleteUser'))) {
        router.delete(route('users.destroy', user.id));
    }
}

function dateValue(value) {
    return value ? String(value).slice(0, 10) : '';
}
</script>

<template>
    <Head :title="t('userManagement')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ t('userManagement') }}</h2>
                <button class="rounded-md bg-gray-950 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black dark:bg-black dark:hover:bg-gray-800" @click="openCreate">
                    {{ t('createUser') }}
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-lg border bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b bg-gray-50 px-5 py-4 dark:border-gray-800 dark:bg-gray-800">
                        <input v-model="search" type="search" :placeholder="`${t('search')} ${t('userManagement')}`" class="h-10 w-full max-w-md rounded-md border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 text-left text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-5 py-3">{{ t('name') }}</th>
                                    <th class="px-5 py-3">{{ t('email') }}</th>
                                    <th class="px-5 py-3">{{ t('role') }}</th>
                                    <th class="px-5 py-3">{{ t('subscriptionsCount') }}</th>
                                    <th class="px-5 py-3">{{ t('categoriesCount') }}</th>
                                    <th class="px-5 py-3">{{ t('registeredAt') }}</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="user in filteredUsers" :key="user.id">
                                    <td class="px-5 py-4 font-medium text-gray-900 dark:text-gray-100">
                                        {{ user.name }}
                                        <span v-if="user.email === 'admin@qq.com'" class="ml-2 rounded bg-gray-100 px-2 py-1 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-300">{{ t('defaultAdmin') }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ user.email }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            {{ user.role === 'admin' ? t('administrator') : t('regularUser') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">{{ user.subscriptions_count }}</td>
                                    <td class="px-5 py-4">{{ user.categories_count }}</td>
                                    <td class="px-5 py-4">{{ dateValue(user.created_at) }}</td>
                                    <td class="space-x-3 px-5 py-4 text-right">
                                        <button class="text-indigo-700 dark:text-indigo-300" @click="edit(user)">{{ t('edit') }}</button>
                                        <button v-if="user.email !== 'admin@qq.com'" class="text-rose-700 dark:text-rose-300" @click="destroy(user)">{{ t('delete') }}</button>
                                    </td>
                                </tr>
                                <tr v-if="!filteredUsers.length">
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">{{ t('noData') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
            <form class="w-full max-w-lg rounded-lg border bg-white p-5 text-gray-900 shadow-2xl dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100" @submit.prevent="submit">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                    <h3 class="text-lg font-semibold">{{ editing ? t('editUser') : t('createUser') }}</h3>
                    <button type="button" class="text-3xl leading-none text-gray-400 hover:text-gray-700 dark:hover:text-gray-100" @click="showModal = false">&times;</button>
                </div>
                <div class="mt-5 grid gap-4">
                    <label>
                        <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('name') }}</span>
                        <input v-model="form.name" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </label>
                    <label>
                        <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('email') }}</span>
                        <input v-model="form.email" type="email" :disabled="editing?.email === 'admin@qq.com'" class="w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:disabled:bg-gray-800" />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </label>
                    <label>
                        <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('role') }}</span>
                        <select v-model="form.role" :disabled="editing?.email === 'admin@qq.com'" class="w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:disabled:bg-gray-800">
                            <option value="user">{{ t('regularUser') }}</option>
                            <option value="admin">{{ t('administrator') }}</option>
                        </select>
                        <InputError :message="form.errors.role" class="mt-1" />
                    </label>
                    <label>
                        <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('password') }}</span>
                        <input v-model="form.password" type="password" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                        <span v-if="editing" class="mt-1 block text-xs text-gray-500">{{ t('resetPasswordHint') }}</span>
                        <InputError :message="form.errors.password" class="mt-1" />
                    </label>
                    <label>
                        <span class="mb-1 block text-sm text-gray-700 dark:text-gray-300">{{ t('confirmPassword') }}</span>
                        <input v-model="form.password_confirmation" type="password" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800" @click="showModal = false">{{ t('cancel') }}</button>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" :disabled="form.processing">{{ t('save') }}</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
