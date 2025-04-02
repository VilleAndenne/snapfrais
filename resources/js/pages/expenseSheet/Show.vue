<template>
    <AppLayout>
        <Head :title="`Note de frais #${expenseSheet.id}`" />

        <div class="container mx-auto p-4 space-y-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-semibold">Note de frais #{{ expenseSheet.id }}</h1>
                    <p class="text-sm text-muted-foreground">
                        Créée le {{ formatDate(expenseSheet.created_at) }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 mr-2">
                        <Button v-if="canEdit" variant="outline" size="sm" @click="editExpenseSheet">
                            <PencilIcon class="h-4 w-4 mr-1" />
                            Modifier
                        </Button>
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

            <!-- Informations générales -->
            <Card>
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
            <Card>
                <CardHeader>
                    <CardTitle>Détails des coûts</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <div
                            v-for="(cost, index) in expenseSheet.costs"
                            :key="index"
                            class="p-4 border rounded space-y-4 bg-white"
                        >
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold">{{ cost.form_cost.name }}</h3>
                                <span
                                    class="text-sm italic text-muted-foreground">{{ getActiveRate(cost, cost.date) }}€ / {{ cost.type
                                    }}</span>
                            </div>
                            <p class="text-sm text-gray-600">{{ cost.description }}</p>

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

                                    <li v-if="cost.steps && cost.steps.length > 0">
                                        <span class="font-semibold">Étapes :</span>
                                        <ul class="list-decimal pl-5">
                                            <li v-for="(step, stepIndex) in cost.steps" :key="step.id">
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
                                        class="text-gray-800"
                                    >
                                        <span class="font-semibold">{{ key }} :</span>
                                        <span v-if="requirement.file">
                                <a
                                    :href="getFileUrl(requirement.file)"
                                    target="_blank"
                                    class="text-blue-600 underline"
                                >
                                    Visualiser le fichier
                                </a>
                            </span>
                                        <span v-else>{{ requirement.value }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Affichage du montant -->
                            <div>
                                <h4 class="text-sm font-medium text-muted-foreground">Montant :</h4>
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
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
    PencilIcon
} from 'lucide-vue-next';
import { formatDate, formatCurrency, formatRate, getActiveRate } from '@/utils/formatters';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

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

// État pour le modal de rejet
const isRejectModalOpen = ref(false);
const rejectionReason = ref('');

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
    // Implement edit logic here
    console.log('Editing expense sheet', props.expenseSheet.id);
    // You might want to navigate to an edit page or open a modal
};

// Existing methods for printing and downloading
const printExpenseSheet = () => {
    window.print();
};

const downloadPdf = () => {
    // Implement PDF download logic
    console.log('Downloading PDF for expense sheet', props.expenseSheet.id);
};

// Obtenir le libellé du statut
const getStatusLabel = (expenseSheet) => {
    if (expenseSheet.approved == true) {
        return {
            label: 'Approuvée',
            variant: 'success'
        };
    }
    if (expenseSheet.approved == false) {
        return {
            label: 'Rejetée',
            variant: 'destructive'
        };
    }
    // Vérifiez si le statut est indéfini ou nul
    if (expenseSheet.approved === null || expenseSheet.approved === undefined) {
        return {
            label: 'En attente',
            variant: 'outline'
        };
    }

    // Par défaut (si aucun des cas précédents ne s'applique)
    return {
        label: 'Statut inconnu',
        variant: 'secondary'
    };
};


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

// Obtenir l'URL complète du fichier à partir du chemin
const getFileUrl = (path) => {
    return `/storage/${path}`;
};

</script>

