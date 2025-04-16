<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { getStatusLabel } from '../utils/formatters';

const props = defineProps<{
    forms: Array<{
        id: number;
        name: string;
        description: string;
    }>;
    expenseSheets: Array<{
        id: number;
        type: string;
        distance: number;
        route: string;
        total: number;
        status: string;
        created_at: string;
    }>;
}>();


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard'
    }
];


const goToForm = (formId) => {
    router.visit('/expense-sheet/' + formId + '/create');
};

</script>

<template>

    <Head title="Tableau de bord" />

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

            <!-- Notes de frais encodées -->
            <h2 class="text-xl font-semibold mt-6 mb-2">Mes notes de frais</h2>
            <div class="relative min-h-[300px] rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Montant (€)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Créé le
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="sheet in expenseSheets" :key="sheet.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                            {{ sheet.form.name
                            }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                            {{ sheet.total + ' €' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <Badge
                                :variant="getStatusLabel(sheet).variant"
                            >
                                {{ getStatusLabel(sheet).label }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ new Date(sheet.created_at).toLocaleDateString('fr-FR') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <Link :href="'/expense-sheet/' + sheet.id">
                                <Button variant="outline">
                                    Voir
                                </Button>
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p v-if="!expenseSheets?.length" class="p-4 text-gray-500">Aucune note de frais encodée.</p>
            </div>

        </div>
    </AppLayout>
</template>
