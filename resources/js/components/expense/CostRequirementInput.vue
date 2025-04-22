<template>
    <div class="space-y-4">
        <div v-for="(req, index) in requirements" :key="index" class="space-y-2">
            <Label>{{ req.name }}</Label>

            <div v-if="req.type === 'text'">
                <Input v-model="formData[req.name]" type="text" />
            </div>

            <div v-else-if="req.type === 'file'">
                <div v-if="existingFiles[req.name]" class="mb-2">
                    <p class="text-sm text-muted-foreground mb-1">Fichier actuel :</p>
                    <div class="flex items-center gap-2">
                        <a :href="'/storage/' + existingFiles[req.name]" 
                           target="_blank" 
                           class="text-sm text-primary hover:underline">
                            Voir le fichier
                        </a>
                    </div>
                </div>
                <Input v-if="!existingFiles[req.name] || formData[req.name]" 
                       type="file"
                       @change="onFileChange($event, req.name)" />
                <p v-if="formData[req.name]" class="text-sm text-muted-foreground">
                    Nouveau fichier sélectionné : {{ formData[req.name].name }}
                </p>
            </div>

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
import { Trash2Icon } from 'lucide-vue-next';

const props = defineProps({
    requirements: {
        type: Array,
        default: () => []
    },
    modelValue: {
        type: Object,
        default: () => ({})
    },
    existingFiles: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['update:modelValue']);

const formData = reactive({ ...props.modelValue });

watch(
    formData,
    (val) => {
        emit('update:modelValue', { ...val });
    },
    { deep: true }
);

function onFileChange(event, key) {
    const file = event.target.files[0];
    if (file) {
        formData[key] = file;
        emit('update:modelValue', { ...formData });
    }
}

function removeExistingFile(key) {
    formData[key] = null;
    emit('update:modelValue', { ...formData });
}
</script>

<style scoped></style>
