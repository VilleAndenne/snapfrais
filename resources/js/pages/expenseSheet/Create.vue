<template>
    <AppLayout>
        <Head title="Nouvelle note de frais" />

        <div class="container mx-auto p-4 space-y-6">
            <h1 class="text-2xl font-semibold">Créer une note de frais</h1>

            <!-- Sélection du département -->
            <div class="flex items-center space-x-4">
                <Label for="department" class="w-1/4">Département</Label>
                <Select v-model="form.department_id">
                    <SelectTrigger id="department" class="w-3/4">
                        <SelectValue placeholder="Sélectionner un département" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="dep in props.departments" :key="dep.id" :value="dep.id">
                            {{ dep.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Coûts ajoutés -->
            <div v-if="selectedCosts.length" class="space-y-6 pt-6 border-t">
                <h2 class="text-lg font-medium">Votre demande</h2>

                <div
                    v-for="(cost, index) in selectedCosts"
                    :key="index"
                    class="p-4 border rounded space-y-4 bg-white relative"
                >
                    <!-- Bouton de suppression -->
                    <Button
                        variant="ghost"
                        size="icon"
                        class="absolute top-2 right-2 text-destructive"
                        @click="removeCost(index)"
                    >
                        <Trash2Icon class="w-5 h-5" />
                    </Button>

                    <!-- Détails du coût -->
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold">{{ cost.name }}</h3>
                        <span class="text-sm italic text-muted">{{ cost.type }}</span>
                    </div>
                    <p class="text-sm text-gray-600">{{ cost.description }}</p>

                    <!-- Champ de date -->
                    <div class="mt-2">
                        <Label for="cost-date" class="text-sm">Date du coût</Label>
                        <input
                            type="date"
                            v-model="costData[index].date"
                            class="border rounded p-2 w-full"
                            @change="updateRate(index, cost)"
                        />
                    </div>

                    <!-- Champs dynamiques selon le type de coût -->
                    <div v-if="cost.type === 'km'">
                        <KmCostInput v-model="costData[index].kmData" />
                    </div>
                    <div v-else-if="cost.type === 'fixed'">
                        <FixedCostDisplay v-model="costData[index]" />
                    </div>
                    <div v-else-if="cost.type === 'percentage'">
                        <PercentageCostInput v-model="costData[index].percentageData" />
                    </div>

                    <!-- Prérequis du coût -->
                    <CostrequirementInput
                        v-if="cost.requirements?.length"
                        :requirements="cost.requirements"
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

            <!-- Bouton d'envoi -->
            <div class="flex justify-end pt-8">
                <Button @click="submit" :disabled="!selectedCosts.length || form.processing">
                    <Loader2Icon v-if="form.processing" class="w-4 h-4 animate-spin mr-2" />
                    {{ form.processing ? 'Envoi en cours...' : 'Envoyer la demande' }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Loader2Icon, Trash2Icon } from 'lucide-vue-next';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/components/ui/select';

import CostPicker from '@/components/expense/CostPicker.vue';
import KmCostInput from '@/components/expense/KmCostInput.vue';
import FixedCostDisplay from '@/components/expense/FixedCostDisplay.vue';
import PercentageCostInput from '@/components/expense/PercentageCostInput.vue';
import CostrequirementInput from '@/components/expense/require.vue';

const costs = ref([]);
const selectedCosts = ref([]);
const costData = ref([]);

const props = defineProps({
    form: { type: Object, required: true },
    departments: { type: Array, required: true }
});

const form = useForm({
    costs: [],
    department_id: null
});

onMounted(() => {
    costs.value = props.form.costs;
});

const getActiveRate = (cost, date) => {
    const activeRate = cost.reimbursement_rates.find(
        (rate) => rate.start_date <= date && (!rate.end_date || rate.end_date >= date)
    );
    return activeRate?.value ?? 0;
};

const addToRequest = (cost) => {
    if (selectedCosts.value.length >= 7) {
        alert('Vous avez atteint le maximum de 7 coûts.');
        return;
    }

    selectedCosts.value.push(cost);
    costData.value.push({
        date: new Date().toISOString().split('T')[0],
        kmData: {},
        percentageData: {
            paidAmount: null,
            percentage: getActiveRate(cost, new Date().toISOString().split('T')[0]),
            reimbursedAmount: 0
        },
        requirements: {},
        fixedAmount: getActiveRate(cost, new Date().toISOString().split('T')[0])
    });
};

const updateRate = (index, cost) => {
    const date = costData.value[index].date;
    const rate = getActiveRate(cost, date);

    if (cost.type === 'percentage') {
        costData.value[index].percentageData.percentage = rate;
    } else if (cost.type === 'fixed') {
        costData.value[index].fixedAmount = rate;
    }
};

const removeCost = (index) => {
    selectedCosts.value.splice(index, 1);
    costData.value.splice(index, 1);
};

const submit = () => {
    form.costs = selectedCosts.value.map((cost, index) => {
        const data =
            cost.type === 'km' ? costData.value[index].kmData :
                cost.type === 'percentage' ? costData.value[index].percentageData :
                    { amount: costData.value[index].fixedAmount };

        const requirements = {};
        if (costData.value[index].requirements) {
            Object.entries(costData.value[index].requirements).forEach(([reqId, req]) => {
                if (req instanceof File) {
                    requirements[reqId] = { file: req };
                } else if (req !== null && req !== undefined && req !== '') {
                    requirements[reqId] = { value: req };
                }
            });
        }

        return {
            cost_id: cost.id,
            date: costData.value[index].date,
            data,
            requirements
        };
    });

    form.post(`/expense-sheet/${props.form.id}`, {
        preserveState: false,
        onSuccess: () => {
            alert('Note de frais enregistrée avec succès !');
            form.reset();
            selectedCosts.value = [];
            costData.value = [];
        },
        onError: (errors) => {
            alert('Une erreur est survenue lors de l\'envoi.');
            console.error(errors);
        },
        transform: (data) => {
            const formData = new FormData();
            formData.append('department_id', form.department_id);

            data.costs.forEach((cost, index) => {
                formData.append(`costs[${index}][cost_id]`, cost.cost_id);
                formData.append(`costs[${index}][date]`, cost.date);

                // Ajouter les données du coût
                Object.entries(cost.data).forEach(([key, value]) => {
                    formData.append(`costs[${index}][data][${key}]`, value);
                });

                // Ajouter les requirements correctement
                if (cost.requirements) {
                    Object.entries(cost.requirements).forEach(([reqId, req]) => {
                        if (req.file instanceof File) {
                            formData.append(`costs[${index}][data][requirements][${reqId}][file]`, req.file);
                        } else if (req.value) {
                            formData.append(`costs[${index}][data][requirements][${reqId}][value]`, req.value);
                        }
                    });
                }
            });

            return formData;
        }
    });
};


</script>
