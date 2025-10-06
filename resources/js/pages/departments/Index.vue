<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Départements" />
        <div class="p-3 sm:p-4">
            <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3 sm:mb-4">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Départements</h2>
                <div class="flex flex-col xs:flex-row items-stretch xs:items-center gap-2 w-full sm:w-auto">
                    <Input
                        v-model="search"
                        placeholder="Rechercher..."
                        class="w-full xs:w-[200px]"
                    >
                        <template #leading>
                            <SearchIcon class="h-4 w-4 text-muted-foreground" />
                        </template>
                    </Input>
                    <Button @click="navigateToCreate" class="w-full xs:w-auto">
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Ajouter
                    </Button>
                </div>
            </header>

            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="text-xs sm:text-sm">Nom</TableHead>
                                <TableHead class="hidden sm:table-cell text-xs sm:text-sm">Service supérieur</TableHead>
                                <TableHead class="w-[80px] sm:w-[100px] text-xs sm:text-sm">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="department in filteredDepartments" :key="department.id">
                                <TableCell class="text-xs sm:text-sm">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ department.name }}</span>
                                        <span class="sm:hidden text-xs text-muted-foreground">{{ getParentName(department.parent_id) || 'Aucun' }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="hidden sm:table-cell text-xs sm:text-sm">
                                    {{ getParentName(department.parent_id) || 'Aucun' }}
                                </TableCell>
                                <TableCell>
                                <div class="flex items-center gap-1 sm:gap-2">
                                    <Button variant="ghost" size="icon" @click="navigateToEdit(department.id)" class="h-8 w-8 sm:h-10 sm:w-10">
                                        <PencilIcon class="h-3 w-3 sm:h-4 sm:w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(department)" class="h-8 w-8 sm:h-10 sm:w-10">
                                        <TrashIcon class="h-3 w-3 sm:h-4 sm:w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="filteredDepartments?.length === 0">
                            <TableCell colspan="3" class="h-20 sm:h-24 text-center text-xs sm:text-sm">
                                Aucun département trouvé.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                    <TableFooter v-if="departments.length > 0">
                        <TableRow>
                            <TableCell colspan="3" class="text-xs sm:text-sm">
                                Total: {{ departments.length }} département{{ departments.length > 1 ? 's' : '' }}
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
                </div>
            </Card>

            <!-- Modal de confirmation de suppression -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent class="max-w-[90vw] sm:max-w-md">
                    <AlertDialogHeader>
                        <AlertDialogTitle class="text-base sm:text-lg">Êtes-vous sûr de vouloir supprimer ce département?</AlertDialogTitle>
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
import { Head, usePage, router, useForm } from '@inertiajs/vue3';
import { PlusIcon, SearchIcon, PencilIcon, TrashIcon, LoaderIcon } from 'lucide-vue-next';

// Import explicite des composants shadcn/ui
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableHeader,
    TableBody,
    TableFooter,
    TableHead,
    TableRow,
    TableCell
} from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter
} from '@/components/ui/alert-dialog';
import {
    Avatar,
    AvatarFallback
} from '@/components/ui/avatar';
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

// Confirmer la suppression
const confirmDelete = (department) => {
    departmentToDelete.value = department;
    showDeleteDialog.value = true;
    const formDelete = useForm({
        id: department.id
    });

    formDelete.delete(route('departments.destroy', department.id));

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

// Obtenir les initiales d'un utilisateur
const getUserInitials = (name) => {
    return name
        .split(' ')
        .map(part => part[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};
</script>
