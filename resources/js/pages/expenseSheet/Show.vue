<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Note de frais #${expenseSheet.id}`" />

        <div class="container mx-auto p-4 space-y-6">


            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">Note de frais #{{ expenseSheet.id }}</h1>
                    <p class="text-sm text-muted-foreground">
                        Créée le {{ formatDate(expenseSheet.created_at) }}
                    </p>
                </div>


                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 mr-2">
                        <!--                        <Button v-if="canEdit" variant="outline" size="sm" @click="editExpenseSheet">-->
                        <!--                            <PencilIcon class="h-4 w-4 mr-1" />-->
                        <!--                            Modifier-->
                        <!--                        </Button>-->
                        <Button v-if="canApprove" variant="success" size="sm" @click="approveExpenseSheet">
                            <CheckIcon class="h-4 w-4 mr-1" />
                            Approuver
                        </Button>
                        <Button v-if="canReject" variant="destructive" size="sm" @click="openRejectModal">
                            <XIcon class="h-4 w-4 mr-1" />
                            Rejeter
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
                            <DropdownMenuItem @click="downloadPdf">
                                <DownloadIcon class="mr-2 h-4 w-4" />
                                Télécharger PDF
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Bannière de refus moderne -->
            <div v-if="expenseSheet.approved == false"
                 class="bg-gradient-to-r from-destructive/10 to-destructive/5 border-l-4 border-destructive shadow-sm px-5 py-4 rounded-lg flex items-start gap-3 transition-all duration-200 hover:shadow-md">
                <div class="bg-destructive/10 p-2 rounded-full flex-shrink-0">
                    <AlertCircleIcon class="h-5 w-5 text-destructive" />
                </div>
                <div class="space-y-1.5">
                    <h3 class="font-medium text-destructive flex items-center gap-2">
                        Note de frais refusée
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-destructive/70 animate-pulse"></span>
                    </h3>
                    <div class="text-sm text-destructive/90 space-y-1">
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


            <!-- Informations générales -->
            <Card class="bg-card text-card-foreground">
                <CardHeader>
                    <CardTitle>Informations générales</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground">Demandeur</h3>
                            <p class="flex items-center mt-1">
                                <Avatar class="h-6 w-6 mr-2">
                                    <AvatarFallback>{{ getInitials(expenseSheet.user.name) }}</AvatarFallback>
                                </Avatar>
                                {{ expenseSheet.user.name }}
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
                        <div
                            v-for="(cost, index) in expenseSheet.costs"
                            :key="index"
                            class="p-4 border rounded space-y-4 bg-background"
                        >
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold text-foreground">{{ cost.form_cost.name }}</h3>
                                <span
                                    class="text-sm italic text-muted-foreground">{{ getActiveRate(cost, cost.date) }} / {{ cost.type
                                    }}</span>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ cost.description }}</p>

                            <!-- Date du coût -->
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-medium text-muted-foreground">Date du coût :</h4>
                                <p>{{ formatDate(cost.date) }}</p>
                            </div>

                            <!-- Affichage de la route et des étapes -->
                            <div v-if="cost.route" class="space-y-2">
                                <h4 class="text-sm font-medium text-muted-foreground">Route :</h4>
                                <ul class="list-disc pl-5">
                                    <li>
                                        <span class="font-semibold">Départ :</span>
                                        {{ cost.route.departure }}
                                    </li>

                                    <li v-if="cost.route.steps && cost.route.steps.length > 0">
                                        <span class="font-semibold">Étapes :</span>
                                        <ul class="list-decimal pl-5">
                                            <li v-for="(step, stepIndex) in cost.route.steps" :key="step.id">
                                                {{ step.address }}
                                            </li>
                                        </ul>
                                    </li>

                                    <li>
                                        <span class="font-semibold">Arrivée :</span>
                                        {{ cost.route.arrival }}
                                    </li>

                                    <li>
                                        <span class="font-semibold">Distance Google :</span>
                                        {{ cost.route.google_km }} km
                                    </li>

                                    <li v-if="cost.route.manual_km">
                                        <span class="font-semibold">Distance manuelle :</span>
                                        {{ cost.route.manual_km }} km
                                    </li>

                                    <li v-if="cost.route.justification">
                                        <span class="font-semibold">Justification :</span>
                                        {{ cost.route.justification }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Affichage des requirements -->
                            <div v-if="cost.requirements" class="space-y-2">
                                <h4 class="text-sm font-medium text-muted-foreground">Annexes :</h4>
                                <ul class="list-disc pl-5">
                                    <li
                                        v-for="(requirement, key) in parseRequirements(cost.requirements)"
                                        :key="key"
                                        class="text-foreground"
                                    >
                                        <span class="font-semibold">{{ key }} :</span>
                                        <span v-if="requirement.file">
                                            <a
                                                :href="requirement.file"
                                                target="_blank"
                                                class="text-primary underline"
                                            >
                                                Visualiser le fichier
                                            </a>
                                        </span>
                                        <span v-else>{{ requirement.value }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="cost.amount">
                                <h4 class="text-sm font-medium text-muted-foreground">Montant payé :</h4>
                                <p class="font-bold">{{ formatCurrency(cost.amount) }}</p>
                            </div>

                            <!-- Affichage du montant -->
                            <div>
                                <h4 class="text-sm font-medium text-muted-foreground">Montant dû estimé :</h4>
                                <p class="font-bold">{{ formatCurrency(cost.total) }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>

        <!-- Modal de justification de refus -->
        <Dialog :open="isRejectModalOpen" @update:open="isRejectModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Justification du refus</DialogTitle>
                    <DialogDescription>
                        Veuillez fournir une raison pour le refus de cette note de frais.
                    </DialogDescription>
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
                    <Button
                        variant="destructive"
                        @click="confirmReject"
                        :disabled="!rejectionReason.trim()"
                    >
                        Confirmer le refus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>

    <ExpenseSheetPdf ref="pdfContent" :expenseSheet="expenseSheet" class="hidden" />

</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import ExpenseSheetPdf from '@/pages/expenseSheet/Pdf.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import {
    MoreVerticalIcon,
    PrinterIcon,
    DownloadIcon,
    CheckIcon,
    XIcon,
    PencilIcon,
    AlertCircleIcon
} from 'lucide-vue-next';
import { formatDate, formatCurrency, formatRate, getStatusLabel, getActiveRate } from '@/utils/formatters';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import html2pdf from 'html2pdf.js';

const props = defineProps({
    expenseSheet: Object,
    canApprove: {
        type: Boolean,
        default: false
    },
    canReject: {
        type: Boolean,
        default: false
    },
    canEdit: {
        type: Boolean,
        default: false
    }
});

const breadcrumbs = [
    {
        title: 'Tableau de bord',
        href: '/dashboard'
    },
    {
        title: `Note de frais #${props.expenseSheet.id}`,
        href: `/expense-sheet/${props.expenseSheet.id}`
    }
];

// État pour le modal de rejet
const isRejectModalOpen = ref(false);
const rejectionReason = ref('');

const pdfContent = ref();


// Ouvrir le modal de rejet
const openRejectModal = () => {
    rejectionReason.value = '';
    isRejectModalOpen.value = true;
};

// Fermer le modal de rejet
const closeRejectModal = () => {
    isRejectModalOpen.value = false;
};

// Confirmer le rejet avec la raison
const confirmReject = () => {
    if (!rejectionReason.value.trim()) return;

    useForm({
        approval: false,
        reason: rejectionReason.value
    }).post('/expense-sheet/' + props.expenseSheet.id + '/approve');
    closeRejectModal();
};

// Action methods
const approveExpenseSheet = () => {
    useForm({
        approval: true
    }).post('/expense-sheet/' + props.expenseSheet.id + '/approve');
};

const editExpenseSheet = () => {
    router.visit('/expense-sheet/' + props.expenseSheet.id + '/edit');
};

const downloadPdf = () => {
    html2pdf()
        .set({
            margin: 10,
            filename: `note-frais-${props.expenseSheet.id}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        })
        .from(pdfContent.value.$el) // Attention : .value.$el car c'est un composant enfant
        .save();
};

const printExpenseSheet = () => {
    if (!pdfContent.value) return;

    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    printWindow.document.write(`
        <html>
            <head>
                <title>Impression Note de frais</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                </style>
            </head>
            <body>
                ${pdfContent.value.$el.outerHTML}
            </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
};


// Obtenir le libellé du statut


// Obtenir les initiales d'un nom
const getInitials = (name) => {
    return name.split(' ').map((word) => word.charAt(0)).join('');
};

// Fonction pour parser les requirements JSON
const parseRequirements = (requirements) => {
    try {
        return typeof requirements === 'string' ? JSON.parse(requirements) : requirements;
    } catch (e) {
        console.error('Erreur lors du parsing des requirements:', e);
        return {};
    }
};

</script>

