<script setup>
import {Button} from '@/components/ui/button'

const props = defineProps({
  availableCosts: Array,
  selectedCosts: Array
})

const emit = defineEmits(['add'])

const addCost = (cost) => {
  if (props.selectedCosts.length < 7) {
    emit('add', JSON.parse(JSON.stringify(cost))) // Clone pour éviter les mutations
  } else {
    alert("Vous avez atteint le maximum de 7 coûts pour une demande.")
  }
}
</script>

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
        <Button size="sm" @click="addCost(cost)">
          Ajouter
        </Button>
      </div>
    </div>
  </div>
</template>
