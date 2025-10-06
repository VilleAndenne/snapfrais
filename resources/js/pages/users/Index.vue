<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Utilisateurs" />

        <div class="p-3 sm:p-4">
            <!-- Header -->
            <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3 sm:mb-4">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Utilisateurs</h2>
                <div class="flex flex-col xs:flex-row items-stretch xs:items-center gap-2 w-full sm:w-auto">
                    <Input
                        v-model="filters.search"
                        placeholder="Rechercher par nom ou email..."
                        class="w-full xs:w-[260px]"
                    >
                        <template #leading>
                            <SearchIcon class="h-4 w-4 text-muted-foreground" />
                        </template>
                    </Input>
                    <Button @click="addUser" class="w-full xs:w-auto">
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
                                <TableHead class="hidden sm:table-cell text-xs sm:text-sm">Email</TableHead>
                                <TableHead class="w-[80px] sm:w-[100px] text-right text-xs sm:text-sm">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="user in users.data" :key="user.id">
                                <TableCell class="font-medium text-xs sm:text-sm">
                                    <div class="flex flex-col">
                                        <span>{{ user.name }}</span>
                                        <span class="sm:hidden text-xs text-muted-foreground">{{ user.email }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="hidden sm:table-cell text-xs sm:text-sm">{{ user.email }}</TableCell>
                                <TableCell class="text-right">
                                <div class="flex justify-end gap-1 sm:gap-2">
                                    <Button variant="ghost" size="icon" @click="editUser(user.id)" class="h-8 w-8 sm:h-10 sm:w-10">
                                        <PencilIcon class="h-3 w-3 sm:h-4 sm:w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(user)" class="h-8 w-8 sm:h-10 sm:w-10">
                                        <TrashIcon class="h-3 w-3 sm:h-4 sm:w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="3" class="h-20 sm:h-24 text-center text-xs sm:text-sm">
                                Pas d'utilisateur trouvé.
                            </TableCell>
                        </TableRow>
                    </TableBody>

                    <TableFooter v-if="users.total > 0">
                        <TableRow>
                            <TableCell colspan="3" class="text-xs sm:text-sm">
                                Total: {{ users.total }} utilisateur{{ users.total > 1 ? 's' : '' }}
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
                </div>
            </Card>

            <!-- Pagination -->
            <div class="mt-3 sm:mt-4 flex flex-col xs:flex-row items-start xs:items-center justify-between gap-2" v-if="users.total > 0">
                <div class="text-xs sm:text-sm text-muted-foreground">
                    Affichage de {{ users.from }} à {{ users.to }} sur {{ users.total }} utilisateurs
                </div>
                <div class="flex gap-1 sm:gap-2 w-full xs:w-auto">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!users.prev_page_url"
                        @click="goToPage(users.current_page - 1)"
                        class="flex-1 xs:flex-initial text-xs sm:text-sm"
                    >
                        Précédent
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!users.next_page_url"
                        @click="goToPage(users.current_page + 1)"
                        class="flex-1 xs:flex-initial text-xs sm:text-sm"
                    >
                        Suivant
                    </Button>
                </div>
            </div>

            <!-- Modal de confirmation de suppression -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent class="max-w-[90vw] sm:max-w-md">
                    <AlertDialogHeader>
                        <AlertDialogTitle class="text-base sm:text-lg">Êtes-vous sûr de vouloir supprimer cet utilisateur ?</AlertDialogTitle>
                        <AlertDialogDescription class="text-xs sm:text-sm">
                            Cette action ne peut pas être annulée. L'utilisateur "{{ userToDelete?.name }}" sera définitivement supprimé.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="flex-col xs:flex-row gap-2">
                        <Button variant="outline" @click="showDeleteDialog = false" class="w-full xs:w-auto">Annuler</Button>
                        <Button variant="destructive" @click="deleteUser" :disabled="isDeleting" class="w-full xs:w-auto">
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
