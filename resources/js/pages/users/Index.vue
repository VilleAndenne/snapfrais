<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Utilisateurs" />

        <div class="p-4">
            <!-- Header -->
            <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
                <h2 class="text-2xl font-semibold tracking-tight">Utilisateurs</h2>
                <div class="flex items-center gap-2">
                    <Input
                        v-model="filters.search"
                        placeholder="Rechercher par nom ou email..."
                        class="w-[260px]"
                    >
                        <template #leading>
                            <SearchIcon class="h-4 w-4 text-muted-foreground" />
                        </template>
                    </Input>
                    <Button @click="addUser">
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
                            <TableHead>Email</TableHead>
                            <TableHead class="w-[100px] text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" @click="editUser(user.id)">
                                        <PencilIcon class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(user)">
                                        <TrashIcon class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="3" class="h-24 text-center">
                                Pas d'utilisateur trouvé.
                            </TableCell>
                        </TableRow>
                    </TableBody>

                    <TableFooter v-if="users.total > 0">
                        <TableRow>
                            <TableCell colspan="3">
                                Total: {{ users.total }} utilisateur{{ users.total > 1 ? 's' : '' }}
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </Card>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between" v-if="users.total > 0">
                <div class="text-sm text-muted-foreground">
                    Affichage de {{ users.from }} à {{ users.to }} sur {{ users.total }} utilisateurs
                </div>
                <div class="flex gap-1">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!users.prev_page_url"
                        @click="goToPage(users.current_page - 1)"
                    >
                        Précédent
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!users.next_page_url"
                        @click="goToPage(users.current_page + 1)"
                    >
                        Suivant
                    </Button>
                </div>
            </div>

            <!-- Modal de confirmation de suppression -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Êtes-vous sûr de vouloir supprimer cet utilisateur ?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Cette action ne peut pas être annulée. L'utilisateur "{{ userToDelete?.name }}" sera définitivement supprimé.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">Annuler</Button>
                        <Button variant="destructive" @click="deleteUser" :disabled="isDeleting">
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
import { ref, computed, watch } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { PlusIcon, SearchIcon, PencilIcon, TrashIcon, LoaderIcon } from 'lucide-vue-next'

// shadcn/ui
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card } from '@/components/ui/card'
import {
    Table,
    TableHeader,
    TableBody,
    TableFooter,
    TableHead,
    TableRow,
    TableCell
} from '@/components/ui/table'
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter
} from '@/components/ui/alert-dialog'

import AppLayout from '@/layouts/AppLayout.vue'

const page = usePage()

// Props via Inertia (pagination-friendly)
const users = computed(() => page.props.users)
const initialSearch = page.props.filters?.search ?? ''

// UI State
const filters = ref({ search: initialSearch })
const showDeleteDialog = ref(false)
const userToDelete = ref(null)
const isDeleting = ref(false)

const breadcrumbs = [
    { title: 'Utilisateurs', href: route('users.index') }
]

// Actions
const addUser = () => {
    router.visit(route('users.create'))
}

const editUser = (id) => {
    router.visit(route('users.edit', id))
}

// Recherche automatique à la frappe
watch(() => filters.value.search, (val) => {
    router.get(route('users.index'), { search: val, page: 1 }, {
        preserveState: true,
        replace: true
    })
})
const goToPage = (targetPage) => {
    router.get(route('users.index'), { search: filters.value.search, page: targetPage }, {
        preserveState: true,
        replace: true
    })
}

const confirmDelete = (user) => {
    userToDelete.value = user
    showDeleteDialog.value = true
}

const deleteUser = () => {
    if (!userToDelete.value) return
    isDeleting.value = true
    router.delete(route('users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false
            userToDelete.value = null
        },
        onFinish: () => {
            isDeleting.value = false
        }
    })
}
</script>
