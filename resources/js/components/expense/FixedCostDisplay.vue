<template>
    <div class="space-y-2">
        <Label>Montant fixe</Label>
        <Input v-model="localValue" disabled />
    </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const props = defineProps({
    modelValue: {
        default: () => ({}),
    },
});

const emit = defineEmits(["update:modelValue"]);

// Gestion de la valeur locale pour l'input
const localValue = ref(props.modelValue?.fixedAmount ?? 0);

// Met à jour la valeur locale quand le prop change
watch(
    () => props.modelValue,
    (newValue) => {
        localValue.value = newValue?.fixedAmount ?? 0;
    },
    { deep: true }
);

// Émettre la mise à jour lorsque la valeur change
watch(localValue, (newValue) => {
    emit("update:modelValue", { fixedAmount: newValue });
});
</script>

<style scoped></style>
