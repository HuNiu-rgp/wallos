<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { computed, ref } from 'vue';

const props = defineProps({ categories: Array });
const { t } = useI18n();
const editing = ref(null);

const form = useForm({
    name: '',
    type: 'expense',
    parent_id: '',
    color: '#4f46e5',
    icon: '',
});

const parentOptions = computed(() => props.categories.filter((category) => category.id !== editing.value && category.type === form.type));

function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.type = 'expense';
    form.parent_id = '';
    form.color = '#4f46e5';
}

function edit(category) {
    editing.value = category.id;
    form.name = category.name;
    form.type = category.type;
    form.parent_id = category.parent_id || '';
    form.color = category.color || '#4f46e5';
    form.icon = category.icon || '';
}

function submit() {
    if (editing.value) {
        form.put(route('categories.update', editing.value, false), { onSuccess: reset });
        return;
    }

    form.post(route('categories.store', undefined, false), { onSuccess: reset });
}

function destroy(category) {
    if (window.confirm(t('confirmDelete'))) {
        router.delete(route('categories.destroy', category.id, false));
    }
}
</script>

<template>
    <Head :title="t('categories')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ t('categories') }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[360px_1fr] lg:px-8">
                <form class="rounded-lg border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="submit">
                    <h3 class="mb-4 font-semibold text-gray-900 dark:text-gray-100">{{ editing ? t('edit') : t('createCategory') }}</h3>
                    <div class="space-y-4">
                        <label class="block text-sm">
                            <span class="text-gray-700 dark:text-gray-300">{{ t('name') }}</span>
                            <input v-model="form.name" class="mt-1 w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </label>
                        <label class="block text-sm">
                            <span class="text-gray-700 dark:text-gray-300">{{ t('type') }}</span>
                            <select v-model="form.type" class="mt-1 w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <option value="expense">{{ t('expense') }}</option>
                                <option value="income">{{ t('income') }}</option>
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="text-gray-700 dark:text-gray-300">{{ t('category') }}</span>
                            <select v-model="form.parent_id" class="mt-1 w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                                <option value="">{{ t('selectOptional') }}</option>
                                <option v-for="category in parentOptions" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ t('color') }}</span>
                                <input v-model="form.color" type="color" class="mt-1 h-10 w-full rounded-md border-gray-300 dark:border-gray-700" />
                            </label>
                            <label class="block text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ t('icon') }}</span>
                                <input v-model="form.icon" class="mt-1 w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" />
                            </label>
                        </div>
                    </div>
                    <div class="mt-5 flex gap-2">
                        <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-indigo-600" :disabled="form.processing">
                            {{ editing ? t('update') : t('add') }}
                        </button>
                        <button v-if="editing" type="button" class="rounded-md border px-4 py-2 text-sm dark:border-gray-700" @click="reset">{{ t('cancel') }}</button>
                    </div>
                </form>

                <section class="overflow-hidden rounded-lg border bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 text-left text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-5 py-3">{{ t('name') }}</th>
                                    <th class="px-5 py-3">{{ t('type') }}</th>
                                    <th class="px-5 py-3">{{ t('category') }}</th>
                                    <th class="px-5 py-3">{{ t('subscriptions') }}</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-if="!props.categories.length">
                                    <td colspan="5" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">{{ t('noData') }}</td>
                                </tr>
                                <tr v-for="category in props.categories" :key="category.id">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2 font-medium text-gray-900 dark:text-gray-100">
                                            <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: category.color || '#9ca3af' }"></span>
                                            {{ category.name }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">{{ t(category.type) }}</td>
                                    <td class="px-5 py-4">{{ category.parent?.name || t('selectOptional') }}</td>
                                    <td class="px-5 py-4">{{ category.subscriptions_count }}</td>
                                    <td class="space-x-3 px-5 py-4 text-right">
                                        <button class="text-indigo-700" @click="edit(category)">{{ t('edit') }}</button>
                                        <button class="text-rose-700" @click="destroy(category)">{{ t('delete') }}</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
