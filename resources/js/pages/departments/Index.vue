<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Départements" />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Départements</h2>

                <div class="flex flex-col xs:flex-row gap-2 w-full sm:w-auto">
                    <button @click="navigateToCreate" class="px-3 sm:px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md flex items-center justify-center gap-2 text-sm">
                        <PlusIcon class="h-4 w-4" />
                        Ajouter
                    </button>

                    <div class="relative w-full">
                        <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <input v-model="search" type="text" placeholder="Rechercher un département..." class="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm" />
                        <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <Badge variant="outline" class="px-2 sm:px-3 py-0.5 sm:py-1 text-xs">
                    {{ filteredDepartments.length }} département{{ filteredDepartments.length > 1 ? 's' : '' }}
                </Badge>
            </div>

            <!-- Vue Mobile (cartes) - visible uniquement sur mobile -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="department in filteredDepartments"
                    :key="department.id"
                    class="border rounded-lg p-4 bg-card space-y-3"
                >
                    <div class="flex items-start gap-2">
                        <Building2Icon class="h-5 w-5 flex-shrink-0 mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm">{{ department.name }}</h3>
                            <p class="text-xs text-muted-foreground truncate">
                                Supérieur : {{ getParentName(department.parent_id) || 'Aucun' }}
                            </p>
                        </div>
                    </div>

                    <div class="pt-2 border-t flex gap-2">
                        <button @click="navigateToEdit(department.id)" class="flex-1 px-3 py-2 border rounded-md text-sm hover:bg-muted transition-colors flex items-center justify-center gap-1">
                            <PencilIcon class="h-4 w-4" />
                            Modifier
                        </button>
                        <button @click="confirmDelete(department)" class="px-3 py-2 border rounded-md text-sm hover:bg-muted transition-colors text-destructive">
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-if="filteredDepartments.length === 0" class="text-center p-8 text-muted-foreground border rounded-lg">
                    <Building2Icon class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-sm">Aucun département trouvé.</p>
                </div>
            </div>

            <!-- Vue Desktop (tableau) - visible sur tablette et + -->
            <div class="hidden md:block overflow-x-auto border rounded-xl">
                <table class="min-w-full divide-y">
                    <thead class="bg-muted">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Nom</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Service supérieur</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="department in filteredDepartments" :key="department.id" class="hover:bg-muted/50 transition-colors">
                        <td class="px-4 lg:px-6 py-4">
                            <div class="flex items-center gap-2">
                                <Building2Icon class="h-5 w-5 flex-shrink-0" />
                                <span class="text-sm font-medium">{{ department.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 lg:px-6 py-4 text-sm">{{ getParentName(department.parent_id) || 'Aucun' }}</td>
                        <td class="px-4 lg:px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button @click="navigateToEdit(department.id)" class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors flex items-center gap-1">
                                    <PencilIcon class="h-4 w-4" />
                                    Modifier
                                </button>
                                <button @click="confirmDelete(department)" class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors text-destructive">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div v-if="filteredDepartments.length === 0" class="text-center p-8 text-muted-foreground">
                    <Building2Icon class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-sm">Aucun département trouvé.</p>
                </div>
            </div>

            <!-- Modal de confirmation de suppression -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent class="max-w-[90vw] sm:max-w-md">
                    <AlertDialogHeader>
                        <AlertDialogTitle class="text-base sm:text-lg">Êtes-vous sûr de vouloir supprimer ce département ?</AlertDialogTitle>
                        <AlertDialogDescription class="text-xs sm:text-sm">
                            Cette action ne peut pas être annulée. Le département "{{ departmentToDelete?.name }}" sera
                            définitivement supprimé.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="flex-col xs:flex-row gap-2">
                        <Button variant="outline" @click="showDeleteDialog = false" class="w-full xs:w-auto">Annuler</Button>
                        <Button
                            variant="destructive"
                            @click="deleteDepartment"
                            :disabled="isDeleting"
                            class="w-full xs:w-auto"
                        >
                            <LoaderIcon v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
                            Supprimer
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { PlusIcon, SearchIcon, PencilIcon, TrashIcon, LoaderIcon, X, Building2Icon } from 'lucide-vue-next';

// shadcn/ui
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter
} from '@/components/ui/alert-dialog';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
// État
const departments = ref(page.props.departments);
const isDeleting = ref(false);

const breadcrumbs = [
    {
        title: 'Départements',
        href: route('departments.index')
    }
];

const search = ref('');
const showDeleteDialog = ref(false);
const departmentToDelete = ref(null);

// Filtrer les départements en fonction de la recherche
const filteredDepartments = computed(() => {
    if (!search.value) return departments.value;

    const query = search.value.toLowerCase();
    return departments.value.filter(dept =>
        dept.name.toLowerCase().includes(query)
    );
});

// Naviguer vers la page de création
const navigateToCreate = () => {
    router.visit(route('departments.create'));
};

// Naviguer vers la page d'édition
const navigateToEdit = (departmentId) => {
    router.visit(route('departments.edit', departmentId));
};

// Ouvrir la modale de confirmation
const confirmDelete = (department) => {
    departmentToDelete.value = department;
    showDeleteDialog.value = true;
};

// Supprimer un département
const deleteDepartment = () => {
    if (departmentToDelete.value) {
        isDeleting.value = true;
        router.delete(route('departments.destroy', departmentToDelete.value.id), {
            onSuccess: () => {
                showDeleteDialog.value = false;
                departmentToDelete.value = null;
            },
            onFinish: () => {
                isDeleting.value = false;
            }
        });
    }
};

// Obtenir le nom du département parent
const getParentName = (parentId) => {
    if (!parentId) return null;
    const parent = departments.value.find(d => d.id === parentId);
    return parent ? parent.name : null;
};
</script>
