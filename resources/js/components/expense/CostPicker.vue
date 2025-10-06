<script setup>
import { Button } from '@/components/ui/button'

const props = defineProps({
  availableCosts: Array,
  selectedCosts: Array
})

const emit = defineEmits(['add'])

const addCost = (cost) => {
  if (props.selectedCosts.length < 30) {
    emit('add', JSON.parse(JSON.stringify(cost))) // Clone pour éviter les mutations
  } else {
    alert("Vous avez atteint le maximum de 30 coûts pour une demande.")
  }
}
</script>

<template>
  <div class="grid gap-3 sm:gap-4 grid-cols-1 xs:grid-cols-2 lg:grid-cols-3">
    <div
      v-for="(cost, index) in availableCosts"
      :key="index"
      class="border border-border rounded-lg p-3 sm:p-4 shadow-sm space-y-2 bg-card text-card-foreground"
    >
      <div class="flex justify-between items-center gap-2">
        <h3 class="text-base sm:text-lg font-semibold">{{ cost.name }}</h3>
        <span class="text-xs px-2 py-1 rounded-full bg-muted text-muted-foreground capitalize whitespace-nowrap">
          {{ cost.type }}
        </span>
      </div>
      <p class="text-xs sm:text-sm text-muted-foreground">{{ cost.description }}</p>

      <div class="flex justify-end">
        <Button size="sm" @click="addCost(cost)" class="w-full xs:w-auto text-xs sm:text-sm">
          Ajouter
        </Button>
      </div>
    </div>
  </div>
</template>
