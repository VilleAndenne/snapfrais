<template>
    <div class="space-y-4">
        <div
            v-for="(req, index) in requirements"
            :key="index"
            class="space-y-2"
        >
            <Label :for="`req-${req.name}`">{{ req.name }}</Label>

            <!-- TEXT -->
            <div v-if="req.type === 'text'">
                <Input
                    :id="`req-${req.name}`"
                    v-model="localData[req.name]"
                    type="text"
                    :placeholder="req.placeholder || ''"
                    :aria-invalid="isEmpty(req)"
                    :class="{ 'border-red-500': isEmpty(req) }"
                />
                <p v-if="isEmpty(req)" class="text-sm text-red-600">
                    Ce champ est requis.
                </p>
            </div>

            <!-- FILE -->
            <div v-else-if="req.type === 'file'">
                <!-- Fichier existant -->
                <div v-if="existingFiles[req.name] && !localData[req.name]" class="mb-2">
                    <p class="text-sm text-muted-foreground mb-1">Fichier actuel :</p>
                    <div class="flex items-center gap-2">
                        <a
                            :href="getFileUrl(existingFiles[req.name])"
                            target="_blank"
                            class="text-sm text-primary hover:underline"
                        >
                            Voir le fichier
                        </a>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="text-destructive"
                            @click="removeExistingFile(req.name)"
                        >
                            Remplacer / retirer
                        </Button>
                    </div>
                </div>

                <!-- Input file (toujours visible si pas de fichier existant, ou si on remplace) -->
                <Input
                    v-if="!existingFiles[req.name] || localData[req.name]"
                    :id="`req-${req.name}`"
                    type="file"
                    :accept="req.accept || undefined"
                    @change="onFileChange($event, req.name)"
                    :aria-invalid="isEmpty(req)"
                    :class="{ 'border-red-500': isEmpty(req) }"
                />

                <!-- Nom du nouveau fichier -->
                <p v-if="localData[req.name]" class="text-sm text-muted-foreground">
                    Nouveau fichier sélectionné : {{ localData[req.name]?.name }}
                </p>

                <!-- Erreur requise -->
                <p v-if="isEmpty(req)" class="text-sm text-red-600">
                    Ce fichier est requis.
                </p>
            </div>

            <!-- Type inconnu -->
            <div v-else class="text-sm text-destructive">
                Type de prérequis inconnu : {{ req.type }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const props = defineProps({
    requirements: {
        type: Array,
        default: () => [] // [{ name: 'Justif', type: 'file'|'text', placeholder?, accept? }]
    },
    modelValue: {
        type: Object,
        default: () => ({}) // { [req.name]: string | File }
    },
    existingFiles: {
        type: Object,
        default: () => ({}) // { [req.name]: 'path/relative/ou/complete' }
    },
    storageBaseUrl: {
        type: String,
        default: '/storage' // pour préfixer une path relative
    }
});

const emit = defineEmits(['update:modelValue']);

// copie réactive locale pour v-model
const localData = reactive({ ...props.modelValue });

// sync -> parent
watch(
    localData,
    (val) => {
        emit('update:modelValue', { ...val });
    },
    { deep: true }
);

// sync <- parent (si le parent remplace entièrement l'objet)
watch(
    () => props.modelValue,
    (val) => {
        Object.keys(localData).forEach((k) => delete localData[k]);
        Object.assign(localData, val || {});
    },
    { deep: true }
);

function onFileChange(event, key) {
    const file = event.target.files?.[0];
    if (file) {
        localData[key] = file;
        emit('update:modelValue', { ...localData });
    }
}

function removeExistingFile(key) {
    // Retire la référence au fichier existant (côté affichage)
    // et force l'utilisateur à sélectionner un nouveau fichier
    if (props.existingFiles[key]) {
        // On ne modifie pas props.existingFiles (readonly), on se contente de
        // mettre localData[key] à null => l'input file devient requis
        localData[key] = null;
        emit('update:modelValue', { ...localData });
    }
}

// Helper pour savoir si un requirement est vide
function isEmpty(req) {
    const val = localData[req.name];

    if (req.type === 'text') {
        return !val || String(val).trim() === '';
    }
    if (req.type === 'file') {
        // satisfait si un nouveau File est choisi OU s'il existe un fichier existant
        const hasNew = val instanceof File;
        const hasExisting = !!props.existingFiles[req.name];
        return !(hasNew || hasExisting);
    }
    return true; // type inconnu => considéré vide
}

// Génère l'URL affichable du fichier existant
function getFileUrl(path) {
    // si path est déjà absolu (http/https), on le retourne tel quel
    if (/^https?:\/\//i.test(path)) return path;
    // sinon on préfixe par storageBaseUrl (par ex. '/storage')
    return `${props.storageBaseUrl.replace(/\/$/, '')}/${String(path).replace(/^\//, '')}`;
}
</script>

<style scoped></style>
