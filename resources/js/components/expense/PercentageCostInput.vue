<template>
  <div class="space-y-4">
    <!-- Montant payé -->
    <div>
      <label class="block text-sm font-medium mb-1">Montant payé (€)</label>
      <input
        type="number"
        step="0.01"
        v-model.number="local.paidAmount"
        class="input"
        placeholder="0.00"
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
import { watch, reactive, computed } from "vue";

const props = defineProps({
  modelValue: Object, // { paidAmount: number, percentage: number }
});

const emit = defineEmits(["update:modelValue"]);

const local = reactive({
  paidAmount: props.modelValue?.paidAmount ?? 0,
  percentage: props.modelValue?.percentage ?? 0,
});

const reimbursedAmount = computed(() => {
  return (local.paidAmount * local.percentage) / 100;
});

watch(
  () => local,
  () => {
    emit("update:modelValue", {
      paidAmount: local.paidAmount,
      percentage: local.percentage,
      reimbursedAmount: reimbursedAmount.value,
    });
  },
  { deep: true }
);
</script>
