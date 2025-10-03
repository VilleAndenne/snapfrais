<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Créer une patch note" />

        <div class="container mx-auto space-y-6 p-4">
            <h1 class="text-2xl font-semibold text-foreground">Créer une patch note</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Titre -->
                <div class="space-y-2">
                    <Label for="title">Titre</Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        type="text"
                        placeholder="Titre de la patch note"
                        required
                    />
                    <span v-if="form.errors.title" class="text-sm text-destructive">
                        {{ form.errors.title }}
                    </span>
                </div>

                <!-- Contenu -->
                <div class="space-y-2">
                    <Label for="content">Contenu (Markdown)</Label>
                    <MarkdownEditor
                        v-model="form.content"
                        placeholder="Décrivez les nouveautés, corrections, améliorations... Markdown supporté !"
                    />
                    <span v-if="form.errors.content" class="text-sm text-destructive">
                        {{ form.errors.content }}
                    </span>
                </div>

                <!-- Boutons -->
                <div class="flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit('/patch-notes')">
                        Annuler
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2Icon v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Création...' : 'Créer la patch note' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MarkdownEditor from '@/components/MarkdownEditor.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Loader2Icon } from 'lucide-vue-next';

const form = useForm({
    title: '',
    content: '',
});

const submit = () => {
    form.post('/patch-notes', {
        onSuccess: () => {
            form.reset();
        },
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Patch Notes', href: '/patch-notes' },
    { title: 'Créer une patch note' },
];
</script>