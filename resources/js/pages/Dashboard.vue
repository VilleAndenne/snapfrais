<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    forms: Array<{
        id: number;
        name: string;
        description: string;
    }>;
    requests: Array<{
        id: number;
        form_name: string;
        status: string;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];


const goToForm = (formId) => {
    router.visit(route('expense-sheet.create', formId));
};

</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Formulaires disponibles -->
            <h2 class="text-xl font-semibold mb-2">Formulaires disponibles</h2>
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div v-for="form in forms" :key="form.id" @click="goToForm(form.id)"
                    class="relative cursor-pointer aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border hover:shadow-lg transition duration-300">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold">{{ form.name }}</h3>
                        <p class="text-sm text-gray-500">{{ form.description }}</p>
                    </div>
                </div>
            </div>

            <!-- Demandes encodées -->
            <h2 class="text-xl font-semibold mt-6 mb-2">Mes demandes encodées</h2>
            <div class="relative min-h-[300px] rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Formulaire
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Statut
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Date de création
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="request in requests" :key="request.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                {{ request.form_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span :class="[
                                    request.status === 'En attente' ? 'bg-yellow-100 text-yellow-800' :
                                        request.status === 'Approuvé' ? 'bg-green-100 text-green-800' :
                                            'bg-red-100 text-red-800',
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full'
                                ]">
                                    {{ request.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(request.created_at).toLocaleDateString('fr-FR') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button @click="goToForm(request.id)"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    Voir
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!requests?.length" class="p-4 text-gray-500">
                    Aucune demande encodée.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
