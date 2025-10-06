<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Formulaires" />

        <div class="p-3 sm:p-4">
            <!-- Header -->
            <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3 sm:mb-4">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Formulaires</h2>
                <div class="flex flex-col xs:flex-row items-stretch xs:items-center gap-2 w-full sm:w-auto">
                    <Input
                        v-model="filters.search"
                        placeholder="Rechercher un formulaire..."
                        class="w-full xs:w-[260px]"
                    >
                        <template #leading>
                            <SearchIcon class="h-4 w-4 text-muted-foreground" />
                        </template>
                    </Input>
                    <Button @click="addForm" class="w-full xs:w-auto">
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
                                <TableHead class="text-xs sm:text-sm">Nom du formulaire</TableHead>
                                <TableHead class="hidden sm:table-cell text-xs sm:text-sm">Description</TableHead>
                                <TableHead class="w-[80px] sm:w-[120px] text-right text-xs sm:text-sm">Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="form in currentRows" :key="form.id">
                                <TableCell class="font-medium text-xs sm:text-sm">
                                    <div class="flex flex-col">
                                        <span>{{ form.name }}</span>
                                        <span class="sm:hidden text-xs text-muted-foreground line-clamp-2">{{ form.description }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="hidden sm:table-cell text-xs sm:text-sm">{{ form.description }}</TableCell>
                                <TableCell class="text-right">
                                <div class="flex justify-end gap-1 sm:gap-2">
                                    <Button variant="ghost" size="icon" @click="editForm(form.id)" class="h-8 w-8 sm:h-10 sm:w-10">
                                        <PencilIcon class="h-3 w-3 sm:h-4 sm:w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(form)" class="h-8 w-8 sm:h-10 sm:w-10">
                                        <TrashIcon class="h-3 w-3 sm:h-4 sm:w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="currentRows.length === 0">
                            <TableCell colspan="3" class="h-20 sm:h-24 text-center text-xs sm:text-sm">Pas de formulaire trouvé.</TableCell>
                        </TableRow>
                    </TableBody>

                    <TableFooter v-if="totalCount > 0">
                        <TableRow>
                            <TableCell colspan="3" class="text-xs sm:text-sm">
                                Total: {{ totalCount }} formulaire{{ totalCount > 1 ? 's' : '' }}
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
                </div>
            </Card>

            <!-- Pagination (affichée uniquement si pagination serveur) -->
            <div v-if="isPaginated && totalCount > 0" class="mt-3 sm:mt-4 flex flex-col xs:flex-row items-start xs:items-center justify-between gap-2">
                <div class="text-xs sm:text-sm text-muted-foreground">
                    Affichage de {{ meta.from }} à {{ meta.to }} sur {{ meta.total }} formulaires
                </div>
                <div class="flex gap-1 sm:gap-2 w-full xs:w-auto">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!meta.prev_page_url"
                        @click="goToPage(meta.current_page - 1)"
                        class="flex-1 xs:flex-initial text-xs sm:text-sm"
                    >
                        Précédent
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!meta.next_page_url"
                        @click="goToPage(meta.current_page + 1)"
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
