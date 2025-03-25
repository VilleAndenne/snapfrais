<template>
    <div class="space-y-2">
      <Label>Montant fixe</Label>
      <Input :value="formattedAmount" disabled />
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue';
  import { Input } from '@/components/ui/input';
  import { Label } from '@/components/ui/label';
  
  const props = defineProps({
    amount: {
      type: Number,
      required: true
    },
    currency: {
      type: String,
      default: '€'
    }
  });
  
  const formattedAmount = computed(() => `${props.amount.toFixed(2)} ${props.currency}`);
  </script>
  
  <style scoped>
  </style>