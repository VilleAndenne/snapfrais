<template>
    <div class="space-y-4">
      <div v-for="(req, index) in requirements" :key="index" class="space-y-2">
        <Label>{{ req.name }}</Label>
  
        <div v-if="req.type === 'text'">
          <Input v-model="formData[req.name]" type="text" />
        </div>
  
        <div v-else-if="req.type === 'file'">
          <Input type="file" @change="onFileChange($event, req.name)" />
          <p v-if="formData[req.name]" class="text-sm text-muted-foreground">Fichier sélectionné : {{ formData[req.name].name }}</p>
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
  
  const props = defineProps({
    requirements: {
      type: Array,
      default: () => []
    },
    modelValue: {
      type: Object,
      default: () => ({})
    }
  });
  const emit = defineEmits(['update:modelValue']);
  
  const formData = reactive({ ...props.modelValue });
  
  watch(formData, (val) => {
    emit('update:modelValue', { ...val });
  }, { deep: true });
  
  function onFileChange(event, key) {
    const file = event.target.files[0];
    formData[key] = file;
  }
  </script>
  
  <style scoped>
  </style>