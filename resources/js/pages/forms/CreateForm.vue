<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Créer un formulaire" />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- En-tête -->
            <div class="space-y-1">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Créer un formulaire</h2>
                <p class="text-sm text-muted-foreground">
                    Définissez le formulaire, puis ajoutez ses coûts (taux de remboursement et prérequis) un par un.
                </p>
            </div>

            <form @submit.prevent="submitForm" class="max-w-3xl space-y-6">
                <!-- Informations générales -->
                <div class="rounded-xl border bg-card shadow-sm p-5 sm:p-8 space-y-6">
                    <div class="space-y-2">
                        <Label for="name">Nom du formulaire</Label>
                        <Input id="name" v-model="form.name" placeholder="Nom du formulaire"
                            :class="form.errors.name ? 'border-destructive focus-visible:ring-destructive' : ''" />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="description">Description</Label>
                        <Textarea id="description" v-model="form.description" rows="3"
                            :class="form.errors.description ? 'border-destructive focus-visible:ring-destructive' : ''" />
                        <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                    </div>
                </div>

                <!-- Coûts -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-base sm:text-lg font-semibold">Coûts</h3>
                            <p class="text-xs text-muted-foreground">Chaque coût a son type, ses taux et ses prérequis.</p>
                        </div>
                        <Button type="button" variant="outline" size="sm" @click="openAddCost">
                            <PlusIcon class="h-4 w-4 mr-2" />
                            Ajouter un coût
                        </Button>
                    </div>

                    <p v-if="form.errors.costs" class="text-sm text-destructive">{{ form.errors.costs }}</p>

                    <!-- Liste des coûts -->
                    <div v-if="form.costs.length" class="space-y-3">
                        <div
                            v-for="(cost, index) in form.costs"
                            :key="index"
                            class="rounded-lg border bg-card p-4 transition-colors"
                            :class="hasCostError(index) ? 'border-destructive' : ''"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <CoinsIcon class="h-4 w-4 text-muted-foreground shrink-0" />
                                        <span class="font-medium text-sm truncate">{{ cost.name || 'Coût sans nom' }}</span>
                                        <Badge variant="secondary" class="text-[10px]">{{ typeLabel(cost.type) }}</Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ cost.reimbursement_rates.length }} taux · {{ cost.requirements.length }} prérequis
                                    </p>
                                    <p v-if="hasCostError(index)" class="text-xs text-destructive">Contient des erreurs à corriger.</p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <Button type="button" variant="ghost" size="icon" class="h-8 w-8" @click="openEditCost(index)">
                                        <PencilIcon class="h-4 w-4" />
                                    </Button>
                                    <Button type="button" variant="ghost" size="icon" class="h-8 w-8 text-destructive" @click="removeCost(index)">
                                        <Trash2Icon class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- État vide -->
                    <button
                        v-else
                        type="button"
                        @click="openAddCost"
                        class="w-full rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground hover:bg-muted/50 hover:text-foreground transition-colors"
                    >
                        <CoinsIcon class="mx-auto h-6 w-6 mb-1 opacity-60" />
                        Aucun coût — cliquez pour en ajouter un
                    </button>
                </div>

                <!-- Boutons d'action -->
                <div class="flex flex-col-reverse xs:flex-row items-stretch xs:items-center gap-3">
                    <Button type="submit" :disabled="form.processing" class="w-full xs:w-auto">
                        <Loader2Icon v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Envoi en cours...' : 'Enregistrer' }}
                    </Button>
                    <Button type="button" variant="outline" @click="cancel" class="w-full xs:w-auto">
                        Annuler
                    </Button>
                </div>
            </form>
        </div>

        <!-- Modale de configuration d'un coût -->
        <Dialog v-model:open="showCostModal">
            <DialogContent class="max-w-[90vw] sm:max-w-2xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>{{ editingIndex === null ? 'Ajouter un coût' : 'Modifier le coût' }}</DialogTitle>
                    <DialogDescription>
                        Renseignez le coût, ses taux de remboursement et ses prérequis.
                    </DialogDescription>
                </DialogHeader>

                <div class="overflow-y-auto -mx-1 px-1 space-y-6 flex-1">
                    <!-- Infos coût -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="cost-name">Nom</Label>
                            <Input id="cost-name" v-model="costDraft.name" placeholder="Nom du coût" />
                        </div>
                        <div class="space-y-2">
                            <Label for="cost-type">Type</Label>
                            <Select v-model="costDraft.type">
                                <SelectTrigger id="cost-type">
                                    <SelectValue placeholder="Sélectionner un type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="km">Kilométrage</SelectItem>
                                    <SelectItem value="fixed">Fixe</SelectItem>
                                    <SelectItem value="percentage">Pourcentage</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="cost-description">Description</Label>
                            <Input id="cost-description" v-model="costDraft.description" placeholder="Description du coût" />
                        </div>
                    </div>

                    <!-- Taux de remboursement -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium">Taux de remboursement</h4>
                            <Button type="button" variant="outline" size="sm" @click="addRate">
                                <PlusIcon class="mr-1 h-4 w-4" />
                                Ajouter
                            </Button>
                        </div>

                        <div
                            v-for="rate in sortedRates"
                            :key="rate._key"
                            class="rounded-md border p-3 space-y-3"
                        >
                            <div class="grid gap-3" :class="costDraft.type === 'km' ? 'grid-cols-1 sm:grid-cols-4' : 'grid-cols-1 sm:grid-cols-3'">
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Date de début</Label>
                                    <Input type="date" v-model="rate.start_date" />
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Date de fin</Label>
                                    <Input type="date" v-model="rate.end_date" />
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Valeur</Label>
                                    <Input type="number" v-model.number="rate.value" step="0.0001" />
                                </div>
                                <div v-if="costDraft.type === 'km'" class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Transport</Label>
                                    <Select v-model="rate.transport">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Transport" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="car">Voiture</SelectItem>
                                            <SelectItem value="bike">Vélo</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <Button type="button" variant="ghost" size="sm" class="text-destructive h-8" @click="removeRate(rate)">
                                    <Trash2Icon class="h-4 w-4 mr-1" />
                                    Retirer
                                </Button>
                            </div>
                        </div>
                        <p v-if="costDraft.reimbursement_rates.length === 0" class="text-xs text-muted-foreground">Aucun taux.</p>
                    </div>

                    <!-- Prérequis -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium">Prérequis</h4>
                            <Button type="button" variant="outline" size="sm" @click="addRequirement">
                                <PlusIcon class="mr-1 h-4 w-4" />
                                Ajouter
                            </Button>
                        </div>

                        <div
                            v-for="(requirement, reqIndex) in costDraft.requirements"
                            :key="reqIndex"
                            class="rounded-md border p-3 space-y-3"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Nom du prérequis</Label>
                                    <Input type="text" v-model="requirement.name" placeholder="Nom du prérequis" />
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Type</Label>
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
                            </div>
                            <div class="flex justify-end">
                                <Button type="button" variant="ghost" size="sm" class="text-destructive h-8" @click="removeRequirement(reqIndex)">
                                    <Trash2Icon class="h-4 w-4 mr-1" />
                                    Retirer
                                </Button>
                            </div>
                        </div>
                        <p v-if="costDraft.requirements.length === 0" class="text-xs text-muted-foreground">Aucun prérequis.</p>
                    </div>
                </div>

                <DialogFooter class="flex-col xs:flex-row gap-2">
                    <Button type="button" variant="outline" @click="showCostModal = false" class="w-full xs:w-auto">
                        Annuler
                    </Button>
                    <Button type="button" @click="applyCost" :disabled="!costDraftValid" class="w-full xs:w-auto">
                        {{ editingIndex === null ? 'Ajouter le coût' : 'Enregistrer le coût' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Loader2Icon, PlusIcon, Trash2Icon, PencilIcon, CoinsIcon } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    { title: 'Formulaires', href: '/forms' },
    { title: 'Créer un formulaire', href: '/forms/create' },
];

const form = useForm({
    name: '',
    description: '',
    costs: [],
});

const typeLabel = (type) => {
    switch (type) {
        case 'km': return 'Kilométrage';
        case 'fixed': return 'Fixe';
        case 'percentage': return 'Pourcentage';
        default: return 'Type non défini';
    }
};

const hasCostError = (index) =>
    Object.keys(form.errors).some((key) => key.startsWith(`costs.${index}.`));

// === Modale coût ===
const showCostModal = ref(false);
const editingIndex = ref(null);

const blankCost = () => ({
    name: '',
    description: '',
    type: '',
    reimbursement_rates: [],
    requirements: [],
});

const costDraft = ref(blankCost());

const clone = (value) => JSON.parse(JSON.stringify(value));

// Clé locale stable pour conserver le bon objet malgré le tri d'affichage
let rateKeyCounter = 0;
const nextRateKey = () => ++rateKeyCounter;

// Taux triés par date de début décroissante (plus récente en haut, lignes vides en tête)
const sortedRates = computed(() =>
    [...costDraft.value.reimbursement_rates].sort((a, b) => {
        if (!a.start_date && !b.start_date) return 0;
        if (!a.start_date) return -1;
        if (!b.start_date) return 1;
        return b.start_date.localeCompare(a.start_date);
    })
);

const openAddCost = () => {
    editingIndex.value = null;
    costDraft.value = blankCost();
    showCostModal.value = true;
};

const openEditCost = (index) => {
    editingIndex.value = index;
    const draft = clone(form.costs[index]);
    draft.reimbursement_rates.forEach((rate) => {
        rate._key = nextRateKey();
    });
    costDraft.value = draft;
    showCostModal.value = true;
};

const removeCost = (index) => {
    form.costs.splice(index, 1);
};

const addRate = () => {
    costDraft.value.reimbursement_rates.unshift({
        start_date: '',
        end_date: '',
        value: 0,
        transport: costDraft.value.type === 'km' ? 'car' : null,
        _key: nextRateKey(),
    });
};

const removeRate = (rate) => {
    const index = costDraft.value.reimbursement_rates.indexOf(rate);
    if (index !== -1) {
        costDraft.value.reimbursement_rates.splice(index, 1);
    }
};

const addRequirement = () => {
    costDraft.value.requirements.push({ name: '', type: '' });
};

const removeRequirement = (reqIndex) => {
    costDraft.value.requirements.splice(reqIndex, 1);
};

const costDraftValid = computed(() => {
    const c = costDraft.value;
    if (!c.name.trim() || !c.type || !c.description.trim()) {
        return false;
    }
    const ratesOk = c.reimbursement_rates.every(
        (r) => r.start_date && r.value !== '' && r.value !== null && !Number.isNaN(Number(r.value))
    );
    const reqsOk = c.requirements.every((r) => r.name.trim() && r.type);
    return ratesOk && reqsOk;
});

const applyCost = () => {
    if (!costDraftValid.value) {
        return;
    }
    const payload = clone(costDraft.value);
    payload.reimbursement_rates = payload.reimbursement_rates.map(({ _key, ...rate }) => rate);
    if (editingIndex.value === null) {
        form.costs.push(payload);
    } else {
        form.costs.splice(editingIndex.value, 1, payload);
    }
    showCostModal.value = false;
};

const submitForm = () => {
    form.post('/forms', {
        onSuccess: () => {
            form.reset();
        },
    });
};

const cancel = () => {
    router.visit(route('forms.index'));
};
</script>
