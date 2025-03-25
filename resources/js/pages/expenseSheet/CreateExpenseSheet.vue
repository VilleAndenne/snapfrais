<template>
  <AppLayout>
    <Head title="Nouvelle note de frais" />

    <div class="container mx-auto p-4 space-y-6">
      <h1 class="text-2xl font-semibold">Créer une note de frais</h1>

      <!-- Picker des coûts -->
      <div>
        <h2 class="text-lg font-medium mb-2">Coûts disponibles</h2>
        <CostPicker
          :available-costs="costs"
          :selected-costs="selectedCosts"
          @add="addToRequest"
        />
      </div>

      <!-- Liste des coûts ajoutés à la demande -->
      <div v-if="selectedCosts.length" class="space-y-6 pt-6 border-t">
        <h2 class="text-lg font-medium">Votre demande</h2>

        <div
          v-for="(cost, index) in selectedCosts"
          :key="index"
          class="p-4 border rounded space-y-4 bg-white"
        >
          <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold">{{ cost.name }}</h3>
            <span class="text-sm italic text-muted">{{ cost.type }}</span>
          </div>
          <p class="text-sm text-gray-600">{{ cost.description }}</p>

          <!-- Type dynamique -->
          <div v-if="cost.type === 'km'">
            <KmCostInput v-model="costData[index].kmData" />
          </div>

          <div v-else-if="cost.type === 'fixed'">
            <FixedCostDisplay :amount="getActiveRate(cost)" />
          </div>

          <div v-else-if="cost.type === 'percentage'">
            <PercentageCostInput v-model="costData[index].percentageData" />
          </div>

          <!-- Prérequis -->
          <CostRequierementInput
            v-if="cost.requierements?.length"
            :requirements="cost.requierements"
            v-model="costData[index].requirements"
          />
        </div>
      </div>

      <!-- Submit -->
      <div class="flex justify-end pt-8">
        <Button @click="submit" :disabled="!selectedCosts.length"
          >Envoyer la demande</Button
        >
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import {Button} from "@/components/ui/button";

import CostPicker from "@/components/expense/CostPicker.vue";
import KmCostInput from "@/components/expense/KmCostInput.vue";
import FixedCostDisplay from "@/components/expense/FixedCostDisplay.vue";
import PercentageCostInput from "@/components/expense/PercentageCostInput.vue";
import CostRequierementInput from "@/components/expense/CostRequierementInput.vue";

const costs = ref([]); // tous les coûts définis dans le formulaire
const selectedCosts = ref([]); // ceux que l’utilisateur a sélectionnés
const costData = ref([]); // leurs données spécifiques

onMounted(() => {
  // À remplacer par un fetch dynamique depuis le backend
  costs.value = [
    {
      name: "Déplacement en voiture",
      type: "km",
      description: "Remboursé au km avec étapes",
      reimbursement_rates: [
        { start_date: "2024-01-01", end_date: "2025-12-31", value: 0.22 },
      ],
      requierements: [{ name: "Raison", type: "text" }],
    },
    {
      name: "Billet de train",
      type: "percentage",
      description: "Remboursé à 88%",
      reimbursement_rates: [
        { start_date: "2024-01-01", end_date: "2025-12-31", value: 88 },
      ],
      requierements: [{ name: "Billet", type: "file" }],
    },
    {
      name: "Prime vélo",
      type: "fixed",
      description: "Montant forfaitaire",
      reimbursement_rates: [
        { start_date: "2024-01-01", end_date: "2025-12-31", value: 15 },
      ],
      requierements: [],
    },
  ];
});

const getActiveRate = (cost) => {
  const today = new Date().toISOString().split("T")[0];
  const activeRate = cost.reimbursement_rates.find(
    (rate) => rate.start_date <= today && (!rate.end_date || rate.end_date >= today)
  );
  return activeRate?.value ?? 0;
};

const addToRequest = (cost) => {
    selectedCosts.value.push(JSON.parse(JSON.stringify(cost)))

  costData.value.push({
    kmData: {},
    percentageData: {
      paidAmount: null,
      percentage: getActiveRate(cost),
      reimbursedAmount: 0,
    },
    requirements: {},
  });
};

const submit = () => {
  const payload = {
    costs: selectedCosts.value.map((cost, index) => ({
      name: cost.name,
      type: cost.type,
      data:
        cost.type === "km"
          ? costData.value[index].kmData
          : cost.type === "percentage"
          ? costData.value[index].percentageData
          : { amount: getActiveRate(cost) },
      requirements: costData.value[index].requirements,
    })),
  };

  console.log("Payload à envoyer :", payload);

  // Tu peux ici faire un form.post('/notes-de-frais', payload) avec Inertia
};
</script>
