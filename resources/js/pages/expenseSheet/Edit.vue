<template>
    <AppLayout>
        <Head title="Modifier la note de frais" />

        <div class="container mx-auto p-4 space-y-6">
            <h1 class="text-2xl font-semibold">Modifier la note de frais</h1>

            <div v-if="selectedCosts.length" class="space-y-6 pt-6 border-t">
                <h2 class="text-lg font-medium">Votre demande</h2>

                <div
                    v-for="(cost, index) in selectedCosts"
                    :key="index"
                    class="p-4 border rounded space-y-4 bg-white relative"
                >
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

                    <div v-if="cost.type === 'km'">
                        <KmCostInput v-model="costData[index].kmData" />
                    </div>
                    <div v-else-if="cost.type === 'fixed'">
                        <FixedCostDisplay v-model="costData[index].fixedAmount" />
                    </div>
                    <div v-else-if="cost.type === 'percentage'">
                        <PercentageCostInput v-model="costData[index].percentageData" />
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Date du coût</label>
                        <input 
                            type="date" 
                            v-model="costData[index].date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                            required
                        />
                    </div>

                    <CostRequierementInput
                        v-if="cost.requirements?.length"
                        :requirements="cost.requirements"
                        :existing-files="getExistingFiles(cost.requirements_data)"
                        v-model="costData[index].requirements"
                    />
                </div>
            </div>

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

            <div class="flex justify-end pt-8">
                <Button @click="submit" :disabled="!selectedCosts.length || form.processing">
                    <Loader2Icon v-if="form.processing" class="w-4 h-4 animate-spin mr-2" />
                    {{ form.processing ? "Mise à jour en cours..." : "Mettre à jour la demande" }}
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
import CostRequierementInput from "@/components/expense/CostRequirementInput.vue";

const costs = ref([]);
const selectedCosts = ref([]);
const costData = ref([]);

const props = defineProps({
    form: Object,
    expenseSheet: Object,
});

const form = useForm({
    costs: [],
});

const getActiveRate = (cost) => {
    const today = new Date().toISOString().split("T")[0];
    const activeRate = cost.reimbursement_rates.find(
        (rate) => rate.start_date <= today && (!rate.end_date || rate.end_date >= today)
    );
    return activeRate?.value ?? 0;
};

onMounted(() => {
    console.log('Form costs:', props.form.costs);
    console.log('Expense sheet costs:', props.expenseSheet.costs);
    
    costs.value = props.form.costs;
    selectedCosts.value = props.expenseSheet.costs.map((item) => {
        const foundCost = costs.value.find((c) => c.id === item.cost_id);
        if (!foundCost) {
            console.warn('Cost not found:', item.cost_id);
            return null;
        }
        return {
            ...foundCost,
            ...item
        };
    }).filter(Boolean);

    costData.value = props.expenseSheet.costs.map((item) => {
        let data = {};
        if (item.type === 'km') {
            data = {
                departure: item.data.route?.departure || '',
                arrival: item.data.route?.arrival || '',
                steps: item.data.route?.steps || [],
                manualKm: item.data.route?.manual_km || 0,
                justification: item.data.route?.justification || '',
            };
        } else if (item.type === 'percentage') {
            data = {
                paidAmount: item.data.paidAmount || 0,
                percentage: getActiveRate(item),
                reimbursedAmount: item.total || 0,
            };
        } else if (item.type === 'fixed') {
            data = {
                amount: item.total || getActiveRate(item),
            };
        }

        return {
            kmData: item.type === 'km' ? data : {},
            percentageData: item.type === 'percentage' ? data : { paidAmount: null, percentage: getActiveRate(item), reimbursedAmount: 0 },
            fixedAmount: item.type === 'fixed' ? data.amount : getActiveRate(item),
            requirements: item.requirements_data || {},
            date: item.date || new Date().toISOString().split('T')[0],
        };
    });

    console.log('Selected costs:', selectedCosts.value);
    console.log('Cost data:', costData.value);
});

const addToRequest = (cost) => {
    if (selectedCosts.value.length >= 7) return;
    const copy = JSON.parse(JSON.stringify(cost));
    selectedCosts.value.push(copy);
    costData.value.push({
        kmData: {},
        percentageData: { paidAmount: null, percentage: getActiveRate(cost), reimbursedAmount: 0 },
        requirements: {},
        fixedAmount: getActiveRate(cost),
        date: new Date().toISOString().split('T')[0],
    });
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

    form.put("/expense-sheet/" + props.expenseSheet.id, {
        onSuccess: () => {
            window.location.href = '/dashboard';
        },
        onError: (errors) => {
            alert("Une erreur est survenue lors de la mise à jour : " + Object.values(errors).join(', '));
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
                            formData.append(`costs[${index}][requirements][${reqId}][file]`, req.file);
                        } else if (req.value) {
                            formData.append(`costs[${index}][requirements][${reqId}][value]`, req.value);
                        }
                    });
                }
            });

            return formData;
        }
    });
};

const getExistingFiles = (requirementsData) => {
    if (!requirementsData) return {};
    const files = {};
    Object.entries(requirementsData).forEach(([key, value]) => {
        if (value?.file) {
            files[key] = value.file;
        }
    });
    return files;
};
</script>
