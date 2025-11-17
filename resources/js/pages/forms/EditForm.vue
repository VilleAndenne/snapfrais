<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Modifier le formulaire" />

        <div class="container p-4">
            <Card class="w-full">
                <CardHeader>
                    <CardTitle>Modifier le formulaire</CardTitle>
                    <CardDescription>
                        Modifiez le formulaire existant.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submitForm" class="space-y-8">
                        <!-- Form Basic Information -->
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Nom du formulaire</Label>
                                <Input id="name" v-model="form.name"
                                       :class="{ 'border-destructive': form.errors?.name }" />
                                <p v-if="form.errors?.name" class="text-sm text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">,,
                                <Label for="description">Description</Label>
                                <Textarea id="description" v-model="form.description" rows="3"
                                          :class="{ 'border-destructive': form.errors?.description }" />
                                <p v-if="form.errors?.description" class="text-sm text-destructive">
                                    {{ form.errors.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Form Costs Section -->
                        <div class="space-y-6">
                            <h2 class="text-xl font-semibold">Coûts</h2>

                            <div v-for="(cost, costIndex) in form.costs" :key="costIndex"
                                 class="p-4 border rounded-lg space-y-4">
                                <!-- ID caché pour la mise à jour -->
                                <input type="hidden" v-model="cost.id" />

                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-medium">Coût #{{ costIndex + 1 }}</h3>
                                    <Button variant="ghost" type="button" size="icon"
                                            @click="removeCost(costIndex)"
                                            class="text-destructive">
                                        <Trash2Icon class="h-5 w-5" />
                                    </Button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label :for="`cost-name-${costIndex}`">Nom</Label>
                                        <Input :id="`cost-name-${costIndex}`" v-model="cost.name"
                                               :class="{ 'border-destructive': form.errors?.[`costs.${costIndex}.name`] }" />
                                        <p v-if="form.errors?.[`costs.${costIndex}.name`]">
                                            {{ form.errors[`costs.${costIndex}.name`] }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`cost-type-${costIndex}`">Type</Label>
                                        <Select v-model="cost.type" @update:modelValue="onTypeChange(cost)">
                                            <SelectTrigger :id="`cost-type-${costIndex}`">
                                                <SelectValue placeholder="Sélectionner un type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="km">Kilométrage</SelectItem>
                                                <SelectItem value="fixed">Fixe</SelectItem>
                                                <SelectItem value="percentage">Pourcentage</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <!-- Description du coût -->
                                <div class="space-y-2">
                                    <Label :for="`cost-description-${costIndex}`">Description</Label>
                                    <Textarea :id="`cost-description-${costIndex}`" v-model="cost.description"
                                              rows="3"
                                              :class="{ 'border-destructive': form.errors?.[`costs.${costIndex}.description`] }" />
                                    <p v-if="form.errors?.[`costs.${costIndex}.description`]">
                                        {{ form.errors[`costs.${costIndex}.description`] }}
                                    </p>
                                </div>

                                <!-- Reimbursement Rates -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-md font-medium">Taux de remboursement</h4>
                                        <Button type="button" @click="addReimbursementRate(costIndex)" variant="outline"
                                                size="sm">
                                            <PlusIcon class="h-4 w-4 mr-1" />
                                            Ajouter un taux
                                        </Button>
                                    </div>

                                    <div v-for="(rate, rateIndex) in cost.reimbursement_rates" :key="rateIndex"
                                         class="p-3 border rounded-md space-y-3">
                                        <!-- ID caché -->
                                        <input type="hidden" v-model="rate.id" />

                                        <div class="grid gap-4" :class="cost.type === 'km' ? 'grid-cols-4' : 'grid-cols-3'">
                                            <Input type="date" v-model="rate.start_date" />
                                            <Input type="date" v-model="rate.end_date" />
                                            <Input type="number" v-model.number="rate.value" step="0.0001" />

                                            <!-- Transport uniquement pour type km -->
                                            <template v-if="cost.type === 'km'">
                                                <Select v-model="rate.transport">
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Transport" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="car">Voiture</SelectItem>
                                                        <SelectItem value="bike">Vélo</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </template>
                                        </div>

                                        <Button type="button" @click="removeReimbursementRate(costIndex, rateIndex)"
                                                class="text-destructive">
                                            <Trash2Icon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Requirements -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-md font-medium">Prérequis du coût</h4>
                                        <Button type="button" @click="addRequirement(costIndex)" variant="outline"
                                                size="sm">
                                            <PlusIcon class="h-4 w-4 mr-1" />
                                            Ajouter un prérequis
                                        </Button>
                                    </div>

                                    <div v-for="(requirement, requirementIndex) in cost.requirements"
                                         :key="requirementIndex" class="p-3 border rounded-md space-y-3">
                                        <!-- ID caché -->
                                        <input type="hidden" v-model="requirement.id" />

                                        <div class="grid grid-cols-2 gap-4">
                                            <Input type="text" v-model="requirement.name" />
                                            <Select v-model="requirement.type">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Sélectionner un type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="file">Fichier</SelectItem>
                                                    <SelectItem value="text">Texte</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <Button type="button" @click="removeRequirement(costIndex, requirementIndex)"
                                                class="text-destructive">
                                            <Trash2Icon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <Button @click="addCost" type="button" variant="outline">
                                <PlusIcon class="h-5 w-5 mr-2" />
                                Ajouter un coût
                            </Button>
                        </div>

                        <!-- Form Submission -->
                        <div class="pt-4 border-t">
                            <div class="flex justify-end space-x-2 items-center">
                                <Link :href="route('forms.index')" type="button">
                                    Annuler
                                </Link>
                                <Button type="submit" :disabled="form.processing">
                                    <Loader2Icon v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                                    Enregistrer
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Card, CardHeader, CardTitle, CardDescription, CardContent
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/components/ui/select';
import { Trash2Icon, PlusIcon, Loader2Icon } from 'lucide-vue-next';

const props = defineProps({
    form: Object
});

const breadcrumbs = [
    { title: 'Formulaires', href: '/forms' },
    { title: 'Modifier le formulaire', href: '/forms/' + props.form.id + '/edit' }
];

// Initialise le formulaire à partir des props
const form = useForm(props.form);

// 🔧 Normaliser les données entrantes pour garantir rate.transport sur les coûts km
if (Array.isArray(form.costs)) {
    form.costs.forEach((cost) => {
        cost.reimbursement_rates = cost.reimbursement_rates || [];
        // Si km : s'assurer que chaque taux possède un transport
        if (cost.type === 'km') {
            cost.reimbursement_rates = cost.reimbursement_rates.map((rate) => ({
                ...rate,
                transport: rate.transport ?? 'car'
            }));
        } else {
            // côté non-km, on laisse tel quel (le backend ignore transport)
            cost.reimbursement_rates = cost.reimbursement_rates.map((rate) => ({
                ...rate,
                transport: rate.transport ?? null
            }));
        }
        cost.requirements = cost.requirements || [];
    });
}

const removeCost = (index) => {
    form.costs.splice(index, 1);
};

const onTypeChange = (cost) => {
    // Si type = km et qu'il manque transport sur certains taux → défaut 'car'
    if (cost.type === 'km') {
        cost.reimbursement_rates = (cost.reimbursement_rates || []).map((rate) => ({
            ...rate,
            transport: rate.transport ?? 'car'
        }));
    }
};

const addReimbursementRate = (costIndex) => {
    const isKm = form.costs[costIndex]?.type === 'km';
    form.costs[costIndex].reimbursement_rates.push({
        start_date: '',
        end_date: '',
        value: 0,
        transport: isKm ? 'car' : null
    });
};

const removeReimbursementRate = (costIndex, rateIndex) => {
    form.costs[costIndex].reimbursement_rates.splice(rateIndex, 1);
};

const addRequirement = (costIndex) => {
    form.costs[costIndex].requirements.push({
        name: '',
        type: ''
    });
};

const addCost = () => {
    form.costs.push({
        name: '',
        description: '',
        type: '',
        reimbursement_rates: [],
        requirements: []
    });
};

const removeRequirement = (costIndex, requirementIndex) => {
    form.costs[costIndex].requirements.splice(requirementIndex, 1);
};

const submitForm = () => {
    form.put(route('forms.update', props.form.id), {});
};
</script>
