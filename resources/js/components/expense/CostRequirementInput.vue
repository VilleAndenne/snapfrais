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
                <!-- Afficher le fichier existant -->
                <div v-if="getExistingFile(req.name) && !isReplacingFile(req.name)" class="space-y-2">
                    <div class="p-3 border border-green-200 rounded-lg bg-green-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium text-green-800">Un fichier est déjà uploadé</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="getExistingFile(req.name)" target="_blank" class="text-primary hover:text-primary/80 text-sm font-medium underline">
                                Voir le fichier
                            </a>
                            <Button variant="outline" size="sm" @click="startReplacingFile(req.name)" type="button" class="text-orange-600 border-orange-300 hover:bg-orange-50">
                                Remplacer
                            </Button>
                            <Button variant="outline" size="sm" @click="deleteExistingFile(req.name)" type="button" class="text-red-600 border-red-300 hover:bg-red-50">
                                Supprimer
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Input pour nouveau fichier -->
                <div v-if="!getExistingFile(req.name) || isReplacingFile(req.name)">
                    <Input
                        :key="`file-${req.name}-${fileKeys[req.name] || 0}`"
                        :id="`req-${req.name}`"
                        type="file"
                        :accept="req.accept || undefined"
                        @change="onFileChange($event, req.name)"
                        :aria-invalid="submitted && isEmpty(req)"
                        :class="{ 'border-red-500': submitted && isEmpty(req) }"
                    />
                    <Button
                        v-if="isReplacingFile(req.name)"
                        variant="ghost"
                        size="sm"
                        @click="cancelReplaceFile(req.name)"
                        type="button"
                        class="mt-2"
                    >
                        Annuler
                    </Button>
                </div>
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
import { reactive, watch, nextTick, toRaw, onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

const props = defineProps({
    requirements: { type: Array, default: () => [] },
    modelValue: { type: Object, default: () => ({}) },
    existingData: { type: Object, default: () => ({}) },
    storageBaseUrl: { type: String, default: '/storage' },
    submitted: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue']);

// Initialiser localData avec les données existantes
const initLocalData = () => {
    const initial = {};

    // Parcourir existingData pour initialiser les valeurs
    Object.entries(props.existingData).forEach(([key, value]) => {
        if (value?.value !== undefined) {
            // Valeur texte
            initial[key] = value.value;
        }
        // Les fichiers ne sont pas ajoutés à localData, ils sont gérés via existingData
    });

    // Fusionner avec modelValue si présent
    return { ...initial, ...props.modelValue };
};

const localData = reactive(initLocalData());
const fileKeys = reactive({});
const replacingFiles = reactive({}); // Track which files are being replaced

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

function startReplacingFile(key) {
    replacingFiles[key] = true;
    fileKeys[key] = (fileKeys[key] || 0) + 1;
}

function cancelReplaceFile(key) {
    replacingFiles[key] = false;
    localData[key] = null;
    fileKeys[key] = (fileKeys[key] || 0) + 1;
}

function deleteExistingFile(key) {
    // Marquer le fichier comme supprimé en mettant une valeur null
    localData[key] = null;
    replacingFiles[key] = true;
    fileKeys[key] = (fileKeys[key] || 0) + 1;
}

function isReplacingFile(key) {
    return replacingFiles[key] === true;
}

function getExistingFile(key) {
    const data = props.existingData[key];
    return data?.file || null;
}

function isEmpty(req) {
    const val = localData[req.name];
    if (req.type === 'text') {
        return !val || String(val).trim() === '';
    }
    if (req.type === 'file') {
        const hasNew = val instanceof File;
        const hasExisting = !!getExistingFile(req.name) && !isReplacingFile(req.name);
        return !(hasNew || hasExisting);
    }
    return true;
}

function getFileUrl(path) {
    if (/^https?:\/\//i.test(path)) return path;
    return `${props.storageBaseUrl.replace(/\/$/, '')}/${String(path).replace(/^\//, '')}`;
}
</script>
