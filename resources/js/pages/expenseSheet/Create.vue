<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nouvelle note de frais" />

        <div class="container mx-auto space-y-4 sm:space-y-6 p-3 sm:p-4">
            <h1 class="text-xl sm:text-2xl font-semibold text-foreground">Créer une note de frais</h1>

            <!-- Sélection du département -->
            <div class="flex flex-col space-y-2">
                <Label for="department">Département</Label>
                <Select v-model="form.department_id">
                    <SelectTrigger id="department">
                        <SelectValue placeholder="Sélectionner un département" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="dep in props.departments" :key="dep.id" :value="dep.id">
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
                        <SelectItem v-for="u in selectedDepartment?.users || []" :key="u.id" :value="u.id">
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
                    :id="`cost-card-${index}`"
                    class="relative space-y-3 sm:space-y-4 rounded border border-border bg-card p-3 sm:p-4 text-card-foreground"
                >
                    <!-- Boutons d'action -->
                    <div class="absolute right-2 top-2 flex gap-1 sm:gap-2">
                        <Button
                            v-if="canUseMultiDate(cost)"
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 sm:h-10 sm:w-10 text-primary"
                            title="Répartir ce coût sur plusieurs dates"
                            @click="openMultiDateDialog(index)"
                        >
                            <CalendarPlusIcon class="h-4 w-4 sm:h-5 sm:w-5" />
                        </Button>
                        <Button variant="ghost" size="icon" class="h-8 w-8 sm:h-10 sm:w-10 text-primary" @click="openDuplicateDialog(index)">
                            <CopyIcon class="h-4 w-4 sm:h-5 sm:w-5" />
                        </Button>
                        <Button variant="ghost" size="icon" class="h-8 w-8 sm:h-10 sm:w-10 text-destructive" @click="removeCost(index)">
                            <Trash2Icon class="h-4 w-4 sm:h-5 sm:w-5" />
                        </Button>
                    </div>

                    <!-- Détails du coût -->
                    <div class="flex flex-col xs:flex-row xs:items-center xs:justify-between gap-2 pr-20 xs:pr-24">
                        <h3 class="text-lg sm:text-xl font-bold">{{ cost.name }}</h3>
                        <span class="text-xs sm:text-sm italic text-muted-foreground">{{ cost.type }}</span>
                    </div>
                    <p class="text-xs sm:text-sm text-muted-foreground">{{ cost.description }}</p>

                    <!-- Champ de date -->
                    <div class="mt-2">
                        <Label for="cost-date" class="text-xs sm:text-sm">Date du coût</Label>
                        <input
                            type="date"
                            v-model="costData[index].date"
                            class="w-full rounded border border-border bg-background p-1.5 sm:p-2 text-sm sm:text-base text-foreground"
                            @change="updateRate(index, cost)"
                        />
                        <span v-if="form.errors[`costs.${index}.date`]" class="text-sm text-red-600">
                            {{ form.errors[`costs.${index}.date`] }}
                        </span>
                    </div>

                    <!-- Champs dynamiques selon le type de coût -->
                    <div v-if="cost.type === 'km'">
                        <KmCostInput
                            v-model="costData[index].kmData"
                            :travel_mode="getActiveTransport(cost, costData[index].date) === 'bike' ? 'BICYCLING' : 'DRIVING'"
                        />
                        <!-- Résumé remboursement km -->
                        <div class="mt-3 rounded border border-border bg-muted/40 p-2 sm:p-3">
                            <div class="flex flex-col gap-x-4 gap-y-1.5 sm:gap-y-2 text-xs sm:text-sm">
                                <span class="font-medium">Estimation</span>
                                <span
                                    >• Mode :
                                    {{
                                        getActiveTransport(cost, costData[index].date) === 'bike'
                                            ? 'Vélo'
                                            : getActiveTransport(cost, costData[index].date) === 'car'
                                              ? 'Voiture'
                                              : 'Autre'
                                    }}</span
                                >

                                <span>• Taux : {{ formatRate(getActiveRate(cost, costData[index].date)) }} / km</span>
                                <span>• Distance : {{ roundKm(costData[index]?.kmData?.totalKm) }} km</span>
                                <span>
                                    • Remboursé :
                                    <span class="font-semibold">
                                        {{ formatCurrency(kmReimbursed(index, cost)) }}
                                    </span>
                                </span>
                            </div>
                        </div>
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
                        :submitted="submitted"
                    />
                </div>
            </div>

            <!-- Coûts disponibles -->
            <div>
                <h2 class="mb-2 text-base sm:text-lg font-medium text-foreground">Types de coûts disponibles</h2>
                <p class="mb-3 sm:mb-4 text-xs sm:text-sm text-muted-foreground">Coûts ajoutés : {{ selectedCosts.length }}/30</p>
                <CostPicker :available-costs="costs" :selected-costs="selectedCosts" @add="addToRequest" />
            </div>

            <!-- Boutons d'envoi -->
            <div class="flex flex-col xs:flex-row justify-end gap-2 sm:gap-3 pt-6 sm:pt-8">
                <Button @click="saveDraft" :disabled="!selectedCosts.length || form.processing" variant="outline" class="w-full xs:w-auto">
                    <Loader2Icon v-if="form.processing && isDraftSubmit" class="mr-2 h-4 w-4 animate-spin" />
                    <span class="hidden sm:inline">{{ form.processing && isDraftSubmit ? 'Enregistrement...' : 'Enregistrer en brouillon' }}</span>
                    <span class="sm:hidden">{{ form.processing && isDraftSubmit ? 'Enregistrement...' : 'Brouillon' }}</span>
                </Button>
                <Button @click="submit" :disabled="!selectedCosts.length || form.processing" class="w-full xs:w-auto">
                    <Loader2Icon v-if="form.processing && !isDraftSubmit" class="mr-2 h-4 w-4 animate-spin" />
                    <span class="hidden sm:inline">{{ form.processing && !isDraftSubmit ? 'Envoi en cours...' : 'Envoyer la demande' }}</span>
                    <span class="sm:hidden">{{ form.processing && !isDraftSubmit ? 'Envoi...' : 'Envoyer' }}</span>
                </Button>
            </div>
        </div>

        <!-- Modal de duplication -->
        <Dialog v-model:open="duplicateDialog.isOpen">
            <DialogContent class="max-w-[90vw] sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-base sm:text-lg">Dupliquer le coût</DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm"> Combien de copies de "{{ duplicateDialog.costName }}" voulez-vous créer ? </DialogDescription>
                </DialogHeader>
                <div class="py-3 sm:py-4">
                    <Label for="duplicate-count" class="text-xs sm:text-sm">Nombre de copies</Label>
                    <Input id="duplicate-count" type="number" min="1" max="20" v-model.number="duplicateDialog.count" class="mt-2" />
                </div>
                <DialogFooter class="flex-col xs:flex-row gap-2">
                    <Button variant="outline" @click="duplicateDialog.isOpen = false" class="w-full xs:w-auto">Annuler</Button>
                    <Button @click="confirmDuplicate" class="w-full xs:w-auto">Dupliquer</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal de répartition sur plusieurs dates -->
        <Dialog v-model:open="multiDateDialog.isOpen">
            <DialogContent class="max-w-[90vw] sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="text-base sm:text-lg">Répartir sur plusieurs dates</DialogTitle>
                    <DialogDescription class="text-xs sm:text-sm">
                        Sélectionnez les dates auxquelles vous avez effectué "{{ multiDateDialog.costName }}". Une copie du coût sera créée pour chaque date.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-3 sm:py-4">
                    <!-- Navigation des mois -->
                    <div class="mb-3 flex items-center justify-between">
                        <Button type="button" variant="ghost" size="icon" class="h-8 w-8" @click="prevMonth">
                            <ChevronLeftIcon class="h-4 w-4" />
                        </Button>
                        <span class="text-sm font-medium capitalize">{{ calendarLabel }}</span>
                        <Button type="button" variant="ghost" size="icon" class="h-8 w-8" @click="nextMonth">
                            <ChevronRightIcon class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Jours de la semaine -->
                    <div class="mb-1 grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground">
                        <span v-for="wd in weekdays" :key="wd">{{ wd }}</span>
                    </div>

                    <!-- Grille des jours -->
                    <div class="grid grid-cols-7 gap-1">
                        <template v-for="(cell, i) in calendarCells" :key="i">
                            <div v-if="!cell" />
                            <button
                                v-else
                                type="button"
                                :class="[
                                    'flex h-9 items-center justify-center rounded text-sm transition-colors',
                                    isDateSelected(cell.iso) ? 'bg-primary font-semibold text-primary-foreground' : 'text-foreground hover:bg-muted',
                                ]"
                                @click="toggleDate(cell.iso)"
                            >
                                {{ cell.day }}
                            </button>
                        </template>
                    </div>

                    <p class="mt-3 text-xs text-muted-foreground">{{ multiDateDialog.dates.length }} date(s) sélectionnée(s)</p>
                </div>
                <DialogFooter class="flex-col xs:flex-row gap-2">
                    <Button variant="outline" @click="multiDateDialog.isOpen = false" class="w-full xs:w-auto">Annuler</Button>
                    <Button @click="confirmMultiDate" class="w-full xs:w-auto">Ajouter les coûts</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { CalendarPlusIcon, ChevronLeftIcon, ChevronRightIcon, CopyIcon, Loader2Icon, Trash2Icon } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

import CostPicker from '@/components/expense/CostPicker.vue';
import CostrequirementInput from '@/components/expense/CostRequirementInput.vue';
import FixedCostDisplay from '@/components/expense/FixedCostDisplay.vue';
import KmCostInput from '@/components/expense/KmCostInput.vue';
import PercentageCostInput from '@/components/expense/PercentageCostInput.vue';

const costs = ref([]);
const selectedCosts = ref([]);
const costData = ref([]);

const props = defineProps({
    form: { type: Object, required: true },
    departments: { type: Array, required: true }, // heads[] + users[]
    authUser: { type: Object, required: true }, // { id, name }
});

const form = useForm({
    costs: [],
    department_id: null,
    target_user_id: null, // agent pour qui la note est encodée (si responsable)
    is_draft: false,
});

onMounted(() => {
    costs.value = props.form.costs;
});

// Département sélectionné + statut "head"
const selectedDepartment = computed(() => props.departments.find((d) => d.id === form.department_id) || null);

const isHeadOfSelectedDept = computed(() => {
    if (!selectedDepartment.value) return false;
    const heads = selectedDepartment.value.heads || [];
    return heads.some((h) => Number(h.id) === Number(props.authUser.id));
});

// Quand le département change : si head -> pré-sélectionne soi-même, sinon reset
watch(
    () => form.department_id,
    () => {
        if (isHeadOfSelectedDept.value) {
            form.target_user_id = props.authUser.id;
        } else {
            form.target_user_id = null;
        }
    },
);

const getActiveRateRecord = (cost, date) => {
    const rates = cost.reimbursement_rates || [];
    // On filtre par date uniquement (pas de sélection utilisateur)
    const actives = rates.filter((r) => r.start_date <= date && (!r.end_date || r.end_date >= date));
    // Idéalement: il ne doit y en avoir qu'un ce jour-là
    // Si plusieurs: on prend le plus récent (start_date max), ou on affichera un warning UI
    actives.sort((a, b) => (a.start_date > b.start_date ? -1 : 1));
    return actives[0] || null;
};

const getActiveRate = (cost, date) => getActiveRateRecord(cost, date)?.value ?? 0;

const getActiveTransport = (cost, date) => getActiveRateRecord(cost, date)?.transport ?? 'car';

const addToRequest = (cost) => {
    if (selectedCosts.value.length >= 30) {
        alert('Vous avez atteint le maximum de 30 coûts.');
        return;
    }

    const today = new Date().toISOString().split('T')[0];

    selectedCosts.value.push(cost);
    costData.value.push({
        date: today,
        kmData: {},
        percentageData: {
            paidAmount: null,
            percentage: getActiveRate(cost, today),
            reimbursedAmount: 0,
        },
        requirements: {},
        fixedAmount: getActiveRate(cost, today),
    });
};

const updateRate = (index, cost) => {
    const date = costData.value[index].date;
    const rate = getActiveRate(cost, date);

    if (cost.type === 'percentage') {
        costData.value[index].percentageData.percentage = rate;
        const paidAmount = costData.value[index].percentageData.paidAmount;
        if (paidAmount !== null && typeof paidAmount !== 'undefined') {
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

// Gestion de la duplication
const duplicateDialog = ref({
    isOpen: false,
    costIndex: -1,
    costName: '',
    count: 1,
});

const openDuplicateDialog = (index) => {
    const cost = selectedCosts.value[index];
    duplicateDialog.value = {
        isOpen: true,
        costIndex: index,
        costName: cost.name,
        count: 1,
    };
};

const confirmDuplicate = () => {
    const { costIndex, count } = duplicateDialog.value;

    // Vérifier la limite
    if (selectedCosts.value.length + count > 30) {
        alert(`Vous ne pouvez pas ajouter ${count} coûts. Maximum 30 coûts au total.`);
        return;
    }

    // Récupérer le coût et ses données
    const originalCost = selectedCosts.value[costIndex];
    const originalData = costData.value[costIndex];

    // Dupliquer le coût le nombre de fois demandé
    for (let i = 0; i < count; i++) {
        // Dupliquer le coût
        selectedCosts.value.push(originalCost);

        // Dupliquer les données en réinitialisant les requirements
        const newData = {
            kmData: { ...originalData.kmData }, // Copier les données km
            percentageData: { ...originalData.percentageData }, // Copier les données percentage
            requirements: {}, // Réinitialiser les requirements (vides)
            fixedAmount: originalData.fixedAmount, // Garder le montant fixe
        };

        costData.value.push(newData);
    }

    // Fermer le modal
    duplicateDialog.value.isOpen = false;
};

// Répartition d'un coût sur plusieurs dates
// Disponible pour les coûts sans annexe (prérequis de type fichier) : km,
// pourcentage et fixe. Les éventuels prérequis texte sont recopiés sur chaque date.
const hasAnnexeRequirement = (cost) => (cost.requirements || []).some((r) => r.type === 'file');
const canUseMultiDate = (cost) => !hasAnnexeRequirement(cost);

const multiDateDialog = ref({
    isOpen: false,
    costIndex: -1,
    costName: '',
    dates: [],
});

// Calendrier de sélection multi-dates
const weekdays = ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di'];
const monthNames = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const calendarMonth = ref({ year: 2026, month: 0 });

const pad = (n) => String(n).padStart(2, '0');
const toIso = (year, month, day) => `${year}-${pad(month + 1)}-${pad(day)}`;

const calendarLabel = computed(() => `${monthNames[calendarMonth.value.month]} ${calendarMonth.value.year}`);

const calendarCells = computed(() => {
    const { year, month } = calendarMonth.value;
    const offset = (new Date(year, month, 1).getDay() + 6) % 7; // lundi = 0
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells = [];
    for (let i = 0; i < offset; i++) {
        cells.push(null);
    }
    for (let d = 1; d <= daysInMonth; d++) {
        cells.push({ day: d, iso: toIso(year, month, d) });
    }
    return cells;
});

const isDateSelected = (iso) => multiDateDialog.value.dates.includes(iso);

const toggleDate = (iso) => {
    const dates = multiDateDialog.value.dates;
    const idx = dates.indexOf(iso);
    if (idx === -1) {
        dates.push(iso);
    } else {
        dates.splice(idx, 1);
    }
};

const prevMonth = () => {
    const { year, month } = calendarMonth.value;
    calendarMonth.value = month === 0 ? { year: year - 1, month: 11 } : { year, month: month - 1 };
};

const nextMonth = () => {
    const { year, month } = calendarMonth.value;
    calendarMonth.value = month === 11 ? { year: year + 1, month: 0 } : { year, month: month + 1 };
};

const openMultiDateDialog = (index) => {
    const cost = selectedCosts.value[index];
    const now = new Date();
    calendarMonth.value = { year: now.getFullYear(), month: now.getMonth() };
    multiDateDialog.value = {
        isOpen: true,
        costIndex: index,
        costName: cost.name,
        dates: [],
    };
};

const buildCostDataForDate = (cost, sourceData, date) => {
    const rate = getActiveRate(cost, date);

    // Recopie des prérequis texte (les annexes sont exclues via canUseMultiDate)
    const requirements = {};
    Object.entries(sourceData.requirements || {}).forEach(([key, value]) => {
        if (!(value instanceof File)) {
            requirements[key] = value;
        }
    });

    const data = {
        date,
        kmData: JSON.parse(JSON.stringify(sourceData.kmData || {})),
        percentageData: {
            paidAmount: sourceData.percentageData?.paidAmount ?? null,
            percentage: rate,
            reimbursedAmount: 0,
        },
        requirements,
        fixedAmount: rate,
    };

    if (cost.type === 'percentage') {
        const paid = data.percentageData.paidAmount;
        if (paid !== null && typeof paid !== 'undefined' && paid !== '') {
            data.percentageData.reimbursedAmount = (paid * rate) / 100;
        }
    }

    return data;
};

const confirmMultiDate = () => {
    const { costIndex, dates } = multiDateDialog.value;

    const validDates = [...new Set(dates.filter((d) => !!d))];
    if (!validDates.length) {
        multiDateDialog.value.isOpen = false;
        return;
    }

    if (selectedCosts.value.length + validDates.length > 30) {
        alert(`Vous ne pouvez pas ajouter ${validDates.length} coûts. Maximum 30 coûts au total.`);
        return;
    }

    const originalCost = selectedCosts.value[costIndex];
    const originalData = costData.value[costIndex];

    validDates.forEach((date) => {
        selectedCosts.value.push(originalCost);
        costData.value.push(buildCostDataForDate(originalCost, originalData, date));
    });

    multiDateDialog.value.isOpen = false;
};

const submitted = ref(false);
const isDraftSubmit = ref(false);

const saveDraft = () => {
    isDraftSubmit.value = true;
    submitForm(true);
};

const submit = () => {
    submitted.value = true;
    isDraftSubmit.value = false;
    submitForm(false);
};

const submitForm = (isDraft) => {
    // Définir is_draft dans le form
    form.is_draft = isDraft;

    // Pour les brouillons, on ne valide pas les requirements
    console.log('isDraft:', isDraft);
    if (!isDraft) {
        // 2) Valider les requirements avant envoi
        if (!validateAllRequirements()) {
            const firstErrorKey = Object.keys(form.errors).find((k) => k.includes('.requirements.'));
            if (firstErrorKey) {
                const m = firstErrorKey.match(/costs\.(\d+)\.requirements\./);
                if (m) {
                    const idx = Number(m[1]);
                    const el = document.getElementById(`cost-card-${idx}`);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
            return;
        }
    }

    // 1) Construire l'objet à poster
    form.costs = selectedCosts.value.map((cost, index) => {
        const data =
            cost.type === 'km'
                ? costData.value[index].kmData
                : cost.type === 'percentage'
                  ? costData.value[index].percentageData
                  : { amount: costData.value[index].fixedAmount };

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
            requirements,
        };
    });

    // 2) Valider les requirements avant envoi
    if (!validateAllRequirements()) {
        const firstErrorKey = Object.keys(form.errors).find((k) => k.includes('.requirements.'));
        if (firstErrorKey) {
            const m = firstErrorKey.match(/costs\.(\d+)\.requirements\./);
            if (m) {
                const idx = Number(m[1]);
                const el = document.getElementById(`cost-card-${idx}`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        return;
    }

    // 3) Envoi
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
            const fd = new FormData();
            fd.append('department_id', form.department_id);
            fd.append('is_draft', isDraft);

            if (form.target_user_id) {
                fd.append('target_user_id', form.target_user_id);
            }

            data.costs.forEach((cost, index) => {
                fd.append(`costs[${index}][cost_id]`, cost.cost_id);
                fd.append(`costs[${index}][date]`, cost.date);

                // Données du coût
                Object.entries(cost.data).forEach(([key, value]) => {
                    fd.append(`costs[${index}][data][${key}]`, value ?? '');
                });

                // REQUIREMENTS au bon niveau
                if (cost.requirements) {
                    Object.entries(cost.requirements).forEach(([reqId, req]) => {
                        if (req.file instanceof File) {
                            fd.append(`costs[${index}][requirements][${reqId}][file]`, req.file);
                        } else if (req.value !== undefined && req.value !== null) {
                            fd.append(`costs[${index}][requirements][${reqId}][value]`, req.value);
                        }
                    });
                }
            });

            return fd;
        },
    });
};

// Validation des requirements
const validateRequirementsForCost = (cost, index) => {
    let ok = true;
    const reqMeta = cost.requirements || [];
    const reqValues = costData.value[index]?.requirements || {};

    reqMeta.forEach((req) => {
        const key = req.name;
        const val = reqValues[key];

        form.setError(`costs.${index}.requirements.${key}`, null);

        if (req.type === 'text') {
            const isEmpty = val === undefined || val === null || String(val).trim() === '';
            if (isEmpty) {
                form.setError(`costs.${index}.requirements.${key}`, 'Ce champ est requis.');
                ok = false;
            }
        } else if (req.type === 'file') {
            const hasNewFile = val instanceof File;
            if (!hasNewFile) {
                form.setError(`costs.${index}.requirements.${key}`, 'Ce fichier est requis.');
                ok = false;
            }
        } else {
            form.setError(`costs.${index}.requirements.${key}`, 'Type de prérequis inconnu ou non rempli.');
            ok = false;
        }
    });

    return ok;
};

const validateAllRequirements = () => {
    let allOk = true;
    Object.keys(form.errors || {}).forEach((k) => {
        if (k.startsWith('costs.') && k.includes('.requirements.')) {
            form.setError(k, null);
        }
    });

    selectedCosts.value.forEach((cost, index) => {
        const ok = validateRequirementsForCost(cost, index);
        if (!ok) allOk = false;
    });

    return allOk;
};

// Helpers
const roundKm = (v) => {
    const n = Number(v) || 0;
    return Math.round(n * 10) / 10;
};

const formatCurrency = (v) => (Number(v) || 0).toLocaleString('fr-BE', { style: 'currency', currency: 'EUR' });

const formatRate = (v) => `${v} €`;

// Montant remboursé pour un coût "km"
const kmReimbursed = (index, cost) => {
    const km = Number(costData.value[index]?.kmData?.totalKm) || 0;
    const rate = getActiveRate(cost, costData.value[index].date);
    return Number((km * rate).toFixed(2));
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notes de frais', href: '/expense-sheet' },
    { title: 'Créer une note de frais' },
];
</script>
