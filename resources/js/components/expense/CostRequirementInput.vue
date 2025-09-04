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
                    :class="{ 'border-red-500': submitted && isEmpty(req) }"
                />
                <p v-if="submitted && isEmpty(req)" class="text-sm text-red-600">
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

                <!-- Input file -->
                <Input
                    v-if="!existingFiles[req.name] || localData[req.name]"
                    :id="`req-${req.name}`"
                    type="file"
                    :accept="req.accept || undefined"
                    @change="onFileChange($event, req.name)"
                    :aria-invalid="submitted && isEmpty(req)"
                    :class="{ 'border-red-500': submitted && isEmpty(req) }"
                />

                <!-- Message d’erreur -->
                <p v-if="submitted && isEmpty(req)" class="text-sm text-red-600">
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
        default: () => ({}) // { [req.name]: 'path/to/file.pdf' }
    },
    storageBaseUrl: {
        type: String,
        default: '/storage' // préfixe pour les fichiers relatifs
    },
    submitted: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const localData = reactive({ ...props.modelValue });

// sync -> parent
watch(
    localData,
    (val) => emit('update:modelValue', { ...val }),
    { deep: true }
);

// sync <- parent
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
    // Supprime la référence au fichier existant → l’input file devient obligatoire
    if (props.existingFiles[key]) {
        localData[key] = null;
        emit('update:modelValue', { ...localData });
    }
}

function isEmpty(req) {
    const val = localData[req.name];
    if (req.type === 'text') {
        return !val || String(val).trim() === '';
    }
    if (req.type === 'file') {
        const hasNew = val instanceof File;
        const hasExisting = !!props.existingFiles[req.name];
        return !(hasNew || hasExisting);
    }
    return true;
}

function getFileUrl(path) {
    if (/^https?:\/\//i.test(path)) return path;
    return `${props.storageBaseUrl.replace(/\/$/, '')}/${String(path).replace(/^\//, '')}`;
}
</script>

<style scoped></style>
