<template>
    <div class="space-y-4">
        <!-- Montant payé -->
        <div>
            <label class="block text-sm font-medium mb-1">Montant payé (€)</label>
            <input
                type="number"
                step="0.01"
                v-model.number="local.paidAmount"
                :class="`border border-border rounded p-2 w-full ${isDarkTheme ? 'bg-primary-foreground text-background' : 'bg-background text-foreground'}`"
                placeholder="0.00"
                min="0"
            />
        </div>

        <!-- Pourcentage (non éditable) -->
        <div>
            <label class="block text-sm font-medium mb-1">Pourcentage remboursé</label>
            <div class="text-sm">{{ local.percentage }}%</div>
        </div>

        <!-- Résultat -->
        <div>
            <label class="block text-sm font-medium mb-1">Montant remboursé estimé</label>
            <div class="font-semibold text-green-700">{{ reimbursedAmount.toFixed(2) }} €</div>
        </div>
    </div>
</template>

<script setup>
import { watch, reactive, computed } from 'vue';

const props = defineProps({
    modelValue: Object // { paidAmount: number, percentage: number }
});

const emit = defineEmits(['update:modelValue']);

// Local copy
const local = reactive({
    paidAmount: props.modelValue?.paidAmount ?? 0,
    percentage: props.modelValue?.percentage ?? 0
});

// Recalcule local si le parent modifie modelValue
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue) {
            local.paidAmount = newValue.paidAmount ?? 0;
            local.percentage = newValue.percentage ?? 0;
        }
    },
    { immediate: true, deep: true }
);

// Montant remboursé
const reimbursedAmount = computed(() => {
    return (local.paidAmount * local.percentage) / 100;
});

// Réémission des valeurs modifiées
watch(
    local,
    () => {
        emit('update:modelValue', {
            paidAmount: local.paidAmount,
            percentage: local.percentage,
            reimbursedAmount: reimbursedAmount.value
        });
    },
    { deep: true }
);
</script>
