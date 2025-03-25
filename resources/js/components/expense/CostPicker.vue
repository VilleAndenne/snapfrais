<template>
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    <div
      v-for="(cost, index) in availableCosts"
      :key="index"
      class="border rounded-lg p-4 shadow-sm space-y-2 bg-white"
    >
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold">{{ cost.name }}</h3>
        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 capitalize">
          {{ cost.type }}
        </span>
      </div>
      <p class="text-sm text-gray-600">{{ cost.description }}</p>

      <div class="flex justify-end">
        <Button size="sm" @click="addCost(cost)" :disabled="isAlreadySelected(cost)">
          {{ isAlreadySelected(cost) ? "Ajouté" : "Ajouter" }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Button } from "@/components/ui/button";
import { computed, toRefs } from "vue";

const props = defineProps({
  availableCosts: Array,
  selectedCosts: Array,
});

const emits = defineEmits(["add"]);

const isAlreadySelected = (cost) => {
  return props.selectedCosts.some((c) => c.name === cost.name);
};

const addCost = (cost) => {
  if (!isAlreadySelected(cost)) {
    emits("add", cost);
  }
};
</script>
