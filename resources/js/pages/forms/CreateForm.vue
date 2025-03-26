<template>
    <AppLayout :breadcrumbs="breadcrumbs">

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
                                <Input id="name" v-model="form.name"
                                       :class="{ 'border-destructive': form.errors.name }" />
                                <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea id="description" v-model="form.description" rows="3"
                                          :class="{ 'border-destructive': form.errors.description }" />
                                <p v-if="form.errors.description" class="text-sm text-destructive">{{
                                        form.errors.description }}</p>
                            </div>
                        </div>

                        <!-- Form Costs Section -->
                        <div class="space-y-6">
                            <h2 class="text-xl font-semibold">Coûts</h2>

                            <div v-for="(cost, costIndex) in form.costs" :key="costIndex"
                                 class="p-4 border rounded-lg space-y-4">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-medium">Coût #{{ costIndex + 1 }}</h3>
                                    <Button variant="ghost" type="button" size="icon" @click="removeCost(costIndex)"
                                            class="text-destructive">
                                        <Trash2Icon class="h-5 w-5" />
                                    </Button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label :for="`cost-name-${costIndex}`">Nom</Label>
                                        <Input :id="`cost-name-${costIndex}`" v-model="cost.name"
                                               :class="{ 'border-destructive': form.errors[`costs.${costIndex}.name`] }" />
                                        <p v-if="form.errors[`costs.${costIndex}.name`]"
                                           class="text-sm text-destructive">
                                            {{ form.errors[`costs.${costIndex}.name`] }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label :for="`cost-type-${costIndex}`">Type</Label>
                                        <Select v-model="cost.type">
                                            <SelectTrigger :id="`cost-type-${costIndex}`"
                                                           :class="{ 'border-destructive': form.errors[`costs.${costIndex}.type`] }">
                                                <SelectValue placeholder="Sélectionner un type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="km">Kilométrage</SelectItem>
                                                <SelectItem value="fixed">Fixe</SelectItem>
                                                <SelectItem value="percentage">Pourcentage</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors[`costs.${costIndex}.type`]"
                                           class="text-sm text-destructive">
                                            {{ form.errors[`costs.${costIndex}.type`] }}
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label :for="`cost-description-${costIndex}`">Description</Label>
                                        <Input :id="`cost-description-${costIndex}`" v-model="cost.description"
                                               :class="{ 'border-destructive': form.errors[`costs.${costIndex}.description`] }" />
                                        <p v-if="form.errors[`costs.${costIndex}.description`]"
                                           class="text-sm text-destructive">
                                            {{ form.errors[`costs.${costIndex}.description`] }}
                                        </p>
                                    </div>


                                </div>

                                <!-- Reimbursement Rates -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-md font-medium">Taux de remboursement</h4>
                                        <Button type="button" variant="outline" size="sm"
                                                @click="addReimbursementRate(costIndex)">
                                            <PlusIcon class="h-4 w-4 mr-1" />
                                            Ajouter un taux
                                        </Button>
                                    </div>

                                    <div v-for="(rate, rateIndex) in cost.reimbursement_rates" :key="rateIndex"
                                         class="p-3 border rounded-md space-y-3">
                                        <div class="grid grid-cols-3 gap-4">
                                            <Input type="date" v-model="rate.start_date"
                                                   placeholder="Date de début" />
                                            <Input type="date" v-model="rate.end_date" placeholder="Date de fin" />
                                            <Input type="number" v-model.number="rate.value" placeholder="Valeur"
                                                   step="0.0001" />
                                        </div>
                                        <Button type="button" variant="ghost" size="icon"
                                                @click="removeReimbursementRate(costIndex, rateIndex)"
                                                class="text-destructive">
                                            <Trash2Icon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Requiermeents -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-md font-medium">Prérequis du coût</h4>
                                        <Button type="button" variant="outline" size="sm"
                                                @click="addRequierement(costIndex)">
                                            <PlusIcon class="h-4 w-4 mr-1" />
                                            Ajouter un prérequis
                                        </Button>
                                    </div>

                                    <div v-for="(requierement, requierementIndex) in cost.requierements"
                                         :key="requierementIndex" class="p-3 border rounded-md space-y-3">
                                        <div class="grid grid-cols-3 gap-4">
                                            <Input type="text" v-model="requierement.name"
                                                   placeholder="Nom du prérequis" />

                                            <Select v-model="requierement.type">
                                                <SelectTrigger
                                                    :id="`requierement-type-${costIndex}-${requierementIndex}`"
                                                    :class="{ 'border-destructive': form.errors[`costs.${costIndex}.requierements.${requierementIndex}.type`] }">
                                                    <SelectValue placeholder="Sélectionner un type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="file">Fichier</SelectItem>
                                                    <SelectItem value="text">Texte</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors[`costs.${costIndex}.requierements.${requierementIndex}.type`]"
                                               class="text-sm text-destructive">
                                                {{
                                                    form.errors[`costs.${costIndex}.requierements.${requierementIndex}.type`]
                                                }}
                                            </p>
                                        </div>

                                        <Button type="button" variant="ghost" size="icon"
                                                @click="removeRequierement(costIndex, requierementIndex)"
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
                            <div class="flex justify-end space-x-3">
                                <Button type="button" variant="outline">
                                    Annuler
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    <Loader2Icon v-if="form.processing" class="animate-spin mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Envoi en cours...' : 'Enregistrer' }}
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
import { useForm } from '@inertiajs/vue3';
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

const breadcrumbs = [
    {
        title: 'Formulaires',
        href: '/forms'
    },
    {
        title: 'Créer un formulaire',
        href: '/forms/create'
    }
];

const form = useForm({
    name: '',
    description: '',
    costs: []
});

const addCost = () => {
    form.costs.push({
        name: '',
        description: '',
        type: '',
        reimbursement_rates: [],
        requierements: []
    });
};

const removeCost = (index) => {
    form.costs.splice(index, 1);
};

const addReimbursementRate = (costIndex) => {
    form.costs[costIndex].reimbursement_rates.push({
        start_date: '',
        end_date: '',
        value: 0
    });
};

const removeReimbursementRate = (costIndex, rateIndex) => {
    form.costs[costIndex].reimbursement_rates.splice(rateIndex, 1);
};

const addRequierement = (costIndex) => {
    form.costs[costIndex].requierements.push({
        name: '',
        type: '' // ✅ Champ lié au v-model
    });
};

const removeRequierement = (costIndex, requierementIndex) => {
    form.costs[costIndex].requierements.splice(requierementIndex, 1);
};

const submitForm = () => {
    form.post('/forms', {
        onSuccess: () => {
            alert('Formulaire enregistré avec succès!');
            form.reset();
        },
        onError: (errors) => {
            console.error('Erreur lors de l\'envoi du formulaire', errors);
        }
    });
};
</script>
