<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Note de frais #${expenseSheet.id}`" />

        <div class="container mx-auto space-y-6 p-4">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">Note de frais #{{ expenseSheet.id }}</h1>
                    <p class="text-sm text-muted-foreground">Créée le {{ formatDate(expenseSheet.created_at) }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="mr-2 flex items-center gap-2">
                        <Button v-if="canApprove" variant="success" size="sm" @click="approveExpenseSheet">
                            <CheckIcon class="mr-1 h-4 w-4" />
                            Approuver
                        </Button>
                        <Button v-if="canReject" variant="destructive" size="sm" @click="openRejectModal">
                            <XIcon class="mr-1 h-4 w-4" />
                            Rejeter
                        </Button>
                        <Button v-if="expenseSheet.approved == false && canDestroy" variant="destructive" size="sm" @click="openDeleteModal">
                            <TrashIcon class="mr-1 h-4 w-4" />
                            Supprimer
                        </Button>
                    </div>
                    <Badge :variant="getStatusLabel(expenseSheet).variant">
                        {{ getStatusLabel(expenseSheet).label }}
                    </Badge>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="icon">
                                <MoreVerticalIcon class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="printExpenseSheet">
                                <PrinterIcon class="mr-2 h-4 w-4" />
                                Imprimer
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                                <a :href="`/expense-sheets/${expenseSheet.id}/pdf`" target="_blank" class="flex w-full">
                                    <DownloadIcon class="mr-2 h-4 w-4" />
                                    Télécharger PDF
                                </a>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Bannière de refus -->
            <div
                v-if="expenseSheet.approved == false"
                class="flex items-start gap-3 rounded-lg border-l-4 border-destructive bg-gradient-to-r from-destructive/10 to-destructive/5 px-5 py-4 shadow-sm transition-all duration-200 hover:shadow-md"
            >
                <div class="flex-shrink-0 rounded-full bg-destructive/10 p-2">
                    <AlertCircleIcon class="h-5 w-5 text-destructive" />
                </div>
                <div class="space-y-1.5">
                    <h3 class="flex items-center gap-2 font-medium text-destructive">
                        Note de frais refusée
                        <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-destructive/70"></span>
                    </h3>
                    <div class="space-y-1 text-sm text-destructive/90">
                        <p class="flex items-baseline gap-1.5">
                            <span class="font-semibold">Refusée par :</span>
                            <span>{{ expenseSheet.validated_by.name }}</span>
                        </p>
                        <p class="flex items-baseline gap-1.5">
                            <span class="font-semibold">Motif du refus :</span>
                            <span>{{ expenseSheet.refusal_reason }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bannière approbation -->
            <div
                v-if="expenseSheet.approved == true"
                class="flex items-start gap-3 rounded-lg border-l-4 border-green-400 bg-green-100 px-5 py-4 text-green-800 shadow-sm transition-all duration-200 hover:shadow-md dark:border-green-700 dark:bg-green-900 dark:text-green-200"
            >
                <div class="flex-shrink-0 rounded-full bg-green-200 p-2 dark:bg-green-700">
                    <CheckIcon class="h-5 w-5 text-green-600 dark:text-green-300" />
                </div>
                <div class="space-y-1.5">
                    <h3 class="flex items-center gap-2 font-medium">
                        Note de frais approuvée
                        <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-green-600 dark:bg-green-300"></span>
                    </h3>
                    <div class="space-y-1 text-sm">
                        <p class="flex items-baseline gap-1.5">
                            <span class="font-semibold">Approuvée par :</span>
                            <span>{{ expenseSheet.validated_by.name }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Informations générales -->
            <Card class="bg-card text-card-foreground">
                <CardHeader>
                    <CardTitle>Informations générales</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground">Demandeur</h3>
                            <p class="mt-1 flex items-center">
                                <Avatar class="mr-2 h-6 w-6">
                                    <AvatarFallback>{{ getInitials(expenseSheet.user.name) }}</AvatarFallback>
                                </Avatar>
                                {{ expenseSheet.user.name }}
                            </p>
                        </div>
                        <div v-if="expenseSheet.user.name !== expenseSheet.creator.name">
                            <h3 class="text-sm font-medium text-muted-foreground">Encodeur</h3>
                            <p class="mt-1 flex items-center">
                                <Avatar class="mr-2 h-6 w-6">
                                    <AvatarFallback>{{ getInitials(expenseSheet.creator.name) }}</AvatarFallback>
                                </Avatar>
                                {{ expenseSheet.creator.name }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground">Département</h3>
                            <p class="mt-1">{{ expenseSheet.department?.name || 'Non spécifié' }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Détails des coûts -->
            <Card class="bg-card text-card-foreground">
                <CardHeader>
                    <CardTitle>Détails des coûts</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <div v-for="(cost, index) in expenseSheet.costs" :key="index" class="space-y-4 rounded border bg-background p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-foreground">{{ cost.form_cost.name }}</h3>
                                <div class="flex items-center gap-2">
                                    <!-- Badge transport -->
                                    <Badge variant="secondary" class="flex items-center gap-1">
                                        <component :is="transportIcon(resolveTransport(cost))" class="h-3.5 w-3.5" />
                                        {{ transportLabel(resolveTransport(cost)) }}
                                    </Badge>
                                    <!-- Ancien texte -->
                                    <span class="text-sm italic text-muted-foreground"> {{ getActiveRate(cost, cost.date) }} / {{ cost.type }} </span>
                                </div>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ cost.description }}</p>

                            <!-- Date du coût -->
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-medium text-muted-foreground">Date du coût :</h4>
                                <p>{{ formatDate(cost.date) }}</p>
                            </div>

                            <!-- Affichage route -->
                            <div v-if="cost.route" class="space-y-2">
                                <h4 class="text-sm font-medium text-muted-foreground">Route :</h4>
                                <ul class="list-disc pl-5">
                                    <li><span class="font-semibold">Départ :</span> {{ cost.route.departure }}</li>
                                    <li v-if="cost.route.steps && cost.route.steps.length > 0">
                                        <span class="font-semibold">Étapes :</span>
                                        <ul class="list-decimal pl-5">
                                            <li v-for="(step, stepIndex) in cost.route.steps" :key="stepIndex">
                                                {{ step.address }}
                                            </li>
                                        </ul>
                                    </li>
                                    <li><span class="font-semibold">Arrivée :</span> {{ cost.route.arrival }}</li>
                                    <li class="font-semibold">
                                        <span class="font-semibold">Total des KM :</span>
                                        {{ Math.round(cost.route.google_km + cost.route.manual_km) }} km
                                    </li>
                                </ul>
                            </div>

                            <!-- Requirements -->
                            <div v-if="cost.requirements" class="space-y-2">
                                <h4 class="text-sm font-medium text-muted-foreground">Annexes :</h4>
                                <ul class="list-disc pl-5">
                                    <li v-for="(requirement, key) in parseRequirements(cost.requirements)" :key="key" class="text-foreground">
                                        <span class="font-semibold">{{ key }} :</span>
                                        <span v-if="requirement.file">
                                            <a :href="requirement.file" target="_blank" class="text-primary underline"> Visualiser le fichier </a>
                                        </span>
                                        <span v-else>
                                            <template
                                                v-if="
                                                    requirement.value &&
                                                    (requirement.value.startsWith('http://') || requirement.value.startsWith('https://'))
                                                "
                                            >
                                                <a :href="requirement.value" target="_blank" class="text-primary underline">{{
                                                    requirement.value
                                                }}</a>
                                            </template>
                                            <template v-else>
                                                {{ requirement.value }}
                                            </template>
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="cost.amount">
                                <h4 class="text-sm font-medium text-muted-foreground">Montant payé :</h4>
                                <p class="font-bold">{{ formatCurrency(cost.amount) }}</p>
                            </div>

                            <!-- Montant estimé -->
                            <div>
                                <h4 class="text-sm font-medium text-muted-foreground">Montant dû estimé :</h4>
                                <p class="font-bold">{{ formatCurrency(cost.total) }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Modal rejet -->
        <Dialog :open="isRejectModalOpen" @update:open="isRejectModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Justification du refus</DialogTitle>
                    <DialogDescription>Veuillez fournir une raison pour le refus.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="rejection-reason">Motif du refus</Label>
                        <Textarea
                            id="rejection-reason"
                            v-model="rejectionReason"
                            placeholder="Veuillez expliquer pourquoi cette note de frais est rejetée..."
                            class="min-h-[100px]"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="closeRejectModal">Annuler</Button>
                    <Button variant="destructive" @click="confirmReject" :disabled="!rejectionReason.trim()">Confirmer le refus </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="isDeleteModalOpen" @update:open="isDeleteModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Confirmation de supression</DialogTitle>
                    <DialogDescription>Etes-vous sûr de vouloir supprimer cette note de frais ?</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="closeDeleteModal">Annuler</Button>
                    <Button variant="destructive" @click="confirmDelete">Supprimer</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>

    <ExpenseSheetPdf ref="pdfContent" :expenseSheet="expenseSheet" class="hidden" />
</template>

<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import ExpenseSheetPdf from '@/pages/expenseSheet/Pdf.vue';
import { formatCurrency, formatDate, getActiveRate, getStatusLabel } from '@/utils/formatters';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircleIcon,
    BikeIcon,
    CarIcon,
    CheckIcon,
    DownloadIcon,
    FootprintsIcon,
    MoreVerticalIcon,
    PrinterIcon,
    TrashIcon,
    XIcon,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    expenseSheet: Object,
    canApprove: { type: Boolean, default: false },
    canReject: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canDestroy: { type: Boolean, default: false },
});

const breadcrumbs = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: `Note de frais #${props.expenseSheet.id}`, href: `/expense-sheet/${props.expenseSheet.id}` },
];

// Modal refus
const isRejectModalOpen = ref(false);
const rejectionReason = ref('');

// Modal de delete
const isDeleteModalOpen = ref(false);

const openDeleteModal = () => {
    isDeleteModalOpen.value = true;
};
const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
};

const confirmDelete = () => {
    useForm().delete('/expense-sheet/' + props.expenseSheet.id);
    closeDeleteModal();
};

const openRejectModal = () => {
    rejectionReason.value = '';
    isRejectModalOpen.value = true;
};
const closeRejectModal = () => {
    isRejectModalOpen.value = false;
};
const confirmReject = () => {
    if (!rejectionReason.value.trim()) return;
    useForm({
        approval: false,
        reason: rejectionReason.value,
    }).post('/expense-sheet/' + props.expenseSheet.id + '/approve');
    closeRejectModal();
};
const approveExpenseSheet = () => {
    useForm({ approval: true }).post('/expense-sheet/' + props.expenseSheet.id + '/approve');
};
const editExpenseSheet = () => {
    router.visit('/expense-sheet/' + props.expenseSheet.id + '/edit');
};
const printExpenseSheet = () => {
    /* ... ton code d’impression inchangé ... */
};

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w.charAt(0))
        .join('');
const parseRequirements = (requirements: any) => {
    try {
        return typeof requirements === 'string' ? JSON.parse(requirements) : requirements;
    } catch {
        return {};
    }
};

// === Gestion transport ===
const getActiveRateRecordLocal = (cost: any) => {
    const rates = cost?.form_cost?.reimbursement_rates || [];
    const date = cost?.date;
    if (!date) return null;
    const actives = rates.filter((r: any) => r.start_date <= date && (!r.end_date || r.end_date >= date));
    actives.sort((a: any, b: any) => (a.start_date > b.start_date ? -1 : 1));
    return actives[0] || null;
};
const resolveTransport = (cost: any) => {
    if (cost?.route?.transport) return cost.route.transport;
    const rate = getActiveRateRecordLocal(cost);
    return rate?.transport ?? 'car';
};
const transportLabel = (t: string) => (t === 'bike' ? 'Vélo' : t === 'other' ? 'Autre' : 'Voiture');
const transportIcon = (t: string) => (t === 'bike' ? BikeIcon : t === 'other' ? FootprintsIcon : CarIcon);
</script>
