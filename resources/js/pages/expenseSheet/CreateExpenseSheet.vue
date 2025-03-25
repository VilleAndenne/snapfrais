<template>
  <AppLayout>
    <Head title="Nouvelle note de frais" />

    <div class="container mx-auto p-4 space-y-6">
      <h1 class="text-2xl font-semibold">Créer une note de frais</h1>

      <!-- Coûts ajoutés -->
      <div v-if="selectedCosts.length" class="space-y-6 pt-6 border-t">
        <h2 class="text-lg font-medium">Votre demande</h2>

        <div
          v-for="(cost, index) in selectedCosts"
          :key="index"
          class="p-4 border rounded space-y-4 bg-white relative"
        >
          <!-- Supprimer -->
          <Button
            variant="ghost"
            size="icon"
            class="absolute top-2 right-2 text-destructive"
            @click="removeCost(index)"
          >
            <Trash2Icon class="w-5 h-5" />
          </Button>

          <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold">{{ cost.name }}</h3>
            <span class="text-sm italic text-muted">{{ cost.type }}</span>
          </div>
          <p class="text-sm text-gray-600">{{ cost.description }}</p>

          <!-- Champs dynamiques -->
          <div v-if="cost.type === 'km'">
            <KmCostInput v-model="costData[index].kmData" />
          </div>
          <div v-else-if="cost.type === 'fixed'">
            <FixedCostDisplay v-model="costData[index].fixedAmount" />
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

      <!-- Coûts disponibles -->
      <div>
        <h2 class="text-lg font-medium mb-2">Types de coûts disponibles</h2>
        <p class="text-sm text-gray-600 mb-4">
          Coûts ajoutés : {{ selectedCosts.length }}/7
        </p>
        <CostPicker
          :available-costs="costs"
          :selected-costs="selectedCosts"
          @add="addToRequest"
        />
      </div>

      <!-- Submit -->
      <div class="flex justify-end pt-8">
        <Button @click="submit" :disabled="!selectedCosts.length || form.processing">
          <Loader2Icon v-if="form.processing" class="w-4 h-4 animate-spin mr-2" />
          {{ form.processing ? "Envoi en cours..." : "Envoyer la demande" }}
        </Button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useForm, Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { Button } from "@/components/ui/button";
import { Loader2Icon, Trash2Icon } from "lucide-vue-next";

import CostPicker from "@/components/expense/CostPicker.vue";
import KmCostInput from "@/components/expense/KmCostInput.vue";
import FixedCostDisplay from "@/components/expense/FixedCostDisplay.vue";
import PercentageCostInput from "@/components/expense/PercentageCostInput.vue";
import CostRequierementInput from "@/components/expense/CostRequierementInput.vue";

const costs = ref([]);
const selectedCosts = ref([]);
const costData = ref([]);

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
});


const form = useForm({
  costs: [],
});

onMounted(() => {
  costs.value = props.form.costs;
});

const getActiveRate = (cost) => {
  const today = new Date().toISOString().split("T")[0];
  const activeRate = cost.reimbursement_rates.find(
    (rate) => rate.start_date <= today && (!rate.end_date || rate.end_date >= today)
  );
  return activeRate?.value ?? 0;
};

const addToRequest = (cost) => {
  if (selectedCosts.value.length >= 7) {
    alert("Vous avez atteint le maximum de 7 coûts.");
    return;
  }

  const copy = JSON.parse(JSON.stringify(cost));
  selectedCosts.value.push(copy);

  costData.value.push({
    kmData: {},
    percentageData: {
      paidAmount: null,
      percentage: getActiveRate(cost),
      reimbursedAmount: 0,
    },
    requirements: {},
    fixedAmount: getActiveRate(cost),
  });

  console.log(costData.value);
};

const removeCost = (index) => {
  selectedCosts.value.splice(index, 1);
  costData.value.splice(index, 1);
};

const submit = () => {
  form.costs = selectedCosts.value.map((cost, index) => ({
    cost_id: cost.id,
    data:
      cost.type === "km"
        ? costData.value[index].kmData
        : cost.type === "percentage"
        ? costData.value[index].percentageData
        : { amount: getActiveRate(cost) },
    requirements: costData.value[index].requirements,
  }));

  form.post("/expense-sheet/" + props.form.id, {
    onSuccess: () => {
      alert("Note de frais enregistrée avec succès !");
      form.reset();
      selectedCosts.value = [];
      costData.value = [];
    },
    onError: (errors) => {
      console.error("Erreurs à l’envoi :", errors);
    },
  });
};
</script>
