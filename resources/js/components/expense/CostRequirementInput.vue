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
                <Input
                    v-if="!existingFiles[req.name] || localData[req.name]"
                    :key="`file-${req.name}-${fileKeys[req.name] || 0}`"
                :id="`req-${req.name}`"
                type="file"
                :accept="req.accept || undefined"
                @change="onFileChange($event, req.name)"
                :aria-invalid="submitted && isEmpty(req)"
                :class="{ 'border-red-500': submitted && isEmpty(req) }"
                />
                <p v-if="submitted && isEmpty(req)" class="text-sm text-red-600">Ce fichier est requis.</p>
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
    modelValue: { type: Object, default: () => ({}) },
    existingFiles: { type: Object, default: () => ({}) },
    storageBaseUrl: { type: String, default: '/storage' },
    submitted: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue']);

const localData = reactive({ ...props.modelValue });
const fileKeys = reactive({}); // ✅ compteur par requirement pour forcer le recreate de l'input

let isSyncing = false;

watch(localData, (val) => {
    if (isSyncing) return;
    const raw = toRaw(val);
    emit('update:modelValue', { ...raw });
}, { deep: true });

watch(() => props.modelValue, (val) => {
    isSyncing = true;
    Object.keys(localData).forEach((k) => delete localData[k]);
    Object.assign(localData, val || {});
    nextTick(() => { isSyncing = false; });
}, { deep: true });

function onFileChange(event, key) {
    const file = event.target.files?.[0];
    localData[key] = file || null;
}

function removeExistingFile(key) {
    if (props.existingFiles[key]) {
        localData[key] = null;
        // Optionnel: si tu veux aussi recréer l'input après suppression
        fileKeys[key] = (fileKeys[key] || 0) + 1;
    }
}

function isEmpty(req) {
    const val = localData[req.name];
    if (req.type === 'text') {
        return !val || String(val).trim() === '';
    }
    if (req.type === 'file') {
        const hasNew = val instanceof File; // ✅ important
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
