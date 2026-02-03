<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Modifier la note de frais" />

        <div class="container mx-auto space-y-4 sm:space-y-6 p-3 sm:p-4">
            <h1 class="text-xl sm:text-2xl font-semibold text-foreground">Modifier la note de frais</h1>

            <!-- Sélection du département -->
            <div class="flex flex-col space-y-2">
                <Label for="department">Département</Label>
                <Select v-model="form.department_id">
                    <SelectTrigger id="department">
                        <SelectValue placeholder="Sélectionner un département" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="dep in departments" :key="dep.id" :value="dep.id">
                            {{ dep.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <span v-if="form.errors.department_id" class="text-sm text-red-600">
                    {{ form.errors.department_id }}
                </span>
            </div>

            <!-- Sélecteur d'agent (visible si l'utilisateur est head du service sélectionné) -->
            <div v-if="isHeadOfSelectedDept" class="flex flex-col space-y-2">
                <Label for="targetUser">Pour quel agent ?</Label>
                <Select v-model="form.target_user_id">
                    <SelectTrigger id="targetUser">
                        <SelectValue placeholder="Sélectionner un agent" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="u in (selectedDepartment?.users || [])"
                            :key="u.id"
                            :value="u.id"
                        >
                            {{ u.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <span v-if="form.errors.target_user_id" class="text-sm text-red-600">
                    {{ form.errors.target_user_id }}
                </span>
            </div>

            <!-- Coûts ajoutés -->
            <div v-if="selectedCosts.length" class="space-y-4 sm:space-y-6 border-t border-border pt-4 sm:pt-6">
                <h2 class="text-base sm:text-lg font-medium text-foreground">Votre demande</h2>

                <div
                    v-for="(cost, index) in selectedCosts"
                    :key="index"
                    class="relative space-y-3 sm:space-y-4 rounded border border-border bg-card p-3 sm:p-4 text-card-foreground"
                >
                    <!-- Bouton supprimer -->
                    <Button
                        variant="ghost"
                        size="icon"
                        class="absolute top-2 right-2 h-8 w-8 sm:h-10 sm:w-10 text-destructive"
                        @click="removeCost(index)"
                    >
                        <Trash2Icon class="w-4 h-4 sm:w-5 sm:h-5" />
                    </Button>

                    <!-- En-tête coût -->
                    <div class="flex flex-col xs:flex-row xs:items-center xs:justify-between gap-2 pr-20 xs:pr-24">
                        <h3 class="text-lg sm:text-xl font-bold text-foreground">{{ cost.name }}</h3>
                        <span class="text-xs sm:text-sm italic text-muted-foreground">{{ cost.type }}</span>
                    </div>
                    <p class="text-xs sm:text-sm text-muted-foreground">{{ cost.description }}</p>

                    <!-- Date du coût -->
                    <div class="mt-2">
                        <Label for="cost-date" class="text-xs sm:text-sm">Date du coût</Label>
                        <input
                            type="date"
                            v-model="costData[index].date"
                            class="w-full rounded border border-border bg-background p-1.5 sm:p-2 text-sm sm:text-base text-foreground"
                            required
                        />
                    </div>

                    <!-- Champs dynamiques selon le type de coût -->
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
                        v-if="cost.requirements?.length"
                        :requirements="cost.requirements"
                        :existing-data="cost.requirements_data || {}"
                        v-model="costData[index].requirements"
                    />
                </div>
            </div>

            <!-- Coûts disponibles -->
            <div>
                <h2 class="mb-2 text-base sm:text-lg font-medium text-foreground">Types de coûts disponibles</h2>
                <p class="mb-3 sm:mb-4 text-xs sm:text-sm text-muted-foreground">
                    Coûts ajoutés : {{ selectedCosts.length }}/30
                </p>
                <CostPicker
                    :available-costs="costs"
                    :selected-costs="selectedCosts"
                    @add="addToRequest"
                />
            </div>

            <!-- Bouton d'envoi -->
            <div class="flex flex-col xs:flex-row justify-end gap-2 sm:gap-3 pt-6 sm:pt-8">
                <Button @click="submit" :disabled="!selectedCosts.length || form.processing" class="w-full xs:w-auto">
                    <Loader2Icon v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <span class="hidden sm:inline">{{ form.processing ? "Mise à jour en cours..." : "Mettre à jour la demande" }}</span>
                    <span class="sm:hidden">{{ form.processing ? "Mise à jour..." : "Mettre à jour" }}</span>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { useForm, Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Loader2Icon, Trash2Icon } from "lucide-vue-next";

import CostPicker from "@/components/expense/CostPicker.vue";
import KmCostInput from "@/components/expense/KmCostInput.vue";
import FixedCostDisplay from "@/components/expense/FixedCostDisplay.vue";
import PercentageCostInput from "@/components/expense/PercentageCostInput.vue";
import CostRequierementInput from "@/components/expense/CostRequirementInput.vue";

const costs = ref([]);
const selectedCosts = ref([]);
const costData = ref([]);
const departments = ref([]);

const props = defineProps({
    form: Object,
    expenseSheet: Object,
    departments: Array,
    authUser: Object,
});

const form = useForm({
    costs: [],
    department_id: null,
    target_user_id: null,
    _method: 'PUT',
});

// Breadcrumbs (alignés avec Create.vue)
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notes de frais', href: '/expense-sheet' },
    { title: 'Modifier une note de frais' },
];

// Département sélectionné (pour afficher les utilisateurs)
const selectedDepartment = computed(() => {
    return departments.value.find((d) => d.id === form.department_id) || null;
});

// Vérifie si l'utilisateur est head du département sélectionné
const isHeadOfSelectedDept = computed(() => {
    if (!selectedDepartment.value) return false;
    return (selectedDepartment.value.heads || []).some((h) => h.id === props.authUser.id);
});

// Quand le département change : si head -> pré-sélectionne l'utilisateur existant ou soi-même, sinon reset
watch(
    () => form.department_id,
    () => {
        if (isHeadOfSelectedDept.value) {
            if (!form.target_user_id) {
                form.target_user_id = props.authUser.id;
            }
        } else {
            form.target_user_id = null;
        }
    }
);

const getActiveRate = (cost) => {
    const today = new Date().toISOString().split("T")[0];
    const activeRate = (cost.reimbursement_rates || []).find(
        (rate) => rate.start_date <= today && (!rate.end_date || rate.end_date >= today)
    );
    return activeRate?.value ?? 0;
};

onMounted(() => {
    // Initialiser les départements
    departments.value = props.departments || [];

    // Pré-remplir le département et l'utilisateur existants
    form.department_id = props.expenseSheet.department_id;
    form.target_user_id = props.expenseSheet.user_id;

    costs.value = props.form.costs;

    // Hydratation des coûts sélectionnés à partir de la note existante
    selectedCosts.value = props.expenseSheet.costs
        .map((item) => {
            const foundCost = costs.value.find((c) => c.id === item.cost_id);
            if (!foundCost) return null;
            return { ...foundCost, ...item };
        })
        .filter(Boolean);

    // Hydratation des données de formulaire pour chaque coût
    costData.value = props.expenseSheet.costs.map((item) => {
        let data = {};
        if (item.type === 'km') {
            data = {
                departure: item.data.departure || '',
                arrival: item.data.arrival || '',
                steps: item.data.steps || [],
                manualKm: item.data.manualKm || 0,
                justification: item.data.justification || '',
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

        const requirementsValues = {};
        if (item.requirements_data) {
            Object.entries(item.requirements_data).forEach(([key, value]) => {
                if (value?.value !== undefined) {
                    requirementsValues[key] = value.value;
                }
            });
        }

        return {
            kmData: item.type === 'km' ? data : {},
            percentageData: item.type === 'percentage' ? data : { paidAmount: null, percentage: getActiveRate(item), reimbursedAmount: 0 },
            fixedAmount: item.type === 'fixed' ? data.amount : getActiveRate(item),
            requirements: requirementsValues,
            date: item.date || new Date().toISOString().split('T')[0],
        };
    });
});

const addToRequest = (cost) => {
    if (selectedCosts.value.length >= 30) return;
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

        // Nouvelles valeurs
        if (costData.value[index].requirements) {
            Object.entries(costData.value[index].requirements).forEach(([reqId, req]) => {
                if (req instanceof File) {
                    requirements[reqId] = { file: req };
                } else if (req !== null && req !== undefined && req !== '') {
                    requirements[reqId] = { value: req };
                }
            });
        }

        // Conserver les fichiers existants
        if (cost.requirements_data) {
            Object.entries(cost.requirements_data).forEach(([reqId, existingData]) => {
                if (!requirements[reqId] && existingData?.file) {
                    requirements[reqId] = { existing_file: existingData.file };
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

    form.post("/expense-sheet/" + props.expenseSheet.id, {
        onSuccess: () => {
            window.location.href = '/dashboard';
        },
        onError: (errors) => {
            alert("Une erreur est survenue lors de la mise à jour : " + Object.values(errors).join(', '));
        },
        forceFormData: true,
        transform: (data) => {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('department_id', data.department_id);

            if (!data.costs || data.costs.length === 0) {
                return formData;
            }

            data.costs.forEach((cost, index) => {
                formData.append(`costs[${index}][cost_id]`, cost.cost_id);
                formData.append(`costs[${index}][date]`, cost.date);

                Object.entries(cost.data).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        value.forEach((item, itemIndex) => {
                            formData.append(`costs[${index}][data][${key}][${itemIndex}]`, item);
                        });
                    } else if (value !== null && value !== undefined) {
                        formData.append(`costs[${index}][data][${key}]`, value);
                    }
                });

                if (cost.requirements) {
                    Object.entries(cost.requirements).forEach(([reqId, req]) => {
                        if (req.file instanceof File) {
                            formData.append(`costs[${index}][requirements][${reqId}][file]`, req.file);
                        } else if (req.existing_file) {
                            formData.append(`costs[${index}][requirements][${reqId}][existing_file]`, req.existing_file);
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
</script>
