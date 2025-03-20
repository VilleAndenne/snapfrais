<template>
    <AppLayout>
        <Head title="Créer un formulaire" />
        <div class="container mx-auto p-4">
            <Card class="w-full">
                <CardHeader>
                    <CardTitle>Créer un formulaire</CardTitle>
                    <CardDescription>
                        Créez un nouveau formulaire pour collecter des informations auprès des utilisateurs.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submitForm" class="space-y-8">
                        <!-- Form Basic Information -->
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Nom du formulaire</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :class="{ 'border-destructive': errors.name }"
                                />
                                <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    :class="{ 'border-destructive': errors.description }"
                                />
                                <p v-if="errors.description" class="text-sm text-destructive">{{ errors.description
                                    }}</p>
                            </div>
                        </div>

                        <!-- Form Costs Section -->
                        <div class="space-y-6">
                            <h2 class="text-xl font-semibold">Coûts</h2>

                            <div v-for="(cost, costIndex) in form.costs" :key="costIndex"
                                 class="p-4 border rounded-lg space-y-4">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-medium">Coût #{{ costIndex + 1 }}</h3>
                                    <Button variant="ghost" size="icon" @click="removeCost(costIndex)"
                                            class="text-destructive">
                                        <Trash2Icon class="h-5 w-5" />
                                    </Button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label :for="`cost-name-${costIndex}`">Nom</Label>
                                        <Input
                                            :id="`cost-name-${costIndex}`"
                                            v-model="cost.name"
                                            :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.name`) }"
                                        />
                                        <p v-if="getErrorPath(`costs.${costIndex}.name`)"
                                           class="text-sm text-destructive">{{ getErrorPath(`costs.${costIndex}.name`)
                                            }}</p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`cost-type-${costIndex}`">Type</Label>
                                        <Select v-model="cost.type">
                                            <SelectTrigger :id="`cost-type-${costIndex}`"
                                                           :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.type`) }">
                                                <SelectValue placeholder="Sélectionner un type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="km">Kilométrage</SelectItem>
                                                <SelectItem value="fixed">Fixe</SelectItem>
                                                <SelectItem value="percentage">Pourcentage</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="getErrorPath(`costs.${costIndex}.type`)"
                                           class="text-sm text-destructive">{{ getErrorPath(`costs.${costIndex}.type`)
                                            }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label :for="`cost-description-${costIndex}`">Description</Label>
                                    <Textarea
                                        :id="`cost-description-${costIndex}`"
                                        v-model="cost.description"
                                        rows="2"
                                        :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.description`) }"
                                    />
                                    <p v-if="getErrorPath(`costs.${costIndex}.description`)"
                                       class="text-sm text-destructive">
                                        {{ getErrorPath(`costs.${costIndex}.description`) }}</p>
                                </div>

                                <!-- Cost Requirements -->
                                <div class="mt-4 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-md font-medium">Exigences de coûts</h4>
                                        <Button variant="outline" size="sm" @click="addRequirement(costIndex)"
                                                class="flex items-center">
                                            <PlusIcon class="h-4 w-4 mr-1" />
                                            Ajouter une exigence
                                        </Button>
                                    </div>

                                    <div v-for="(req, reqIndex) in cost.requirements" :key="reqIndex"
                                         class="p-3 border rounded-md space-y-3">
                                        <div class="flex justify-between items-center">
                                            <h5 class="text-sm font-medium">Exigence #{{ reqIndex + 1 }}</h5>
                                            <Button variant="ghost" size="icon"
                                                    @click="removeRequirement(costIndex, reqIndex)"
                                                    class="text-destructive h-8 w-8">
                                                <Trash2Icon class="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <Label :for="`req-name-${costIndex}-${reqIndex}`"
                                                       class="text-xs">Nom</Label>
                                                <Input
                                                    :id="`req-name-${costIndex}-${reqIndex}`"
                                                    v-model="req.name"
                                                    class="h-8 text-sm"
                                                    :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.name`) }"
                                                />
                                                <p v-if="getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.name`)"
                                                   class="text-xs text-destructive">
                                                    {{ getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.name`)
                                                    }}</p>
                                            </div>

                                            <div class="space-y-1">
                                                <Label :for="`req-type-${costIndex}-${reqIndex}`"
                                                       class="text-xs">Type</Label>
                                                <Select v-model="req.type">
                                                    <SelectTrigger :id="`req-type-${costIndex}-${reqIndex}`"
                                                                   class="h-8 text-sm"
                                                                   :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.type`) }">
                                                        <SelectValue placeholder="Sélectionner un type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="text">Texte</SelectItem>
                                                        <SelectItem value="file">Fichier</SelectItem>
                                                        <SelectItem value="number">Nombre</SelectItem>
                                                        <SelectItem value="date">Date</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <p v-if="getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.type`)"
                                                   class="text-xs text-destructive">
                                                    {{ getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.type`)
                                                    }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <Label :for="`req-desc-${costIndex}-${reqIndex}`" class="text-xs">Description</Label>
                                            <Textarea
                                                :id="`req-desc-${costIndex}-${reqIndex}`"
                                                v-model="req.description"
                                                rows="1"
                                                class="text-sm min-h-8"
                                                :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.description`) }"
                                            />
                                            <p v-if="getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.description`)"
                                               class="text-xs text-destructive">
                                                {{ getErrorPath(`costs.${costIndex}.requirements.${reqIndex}.description`)
                                                }}</p>
                                        </div>

                                        <div class="flex items-center">
                                            <div class="flex items-center space-x-2">
                                                <Checkbox
                                                    :id="`req-required-${costIndex}-${reqIndex}`"
                                                    v-model:checked="req.is_required"
                                                />
                                                <Label :for="`req-required-${costIndex}-${reqIndex}`" class="text-xs">
                                                    Obligatoire
                                                </Label>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="cost.requirements.length === 0"
                                         class="text-sm text-muted-foreground italic text-center py-2">
                                        Aucune exigence ajoutée
                                    </div>
                                </div>

                                <!-- Reimbursement Rates -->
                                <div class="mt-4 space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-md font-medium">Taux de remboursement</h4>
                                        <Button variant="outline" size="sm" @click="addReimbursementRate(costIndex)"
                                                class="flex items-center">
                                            <PlusIcon class="h-4 w-4 mr-1" />
                                            Ajouter un taux
                                        </Button>
                                    </div>

                                    <div v-for="(rate, rateIndex) in cost.reimbursement_rates" :key="rateIndex"
                                         class="p-3 border rounded-md space-y-3">
                                        <div class="flex justify-between items-center">
                                            <h5 class="text-sm font-medium">Taux #{{ rateIndex + 1 }}</h5>
                                            <Button variant="ghost" size="icon"
                                                    @click="removeReimbursementRate(costIndex, rateIndex)"
                                                    class="text-destructive h-8 w-8">
                                                <Trash2Icon class="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div class="space-y-1">
                                                <Label :for="`rate-start-${costIndex}-${rateIndex}`" class="text-xs">Date
                                                    de début</Label>
                                                <Input
                                                    :id="`rate-start-${costIndex}-${rateIndex}`"
                                                    v-model="rate.start_date"
                                                    type="date"
                                                    class="h-8 text-sm"
                                                    :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.start_date`) }"
                                                />
                                                <p v-if="getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.start_date`)"
                                                   class="text-xs text-destructive">
                                                    {{ getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.start_date`)
                                                    }}</p>
                                            </div>

                                            <div class="space-y-1">
                                                <Label :for="`rate-end-${costIndex}-${rateIndex}`" class="text-xs">Date
                                                    de fin</Label>
                                                <Input
                                                    :id="`rate-end-${costIndex}-${rateIndex}`"
                                                    v-model="rate.end_date"
                                                    type="date"
                                                    class="h-8 text-sm"
                                                    :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.end_date`) }"
                                                />
                                                <p v-if="getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.end_date`)"
                                                   class="text-xs text-destructive">
                                                    {{ getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.end_date`)
                                                    }}</p>
                                            </div>

                                            <div class="space-y-1">
                                                <Label :for="`rate-value-${costIndex}-${rateIndex}`" class="text-xs">Valeur</Label>
                                                <div class="relative">
                        <span
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                          {{
                                cost.type === 'percentage'
                                    ? '%'
                                    : cost.type === 'km'
                                        ? '€/km'
                                        : '€'
                            }}
                        </span>
                                                    <Input
                                                        :id="`rate-value-${costIndex}-${rateIndex}`"
                                                        v-model.number="rate.value"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="pl-16 h-8 text-sm"
                                                        :class="{ 'border-destructive': getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.value`) }"
                                                    />
                                                </div>
                                                <p v-if="getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.value`)"
                                                   class="text-xs text-destructive">
                                                    {{ getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.value`)
                                                    }}</p>
                                            </div>
                                        </div>

                                        <p v-if="getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.overlap`)"
                                           class="text-xs text-destructive">
                                            {{ getErrorPath(`costs.${costIndex}.reimbursement_rates.${rateIndex}.overlap`)
                                            }}
                                        </p>
                                    </div>

                                    <div v-if="cost.reimbursement_rates.length === 0"
                                         class="text-sm text-muted-foreground italic text-center py-2">
                                        Aucun taux de remboursement ajouté
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.costs.length === 0"
                                 class="text-center py-8 border border-dashed rounded-lg">
                                <p class="text-muted-foreground">Aucun coût ajouté</p>
                            </div>

                            <Button type="button" variant="outline" @click="addCost" class="flex items-center">
                                <PlusIcon class="h-5 w-5 mr-2" />
                                Ajouter un coût
                            </Button>
                        </div>

                        <!-- Form Submission -->
                        <div class="pt-4 border-t">
                            <div class="flex justify-end space-x-3">
                                <Button type="button" variant="outline">
                                    Annuler
                                </Button>
                                <Button type="submit" :disabled="isSubmitting">
                                    <Loader2Icon v-if="isSubmitting" class="animate-spin mr-2 h-4 w-4" />
                                    {{ isSubmitting ? 'Envoi en cours...' : 'Enregistrer le formulaire' }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Trash2Icon,
    PlusIcon,
    Loader2Icon
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

// Form state
const form = reactive({
    name: '',
    description: '',
    costs: []
});

// UI state
const errors = reactive({});
const isSubmitting = ref(false);

// Helper function to get nested error path
const getErrorPath = (path) => {
    const keys = path.split('.');
    let current = errors;

    for (const key of keys) {
        if (!current[key]) return null;
        current = current[key];
    }

    return current;
};

// Add a new cost
const addCost = () => {
    form.costs.push({
        name: '',
        description: '',
        type: '',
        requirements: [],
        reimbursement_rates: []
    });
};

// Remove a cost
const removeCost = (index) => {
    form.costs.splice(index, 1);
};

// Add a requirement to a cost
const addRequirement = (costIndex) => {
    form.costs[costIndex].requirements.push({
        name: '',
        description: '',
        type: '',
        is_required: false
    });
};

// Remove a requirement from a cost
const removeRequirement = (costIndex, reqIndex) => {
    form.costs[costIndex].requirements.splice(reqIndex, 1);
};

// Add a reimbursement rate to a cost
const addReimbursementRate = (costIndex) => {
    form.costs[costIndex].reimbursement_rates.push({
        start_date: '',
        end_date: '',
        value: 0
    });
};

// Remove a reimbursement rate from a cost
const removeReimbursementRate = (costIndex, rateIndex) => {
    form.costs[costIndex].reimbursement_rates.splice(rateIndex, 1);
};

// Check for overlapping date ranges in reimbursement rates
const checkOverlappingDateRanges = (costIndex) => {
    const rates = form.costs[costIndex].reimbursement_rates;
    let hasOverlap = false;

    for (let i = 0; i < rates.length; i++) {
        const rate1 = rates[i];
        if (!rate1.start_date || !rate1.end_date) continue;

        const start1 = new Date(rate1.start_date);
        const end1 = new Date(rate1.end_date);

        for (let j = i + 1; j < rates.length; j++) {
            const rate2 = rates[j];
            if (!rate2.start_date || !rate2.end_date) continue;

            const start2 = new Date(rate2.start_date);
            const end2 = new Date(rate2.end_date);

            // Check for overlap
            if (start1 <= end2 && start2 <= end1) {
                if (!errors.costs) errors.costs = [];
                if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
                if (!errors.costs[costIndex].reimbursement_rates) errors.costs[costIndex].reimbursement_rates = [];
                if (!errors.costs[costIndex].reimbursement_rates[i]) errors.costs[costIndex].reimbursement_rates[i] = {};
                if (!errors.costs[costIndex].reimbursement_rates[j]) errors.costs[costIndex].reimbursement_rates[j] = {};

                errors.costs[costIndex].reimbursement_rates[i].overlap = 'Chevauchement avec une autre plage de dates';
                errors.costs[costIndex].reimbursement_rates[j].overlap = 'Chevauchement avec une autre plage de dates';

                hasOverlap = true;
            }
        }
    }

    return !hasOverlap;
};

// Validate the form
const validateForm = () => {
    // Reset errors
    Object.keys(errors).forEach(key => delete errors[key]);

    let isValid = true;

    // Validate basic form fields
    if (!form.name.trim()) {
        errors.name = 'Le nom du formulaire est requis';
        isValid = false;
    }

    if (!form.description.trim()) {
        errors.description = 'La description du formulaire est requise';
        isValid = false;
    }

    // Validate costs
    form.costs.forEach((cost, costIndex) => {
        if (!cost.name.trim()) {
            if (!errors.costs) errors.costs = [];
            if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
            errors.costs[costIndex].name = 'Le nom du coût est requis';
            isValid = false;
        }

        if (!cost.type) {
            if (!errors.costs) errors.costs = [];
            if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
            errors.costs[costIndex].type = 'Le type du coût est requis';
            isValid = false;
        }

        // Validate requirements
        cost.requirements.forEach((req, reqIndex) => {
            if (!req.name.trim()) {
                if (!errors.costs) errors.costs = [];
                if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
                if (!errors.costs[costIndex].requirements) errors.costs[costIndex].requirements = [];
                if (!errors.costs[costIndex].requirements[reqIndex]) errors.costs[costIndex].requirements[reqIndex] = {};
                errors.costs[costIndex].requirements[reqIndex].name = 'Le nom de l\'exigence est requis';
                isValid = false;
            }

            if (!req.type) {
                if (!errors.costs) errors.costs = [];
                if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
                if (!errors.costs[costIndex].requirements) errors.costs[costIndex].requirements = [];
                if (!errors.costs[costIndex].requirements[reqIndex]) errors.costs[costIndex].requirements[reqIndex] = {};
                errors.costs[costIndex].requirements[reqIndex].type = 'Le type de l\'exigence est requis';
                isValid = false;
            }
        });

        // Validate reimbursement rates
        cost.reimbursement_rates.forEach((rate, rateIndex) => {
            if (!rate.start_date) {
                if (!errors.costs) errors.costs = [];
                if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
                if (!errors.costs[costIndex].reimbursement_rates) errors.costs[costIndex].reimbursement_rates = [];
                if (!errors.costs[costIndex].reimbursement_rates[rateIndex]) errors.costs[costIndex].reimbursement_rates[rateIndex] = {};
                errors.costs[costIndex].reimbursement_rates[rateIndex].start_date = 'La date de début est requise';
                isValid = false;
            }

            if (!rate.end_date) {
                if (!errors.costs) errors.costs = [];
                if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
                if (!errors.costs[costIndex].reimbursement_rates) errors.costs[costIndex].reimbursement_rates = [];
                if (!errors.costs[costIndex].reimbursement_rates[rateIndex]) errors.costs[costIndex].reimbursement_rates[rateIndex] = {};
                errors.costs[costIndex].reimbursement_rates[rateIndex].end_date = 'La date de fin est requise';
                isValid = false;
            }

            if (rate.value <= 0) {
                if (!errors.costs) errors.costs = [];
                if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
                if (!errors.costs[costIndex].reimbursement_rates) errors.costs[costIndex].reimbursement_rates = [];
                if (!errors.costs[costIndex].reimbursement_rates[rateIndex]) errors.costs[costIndex].reimbursement_rates[rateIndex] = {};
                errors.costs[costIndex].reimbursement_rates[rateIndex].value = 'La valeur doit être supérieure à 0';
                isValid = false;
            }

            if (rate.start_date && rate.end_date) {
                const start = new Date(rate.start_date);
                const end = new Date(rate.end_date);

                if (start > end) {
                    if (!errors.costs) errors.costs = [];
                    if (!errors.costs[costIndex]) errors.costs[costIndex] = {};
                    if (!errors.costs[costIndex].reimbursement_rates) errors.costs[costIndex].reimbursement_rates = [];
                    if (!errors.costs[costIndex].reimbursement_rates[rateIndex]) errors.costs[costIndex].reimbursement_rates[rateIndex] = {};
                    errors.costs[costIndex].reimbursement_rates[rateIndex].end_date = 'La date de fin doit être postérieure à la date de début';
                    isValid = false;
                }
            }
        });

        // Check for overlapping date ranges
        if (!checkOverlappingDateRanges(costIndex)) {
            isValid = false;
        }
    });

    return isValid;
};

// Submit the form
const submitForm = async () => {
    if (!validateForm()) {
        return;
    }

    isSubmitting.value = true;

    try {
        // API call to Laravel backend
        const response = await fetch('/api/forms', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(form)
        });

        const data = await response.json();

        if (!response.ok) {
            // Handle validation errors from the server
            if (response.status === 422 && data.errors) {
                Object.assign(errors, data.errors);
                throw new Error('Validation failed');
            }

            throw new Error(data.message || 'Une erreur est survenue');
        }

        // Success - redirect or show success message
        alert('Formulaire enregistré avec succès!');
        // Optionally redirect: window.location.href = '/forms'
    } catch (error) {
        console.error('Error submitting form:', error);
        // Show error message to user
        alert(`Erreur: ${error.message || 'Une erreur est survenue lors de l\'enregistrement du formulaire'}`);
    } finally {
        isSubmitting.value = false;
    }
};
</script>
