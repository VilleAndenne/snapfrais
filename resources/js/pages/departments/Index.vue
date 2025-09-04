<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Départements" />
        <div class="p-6 space-y-6">
            <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-2xl font-semibold tracking-tight">Départements</h2>
                <div class="flex items-center gap-2">
                    <Input
                        v-model="search"
                        placeholder="Rechercher..."
                        class="w-[200px]"
                    >
                        <template #leading>
                            <SearchIcon class="h-4 w-4 text-muted-foreground" />
                        </template>
                    </Input>
                    <Button @click="navigateToCreate">
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Ajouter
                    </Button>
                </div>
            </header>

            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom</TableHead>
                            <TableHead>Service supérieur</TableHead>
                            <TableHead class="w-[100px]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="department in filteredDepartments" :key="department.id">
                            <TableCell>{{ department.name }}</TableCell>
                            <TableCell>
                                {{ getParentName(department.parent_id) || 'Aucun' }}
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Button variant="ghost" size="icon" @click="navigateToEdit(department.id)">
                                        <PencilIcon class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(department)">
                                        <TrashIcon class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="filteredDepartments?.length === 0">
                            <TableCell colspan="4" class="h-24 text-center">
                                Aucun département trouvé.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                    <TableFooter v-if="departments.length > 0">
                        <TableRow>
                            <TableCell colspan="4">
                                Total: {{ departments.length }} département{{ departments.length > 1 ? 's' : '' }}
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </Card>

            <!-- Modal de confirmation de suppression -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Êtes-vous sûr de vouloir supprimer ce département?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Cette action ne peut pas être annulée. Le département "{{ departmentToDelete?.name }}" sera
                            définitivement supprimé.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">Annuler</Button>
                        <Button
                            variant="destructive"
                            @click="deleteDepartment"
                            :disabled="isDeleting"
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
