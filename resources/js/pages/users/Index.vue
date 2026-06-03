<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Utilisateurs" />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Utilisateurs</h2>

                <div class="flex flex-col xs:flex-row gap-2 w-full sm:w-auto">
                    <button @click="addUser" class="px-3 sm:px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md flex items-center justify-center gap-2 text-sm">
                        <PlusIcon class="h-4 w-4" />
                        Ajouter
                    </button>

                    <div class="relative w-full">
                        <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <input v-model="filters.search" type="text" placeholder="Rechercher par nom ou email..." class="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm" />
                        <button v-if="filters.search" @click="filters.search = ''" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <Badge variant="outline" class="px-2 sm:px-3 py-0.5 sm:py-1 text-xs">
                    {{ users.total }} utilisateur{{ users.total > 1 ? 's' : '' }}
                </Badge>
            </div>

            <!-- Vue Mobile (cartes) - visible uniquement sur mobile -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="user in users.data"
                    :key="user.id"
                    class="border rounded-lg p-4 bg-card space-y-3"
                >
                    <div class="flex items-start gap-2">
                        <UserIcon class="h-5 w-5 flex-shrink-0 mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm">{{ user.name }}</h3>
                            <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                        </div>
                    </div>

                    <div class="pt-2 border-t flex gap-2">
                        <button @click="showUser(user.id)" class="flex-1 px-3 py-2 border rounded-md text-sm hover:bg-muted transition-colors flex items-center justify-center gap-1">
                            <EyeIcon class="h-4 w-4" />
                            Voir
                        </button>
                        <button @click="editUser(user.id)" class="px-3 py-2 border rounded-md text-sm hover:bg-muted transition-colors">
                            <PencilIcon class="h-4 w-4" />
                        </button>
                        <button
                            v-if="canImpersonate && !user.super_admin"
                            @click="impersonateUser(user.id)"
                            class="px-3 py-2 border rounded-md text-sm hover:bg-muted transition-colors text-primary"
                            title="Impersonner cet utilisateur"
                        >
                            <UserIcon class="h-4 w-4" />
                        </button>
                        <button @click="confirmDelete(user)" class="px-3 py-2 border rounded-md text-sm hover:bg-muted transition-colors text-destructive">
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-if="users.data.length === 0" class="text-center p-8 text-muted-foreground border rounded-lg">
                    <UserIcon class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-sm">Pas d'utilisateur trouvé.</p>
                </div>
            </div>

            <!-- Vue Desktop (tableau) - visible sur tablette et + -->
            <div class="hidden md:block overflow-x-auto border rounded-xl">
                <table class="min-w-full divide-y">
                    <thead class="bg-muted">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Nom</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Email</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/50 transition-colors">
                        <td class="px-4 lg:px-6 py-4">
                            <div class="flex items-center gap-2">
                                <UserIcon class="h-5 w-5 flex-shrink-0" />
                                <span class="text-sm font-medium">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 lg:px-6 py-4 text-sm">{{ user.email }}</td>
                        <td class="px-4 lg:px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button
                                    v-if="canImpersonate && !user.super_admin"
                                    @click="impersonateUser(user.id)"
                                    class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors text-primary"
                                    title="Impersonner cet utilisateur"
                                >
                                    <UserIcon class="h-4 w-4" />
                                </button>
                                <button @click="showUser(user.id)" class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors flex items-center gap-1" title="Voir cet utilisateur">
                                    <EyeIcon class="h-4 w-4" />
                                    Voir
                                </button>
                                <button @click="editUser(user.id)" class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors">
                                    <PencilIcon class="h-4 w-4" />
                                </button>
                                <button @click="confirmDelete(user)" class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors text-destructive">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div v-if="users.data.length === 0" class="text-center p-8 text-muted-foreground">
                    <UserIcon class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-sm">Pas d'utilisateur trouvé.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4" v-if="users.total > 0">
                <div class="text-sm text-muted-foreground order-2 sm:order-1">
                    Affichage de {{ users.from }} à {{ users.to }} sur {{ users.total }} utilisateurs
                </div>
                <nav class="flex items-center gap-1 order-1 sm:order-2">
                    <button
                        :disabled="!users.prev_page_url"
                        @click="goToPage(users.current_page - 1)"
                        class="px-3 py-2 text-sm rounded-md transition-colors flex items-center gap-1"
                        :class="users.prev_page_url ? 'hover:bg-muted' : 'opacity-50 cursor-not-allowed'"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Précédent
                    </button>
                    <button
                        :disabled="!users.next_page_url"
                        @click="goToPage(users.current_page + 1)"
                        class="px-3 py-2 text-sm rounded-md transition-colors flex items-center gap-1"
                        :class="users.next_page_url ? 'hover:bg-muted' : 'opacity-50 cursor-not-allowed'"
                    >
                        Suivant
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </nav>
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
import { PlusIcon, SearchIcon, PencilIcon, TrashIcon, LoaderIcon, UserIcon, EyeIcon, X, ChevronLeft, ChevronRight } from 'lucide-vue-next'

// shadcn/ui
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
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
const canImpersonate = computed(() => page.props.canImpersonate)

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

const showUser = (id) => {
    router.visit(route('users.show', id))
}

// Petit utilitaire debounce (évite dépendance externe)
function debounce (fn, delay = 300) {
    let t
    return (...args) => {
        clearTimeout(t)
        t = setTimeout(() => fn(...args), delay)
    }
}

// Recherche automatique à la frappe (debounce + rechargement partiel)
const runSearch = debounce((val) => {
    router.get(route('users.index'), { search: val, page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users']
    })
}, 300)

watch(() => filters.value.search, (val) => runSearch(val))

const goToPage = (targetPage) => {
    router.get(route('users.index'), { search: filters.value.search, page: targetPage }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users']
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

const impersonateUser = (userId) => {
    router.post(route('impersonate.start', userId))
}
</script>
