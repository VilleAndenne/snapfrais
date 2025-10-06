<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Formulaires" />

        <div class="p-4">
            <!-- Header -->
            <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
                <h2 class="text-2xl font-semibold tracking-tight">Formulaires</h2>
                <div class="flex items-center gap-2">
                    <Input
                        v-model="filters.search"
                        placeholder="Rechercher un formulaire..."
                        class="w-[260px]"
                    >
                        <template #leading>
                            <SearchIcon class="h-4 w-4 text-muted-foreground" />
                        </template>
                    </Input>
                    <Button @click="addForm">
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Ajouter
                    </Button>
                </div>
            </header>

            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom du formulaire</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead class="w-[120px] text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="form in currentRows" :key="form.id">
                            <TableCell class="font-medium">{{ form.name }}</TableCell>
                            <TableCell>{{ form.description }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" @click="editForm(form.id)">
                                        <PencilIcon class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="confirmDelete(form)">
                                        <TrashIcon class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="currentRows.length === 0">
                            <TableCell colspan="3" class="h-24 text-center">Pas de formulaire trouvé.</TableCell>
                        </TableRow>
                    </TableBody>

                    <TableFooter v-if="totalCount > 0">
                        <TableRow>
                            <TableCell colspan="3">
                                Total: {{ totalCount }} formulaire{{ totalCount > 1 ? 's' : '' }}
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </Card>

            <!-- Pagination (affichée uniquement si pagination serveur) -->
            <div v-if="isPaginated && totalCount > 0" class="mt-4 flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Affichage de {{ meta.from }} à {{ meta.to }} sur {{ meta.total }} formulaires
                </div>
                <div class="flex gap-1">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!meta.prev_page_url"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        Précédent
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!meta.next_page_url"
                        @click="goToPage(meta.current_page + 1)"
                    >
                        Suivant
                    </Button>
                </div>
            </div>

            <!-- Modal de confirmation de suppression -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Supprimer ce formulaire ?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Cette action est irréversible. Le formulaire "{{ formToDelete?.name }}" sera définitivement supprimé.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">Annuler</Button>
                        <Button variant="destructive" @click="deleteForm" :disabled="isDeleting">
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
