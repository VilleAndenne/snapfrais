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
                    :key="`${req.name}-${localData[req.name]}`"
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
import { reactive, watch, nextTick, toRaw } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const props = defineProps({
    requirements: { type: Array, default: () => [] },
    modelValue: { type: Object, default: () => ({}) },   // { [req.name]: string | File }
    existingFiles: { type: Object, default: () => ({}) },
    storageBaseUrl: { type: String, default: '/storage' },
    submitted: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue']);

// --- état local
const localData = reactive({ ...props.modelValue });

// --- garde pour éviter la boucle
let isSyncing = false;

// sync -> parent (seulement si ce n’est PAS une sync entrante)
watch(
    localData,
    (val) => {
        if (isSyncing) return;
        // retire les proxies (notamment pour les File), et clone à plat
        const raw = toRaw(val);
        emit('update:modelValue', { ...raw });
    },
    { deep: true }
);

// sync <- parent (et ne réémet pas)
watch(
    () => props.modelValue,
    (val) => {
        isSyncing = true;
        // Remplace proprement le contenu local
        Object.keys(localData).forEach((k) => delete localData[k]);
        Object.assign(localData, val || {});
        // Laisse le temps au flush des watchers avant de réautoriser l’emit
        nextTick(() => { isSyncing = false; });
    },
    { deep: true }
);

function onFileChange(event, key) {
    const file = event.target.files?.[0];
    if (file) {
        localData[key] = file; // File reste natif (non proxyfié)
    }
}

function removeExistingFile(key) {
    if (props.existingFiles[key]) {
        localData[key] = null;
    }
}

function isEmpty(req) {
    const val = localData[req.name];
    if (req.type === 'text') {
        return !val || String(val).trim() === '';
    }
    if (req.type === 'file') {
        const hasNew = val;
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
