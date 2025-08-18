<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Head title="Importer des utilisateurs" />

            <!-- En-tête avec titre -->
            <div class="flex items-center justify-between mb-6">
                <Heading title="Importer des utilisateurs" />
            </div>

            <!-- Instructions d'import -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 max-w-2xl">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Format du fichier requis</h3>
                <p class="text-sm text-blue-700 mb-2">
                    Votre fichier Excel/CSV doit contenir les colonnes suivantes :
                </p>
                <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                    <li><strong>first_name</strong> - Prénom de l'utilisateur</li>
                    <li><strong>last_name</strong> - Nom de famille de l'utilisateur</li>
                    <li><strong>email</strong> - Adresse email (unique)</li>
                    <li><strong>department</strong> - Nom du département</li>
                </ul>
            </div>

            <form @submit.prevent="importUsers" class="space-y-6 max-w-2xl">
                <!-- Champ de sélection de fichier -->
                <div class="space-y-2">
                    <Label for="file">Fichier Excel/CSV</Label>
                    <div class="flex items-center space-x-4">
                        <input
                            id="file"
                            type="file"
                            ref="fileInput"
                            @change="handleFileChange"
                            accept=".csv,.xlsx,.xls"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90"
                            required
                        />
                    </div>
                    <p v-if="form.errors.file" class="text-sm text-destructive">
                        {{ form.errors.file }}
                    </p>
                    <p v-if="selectedFileName" class="text-sm text-muted-foreground">
                        Fichier sélectionné : {{ selectedFileName }}
                    </p>
                </div>

                <!-- Zone de prévisualisation -->
                <div v-if="selectedFileName" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-800 mb-2">Fichier prêt à importer</h4>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">{{ selectedFileName }}</span>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex items-center gap-4 pt-4">
                    <Button
                        type="submit"
                        :disabled="form.processing || !selectedFileName"
                        :class="{ 'opacity-50': form.processing || !selectedFileName }"
                    >
                        <span v-if="form.processing">Import en cours...</span>
                        <span v-else>Importer les utilisateurs</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelImport"
                    >
                        Annuler
                    </Button>
                </div>
            </form>

            <!-- Message d'avertissement -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6 max-w-2xl">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Attention</h3>
                        <p class="text-sm text-yellow-700 mt-1">
                            Les utilisateurs importés recevront un email avec un lien pour définir leur mot de passe.
                            Assurez-vous que les adresses email sont correctes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
// Références réactives
const fileInput = ref(null);
const selectedFileName = ref('');

// Initialisation du formulaire
const form = useForm({
    file: null,
});

// Définition des breadcrumbs pour la navigation
const breadcrumbs = [
    {
        title: 'Utilisateurs',
        href: '/users'
    },
    {
        title: 'Importer des utilisateurs',
        href: '/users/import'
    }
];

// Fonction pour gérer la sélection de fichier
const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.file = file;
        selectedFileName.value = file.name;
        // Réinitialiser les erreurs précédentes
        form.clearErrors('file');
    } else {
        form.file = null;
        selectedFileName.value = '';
    }
};

// Fonction pour importer les utilisateurs
const importUsers = () => {
    if (!form.file) {
        return;
    }

    form.post(route('users.import'), {
        onSuccess: () => {
            // Réinitialiser le formulaire après succès
            form.reset();
            selectedFileName.value = '';
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        onError: (errors) => {
            console.log('Erreurs d\'import:', errors);
        }
    });
};

// Fonction pour annuler l'import et retourner à la liste
const cancelImport = () => {
    router.visit('/users');
};
</script>
