<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

            <Head title="Formulaires" />

            <!-- En-tête avec titre et bouton d'ajout -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-semibold tracking-tight">Formulaires</h2>
                <Button @click="addForm" class="flex items-center">
                    <PlusIcon class="mr-2 h-4 w-4" />
                    Ajouter un formulaire
                </Button>
            </div>

            <div class="w-full">
                <Table>
                    <TableCaption>Une liste de vos formulaires.</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nom du formulaire</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="form in forms" :key="form.id">
                            <TableCell class="font-medium">{{ form.name }}</TableCell>
                            <TableCell>{{ form.description }}</TableCell>
                            <TableCell class="text-right space-x-2">
                                <Link :href="'/forms/' + form.id + '/edit'">
                                    <Button variant="outline" size="sm">
                                        Modifier
                                    </Button>
                                </Link>
                                <Button @click="deleteForm(form.id)" variant="destructive" size="sm">
                                    Supprimer
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="forms.length === 0">
                            <TableCell colspan="3" class="text-center">Pas de formulaire trouvé.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
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
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { usePage } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';

const page = usePage();

// Sample data - in a real app, this would come from an API or store
const forms = ref(page.props.forms);

const breadcrumbs = [
    {
        title: 'Formulaires',
        href: '/forms'
    }
];


// Function to handle add action - redirects to the create form page
const addForm = () => {
    router.visit('/forms/create');
};

function deleteForm(id) {
    router.delete(route('forms.destroy', id), {
        onSuccess: () => {
            forms.value = forms.value.filter(form => form.id !== id);
        }
    })
}
</script>
