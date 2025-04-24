<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

            <Head title="Utilisateurs" />

            <!-- En-tête avec titre et bouton d'ajout -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-semibold tracking-tight">Utilisateurs</h2>
                <Button @click="addUser" class="flex items-center">
                    <PlusIcon class="mr-2 h-4 w-4" />
                    Ajouter un utilisateur
                </Button>
            </div>

            <!-- Barre de recherche -->
            <div class="mb-4">
                <form @submit.prevent="search">
                    <div class="flex gap-2">
                        <Input
                            v-model="filters.search"
                            placeholder="Rechercher par nom ou email..."
                            class="max-w-sm"
                        />
                        <Button type="submit">Rechercher</Button>
                    </div>
                </form>
            </div>

            <div class="w-full">
                <Table>
                    <TableCaption>Une liste des utilisateurs.</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link :href="'/users/' + user.id + '/edit'">
                                        <Button variant="outline" size="sm">
                                            Modifier
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="confirmDelete(user)"
                                    >
                                        Supprimer
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="3" class="text-center">Pas d'utilisateur trouvé.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between">
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
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { usePage } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';

const page = usePage();

// Récupération des données depuis les props
const users = ref(page.props.users);
const filters = ref(page.props.filters || { search: '' });

const breadcrumbs = [
    {
        title: 'Utilisateurs',
        href: '/users'
    }
];

// Fonction pour ajouter un utilisateur
const addUser = () => {
    router.visit('/users/create');
};

// Fonction pour la recherche
const search = () => {
    router.get('/users', filters.value, {
        preserveState: true,
        replace: true
    });
};

// Fonction pour la pagination
const goToPage = (page) => {
    router.get('/users', {
        ...filters.value,
        page
    }, {
        preserveState: true,
        replace: true
    });
};

// Fonction pour confirmer la suppression
const confirmDelete = (user) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${user.name} ?`)) {
        router.delete(`/users/${user.id}`);
    }
};
</script>
