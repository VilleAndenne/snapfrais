<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nouvelle note de frais" />

        <div class="container mx-auto p-4 space-y-6">
            <h1 class="text-2xl font-semibold text-foreground">Créer une note de frais</h1>

            <!-- Sélection du département -->
            <div class="flex flex-col space-y-2">
                <Label for="department">Département</Label>
                <Select v-model="form.department_id">
                    <SelectTrigger id="department">
                        <SelectValue placeholder="Sélectionner un département" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="dep in props.departments"
                            :key="dep.id"
                            :value="dep.id"
                        >
                            {{ dep.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <span v-if="form.errors.department_id" class="text-sm text-red-600">
                    {{ form.errors.department_id }}
                </span>
            </div>

            <!-- Coûts ajoutés -->
            <div v-if="selectedCosts.length" class="space-y-6 pt-6 border-t border-border">
                <h2 class="text-lg font-medium text-foreground">Votre demande</h2>

                <div
                    v-for="(cost, index) in selectedCosts"
                    :key="index"
                    class="p-4 border border-border rounded bg-card text-card-foreground relative space-y-4"
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
                        <span class="text-sm italic text-muted-foreground">{{ cost.type }}</span>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ cost.description }}</p>

                    <!-- Champ de date -->
                    <div class="mt-2">
                        <Label for="cost-date" class="text-sm">Date du coût</Label>
                        <input
                            type="date"
                            v-model="costData[index].date"
                            class="border border-border rounded p-2 w-full bg-background text-foreground"
                            @change="updateRate(index, cost)"
                        />
                        <span
                            v-if="form.errors[`costs.${index}.date`]"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors[`costs.${index}.date`] }}
                        </span>
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
                <h2 class="text-lg font-medium text-foreground mb-2">Types de coûts disponibles</h2>
                <p class="text-sm text-muted-foreground mb-4">
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
import CostrequirementInput from '@/components/expense/CostRequirementInput.vue';

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
        alert('Vous avez atteint le maximum de 30 coûts.');
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
        const paidAmount = costData.value[index].percentageData.paidAmount;
        if (paidAmount !== null && paidAmount !== undefined) {
            costData.value[index].percentageData.reimbursedAmount = (paidAmount * rate) / 100;
        }
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
        preserveState: true,
        onSuccess: () => {
            form.reset();
            selectedCosts.value = [];
            costData.value = [];
        },
        onError: (errors) => {
            console.warn('Validation error(s)', errors);
        },
        transform: (data) => {
            const formData = new FormData();
            formData.append('department_id', form.department_id);

            data.costs.forEach((cost, index) => {
                formData.append(`costs[${index}][cost_id]`, cost.cost_id);
                formData.append(`costs[${index}][date]`, cost.date);

                Object.entries(cost.data).forEach(([key, value]) => {
                    formData.append(`costs[${index}][data][${key}]`, value);
                });

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

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notes de frais', href: '/expense-sheet' },
    { title: 'Créer une note de frais' }
];
</script>
