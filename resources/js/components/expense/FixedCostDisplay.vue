<template>
  <div class="space-y-2">
    <Label>Montant fixe</Label>
    <Input :value="modelValue + ' €'" disabled />
  </div>
</template>

<script setup>
import { computed } from "vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  currency: {
    type: String,
    default: "€",
  },
});
</script>

<style scoped></style>
