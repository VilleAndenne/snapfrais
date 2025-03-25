<template>
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">

            <Head title="Forms" />

            <!-- En-tête avec titre et bouton d'ajout -->
            <div class="flex items-center justify-between mb-4">
                <Heading title="Forms" />
                <Button @click="addForm" class="flex items-center">
                    <PlusIcon class="mr-2 h-4 w-4" />
                    Add Form
                </Button>
            </div>

            <div class="w-full">
                <Table>
                    <TableCaption>A list of your forms.</TableCaption>
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
                            <TableCell class="text-right">
                                <Link :href="'/forms/' + form.id + '/edit'">
                                <Button variant="outline" size="sm">
                                    Edit
                                </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="forms.length === 0">
                            <TableCell colspan="3" class="text-center">No forms found.</TableCell>
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

const page = usePage()

// Sample data - in a real app, this would come from an API or store
const forms = ref(page.props.forms);


// Function to handle add action - redirects to the create form page
const addForm = () => {
    router.visit('/forms/create');
};
</script>
