<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Formulaires" />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Formulaires</h2>

                <div class="flex flex-col xs:flex-row gap-2 w-full sm:w-auto">
                    <button @click="addForm" class="px-3 sm:px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md flex items-center justify-center gap-2 text-sm">
                        <PlusIcon class="h-4 w-4" />
                        Ajouter
                    </button>

                    <div class="relative w-full">
                        <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <input v-model="filters.search" type="text" placeholder="Rechercher un formulaire..." class="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm" />
                        <button v-if="filters.search" @click="filters.search = ''" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <Badge variant="outline" class="px-2 sm:px-3 py-0.5 sm:py-1 text-xs">
                    {{ totalCount }} formulaire{{ totalCount > 1 ? 's' : '' }}
                </Badge>
            </div>

            <!-- Vue Mobile (cartes) - visible uniquement sur mobile -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="form in currentRows"
                    :key="form.id"
                    class="border rounded-lg p-4 bg-card space-y-3"
                >
                    <div class="flex items-start gap-2">
                        <FileText class="h-5 w-5 flex-shrink-0 mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm">{{ form.name }}</h3>
                            <p class="text-xs text-muted-foreground line-clamp-2">{{ form.description }}</p>
                        </div>
                    </div>

                    <div class="pt-2 border-t flex gap-2">
                        <button @click="editForm(form.id)" class="flex-1 px-3 py-2 border rounded-md text-sm hover:bg-muted transition-colors flex items-center justify-center gap-1">
                            <PencilIcon class="h-4 w-4" />
                            Modifier
                        </button>
                        <button @click="confirmDelete(form)" class="px-3 py-2 border rounded-md text-sm hover:bg-muted transition-colors text-destructive">
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-if="currentRows.length === 0" class="text-center p-8 text-muted-foreground border rounded-lg">
                    <FileText class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-sm">Pas de formulaire trouvé.</p>
                </div>
            </div>

            <!-- Vue Desktop (tableau) - visible sur tablette et + -->
            <div class="hidden md:block overflow-x-auto border rounded-xl">
                <table class="min-w-full divide-y">
                    <thead class="bg-muted">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Nom du formulaire</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Description</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="form in currentRows" :key="form.id" class="hover:bg-muted/50 transition-colors">
                        <td class="px-4 lg:px-6 py-4">
                            <div class="flex items-center gap-2">
                                <FileText class="h-5 w-5 flex-shrink-0" />
                                <span class="text-sm font-medium">{{ form.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 lg:px-6 py-4 text-sm">{{ form.description }}</td>
                        <td class="px-4 lg:px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button @click="editForm(form.id)" class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors flex items-center gap-1">
                                    <PencilIcon class="h-4 w-4" />
                                    Modifier
                                </button>
                                <button @click="confirmDelete(form)" class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors text-destructive">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div v-if="currentRows.length === 0" class="text-center p-8 text-muted-foreground">
                    <FileText class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-sm">Pas de formulaire trouvé.</p>
                </div>
            </div>

            <!-- Pagination (affichée uniquement si pagination serveur) -->
            <div v-if="isPaginated && totalCount > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
                <div class="text-sm text-muted-foreground order-2 sm:order-1">
                    Affichage de {{ meta.from }} à {{ meta.to }} sur {{ meta.total }} formulaires
                </div>
                <nav class="flex items-center gap-1 order-1 sm:order-2">
                    <button
                        :disabled="!meta.prev_page_url"
                        @click="goToPage(meta.current_page - 1)"
                        class="px-3 py-2 text-sm rounded-md transition-colors flex items-center gap-1"
                        :class="meta.prev_page_url ? 'hover:bg-muted' : 'opacity-50 cursor-not-allowed'"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Précédent
                    </button>
                    <button
                        :disabled="!meta.next_page_url"
                        @click="goToPage(meta.current_page + 1)"
                        class="px-3 py-2 text-sm rounded-md transition-colors flex items-center gap-1"
                        :class="meta.next_page_url ? 'hover:bg-muted' : 'opacity-50 cursor-not-allowed'"
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
                        <AlertDialogTitle class="text-base sm:text-lg">Supprimer ce formulaire ?</AlertDialogTitle>
                        <AlertDialogDescription class="text-xs sm:text-sm">
                            Cette action est irréversible. Le formulaire "{{ formToDelete?.name }}" sera définitivement supprimé.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="flex-col xs:flex-row gap-2">
                        <Button variant="outline" @click="showDeleteDialog = false" class="w-full xs:w-auto">Annuler</Button>
                        <Button variant="destructive" @click="deleteForm" :disabled="isDeleting" class="w-full xs:w-auto">
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
import { PlusIcon, SearchIcon, PencilIcon, TrashIcon, LoaderIcon, X, FileText, ChevronLeft, ChevronRight } from 'lucide-vue-next'

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

// Utilise la prop Inertia. Supporte à la fois tableau simple et pagination Laravel
const formsProp = computed(() => page.props.forms)

// Détecte si la charge est paginée (Laravel paginator)
const isPaginated = computed(() => !!formsProp.value && typeof formsProp.value === 'object' && Array.isArray(formsProp.value.data))

// Normalisation des lignes courantes et des métadonnées
const currentRows = computed(() => isPaginated.value ? formsProp.value.data : (formsProp.value || []))
const meta = computed(() => isPaginated.value ? {
    from: formsProp.value.from,
    to: formsProp.value.to,
    total: formsProp.value.total,
    current_page: formsProp.value.current_page,
    next_page_url: formsProp.value.next_page_url,
    prev_page_url: formsProp.value.prev_page_url
} : { from: 1, to: currentRows.value.length, total: currentRows.value.length, current_page: 1 })

const totalCount = computed(() => isPaginated.value ? formsProp.value.total : currentRows.value.length)

const filters = ref({ search: page.props.filters?.search ?? '' })

const breadcrumbs = [
    { title: 'Formulaires', href: route('forms.index') }
]

// Navigation
const addForm = () => router.visit(route('forms.create'))
const editForm = (id) => router.visit(route('forms.edit', id))

// Petit utilitaire debounce (évite dépendance externe)
function debounce (fn, delay = 300) {
    let t
    return (...args) => {
        clearTimeout(t)
        t = setTimeout(() => fn(...args), delay)
    }
}

const runSearch = debounce((val) => {
    router.get(route('forms.index'), { search: val, page: 1 }, {
        preserveState: true,
        replace: true
    })
}, 300)

// Recherche auto à la frappe + reset page -> 1
watch(() => filters.value.search, (val) => runSearch(val))

const goToPage = (targetPage) => {
    router.get(route('forms.index'), { search: filters.value.search, page: targetPage }, {
        preserveState: true,
        replace: true
    })
}

// Suppression avec AlertDialog
const showDeleteDialog = ref(false)
const formToDelete = ref(null)
const isDeleting = ref(false)

const confirmDelete = (form) => {
    formToDelete.value = form
    showDeleteDialog.value = true
}

const deleteForm = () => {
    if (!formToDelete.value) return
    isDeleting.value = true
    router.delete(route('forms.destroy', formToDelete.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false
            formToDelete.value = null
        },
        onFinish: () => {
            isDeleting.value = false
        }
    })
}
</script>
