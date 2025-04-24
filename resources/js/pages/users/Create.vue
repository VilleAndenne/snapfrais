<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Head title="Créer un utilisateur" />

            <!-- En-tête avec titre -->
            <div class="flex items-center justify-between mb-6">
                <Heading title="Créer un nouvel utilisateur" />
            </div>

            <form @submit.prevent="createUser" class="space-y-6 max-w-2xl">
                <!-- Champ Nom -->
                <div class="space-y-2">
                    <Label for="name">Nom</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Nom de l'utilisateur"
                        :error="form.errors.name"
                        required
                    />
                    <p v-if="form.errors.name" class="text-sm text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Champ Email -->
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        placeholder="email@exemple.com"
                        :error="form.errors.email"
                        required
                    />
                    <p v-if="form.errors.email" class="text-sm text-destructive">
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Champ Mot de passe -->
                <div class="space-y-2">
                    <Label for="password">Mot de passe</Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        placeholder="Mot de passe"
                        :error="form.errors.password"
                        required
                    />
                    <p v-if="form.errors.password" class="text-sm text-destructive">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Champ Confirmation mot de passe -->
                <div class="space-y-2">
                    <Label for="password_confirmation">Confirmer le mot de passe</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        placeholder="Confirmer le mot de passe"
                        required
                    />
                </div>

                <div class="flex items-center space-x-2">
                    <input
                        id="is-admin"
                        type="checkbox"
                        v-model="form.is_admin"
                        class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                    />
                    <label for="is-admin" class="text-sm font-medium text-foreground">
                        Administrateur de l'application
                    </label>
                </div>

                <!-- Boutons d'action -->
                <div class="flex items-center gap-4 pt-4">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        :class="{ 'opacity-50': form.processing }"
                    >
                        <span v-if="form.processing">Création en cours...</span>
                        <span v-else>Créer l'utilisateur</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelCreate"
                    >
                        Annuler
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';

// Initialisation du formulaire avec des champs vides
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    is_admin: false,
});

// Définition des breadcrumbs pour la navigation
const breadcrumbs = [
    {
        title: 'Utilisateurs',
        href: '/users'
    },
    {
        title: 'Créer un utilisateur',
        href: '/users/create'
    }
];

// Fonction pour créer un nouvel utilisateur
const createUser = () => {
    form.post('/users', {
        onSuccess: () => {
            // Redirection vers la liste des utilisateurs avec un message de succès
            // Le message de succès sera géré par le contrôleur via une session flash
        },
    });
};

// Fonction pour annuler la création et retourner à la liste
const cancelCreate = () => {
    router.visit('/users');
};
</script>
