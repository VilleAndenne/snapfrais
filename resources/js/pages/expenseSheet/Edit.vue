<template>
    <AppLayout>
        <Head title="Modifier la note de frais" />

        <div class="container mx-auto p-4 space-y-6">
            <h1 class="text-2xl font-semibold">Modifier la note de frais</h1>

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
                        :existing-data="cost.requirements_data || {}"
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

// Département sélectionné (pour afficher les utilisateurs)
const selectedDepartment = computed(() => {
    return departments.value.find((d) => d.id === form.department_id) || null;
});

// Vérifie si l'utilisateur est head du département sélectionné
const isHeadOfSelectedDept = computed(() => {
    if (!selectedDepartment.value) return false;
    return selectedDepartment.value.heads.some((h) => h.id === props.authUser.id);
});

// Quand le département change : si head -> pré-sélectionne l'utilisateur existant ou soi-même, sinon reset
watch(
    () => form.department_id,
    () => {
        if (isHeadOfSelectedDept.value) {
            // Garder le target_user_id existant ou mettre soi-même
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
    const activeRate = cost.reimbursement_rates.find(
        (rate) => rate.start_date <= today && (!rate.end_date || rate.end_date >= today)
    );
    return activeRate?.value ?? 0;
};

onMounted(() => {
    console.log('Form costs:', props.form.costs);
    console.log('Expense sheet costs:', props.expenseSheet.costs);

    // Initialiser les départements
    departments.value = props.departments || [];

    // Pré-remplir le département et l'utilisateur existants
    form.department_id = props.expenseSheet.department_id;
    form.target_user_id = props.expenseSheet.user_id;

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

        // Extraire les valeurs des requirements pour l'initialisation
        const requirementsValues = {};
        if (item.requirements_data) {
            Object.entries(item.requirements_data).forEach(([key, value]) => {
                if (value?.value !== undefined) {
                    // Pour les champs texte, on stocke directement la valeur
                    requirementsValues[key] = value.value;
                }
                // Pour les fichiers, on ne les ajoute pas ici, ils seront gérés via existingData
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

        // Ajouter les nouvelles valeurs depuis costData
        if (costData.value[index].requirements) {
            Object.entries(costData.value[index].requirements).forEach(([reqId, req]) => {
                if (req instanceof File) {
                    requirements[reqId] = { file: req };
                } else if (req !== null && req !== undefined && req !== '') {
                    requirements[reqId] = { value: req };
                }
            });
        }

        // Conserver les fichiers existants non modifiés
        if (cost.requirements_data) {
            Object.entries(cost.requirements_data).forEach(([reqId, existingData]) => {
                // Si ce requirement n'a pas été modifié (pas dans requirements) et c'est un fichier
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

    console.log('Form costs before submit:', form.costs);

    form.post("/expense-sheet/" + props.expenseSheet.id, {
        onSuccess: () => {
            window.location.href = '/dashboard';
        },
        onError: (errors) => {
            console.log('Submit errors:', errors);
            alert("Une erreur est survenue lors de la mise à jour : " + Object.values(errors).join(', '));
        },
        forceFormData: true,
        transform: (data) => {
            console.log('Transform data:', data);
            const formData = new FormData();
            formData.append('_method', 'PUT');

            if (!data.costs || data.costs.length === 0) {
                console.error('No costs in data!');
                return formData;
            }

            data.costs.forEach((cost, index) => {
                formData.append(`costs[${index}][cost_id]`, cost.cost_id);
                formData.append(`costs[${index}][date]`, cost.date);

                // Ajouter les données du coût
                Object.entries(cost.data).forEach(([key, value]) => {
                    if (Array.isArray(value)) {
                        // Pour les tableaux (comme steps), ajouter chaque élément
                        value.forEach((item, itemIndex) => {
                            formData.append(`costs[${index}][data][${key}][${itemIndex}]`, item);
                        });
                    } else if (value !== null && value !== undefined) {
                        formData.append(`costs[${index}][data][${key}]`, value);
                    }
                });

                // Ajouter les requirements correctement
                if (cost.requirements) {
                    Object.entries(cost.requirements).forEach(([reqId, req]) => {
                        if (req.file instanceof File) {
                            formData.append(`costs[${index}][requirements][${reqId}][file]`, req.file);
                        } else if (req.existing_file) {
                            // Fichier existant non modifié
                            formData.append(`costs[${index}][requirements][${reqId}][existing_file]`, req.existing_file);
                        } else if (req.value) {
                            formData.append(`costs[${index}][requirements][${reqId}][value]`, req.value);
                        }
                    });
                }
            });

            // Debug: afficher le contenu du FormData
            for (let pair of formData.entries()) {
                console.log(pair[0]+ ': ' + pair[1]);
            }

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
