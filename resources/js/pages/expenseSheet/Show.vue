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
                                <h3 class="text-xl font-bold">{{ cost.name }}</h3>
                                <span class="text-sm italic text-muted-foreground">{{ cost.type }}</span>
                            </div>
                            <p class="text-sm text-gray-600">{{ cost.description }}</p>

                            <!-- Date du coût -->
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-medium text-muted-foreground">Date du coût :</h4>
                                <p>{{ formatDate(cost.date) }}</p>
                            </div>

                            <!-- Affichage selon le type de coût -->
                            <div v-if="cost.type === 'km'" class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Distance</h4>
                                        <p>{{ cost.distance }} km</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Taux</h4>
                                        <p>{{ getActiveRate(cost, cost.date) }} / km</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Montant</h4>
                                        <p class="font-bold">{{ formatCurrency(cost.total) }}</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-muted-foreground">Trajet</h4>
                                    <p>{{ cost.route.departure }} → {{ cost.route.arrival }}</p>
                                </div>
                            </div>

                            <div v-else-if="cost.type === 'fixed'" class="space-y-2">
                                <div>
                                    <h4 class="text-sm font-medium text-muted-foreground">Montant fixe</h4>
                                    <p class="font-bold">{{ formatCurrency(cost.total) }}</p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-muted-foreground">Taux applicable</h4>
                                    <p>{{ formatRate(cost.rate) }}</p>
                                </div>
                            </div>

                            <div v-else-if="cost.type === 'percentage'" class="space-y-2">
                                <div>
                                    <h4 class="text-sm font-medium text-muted-foreground">Montant payé</h4>
                                    <p>{{ formatCurrency(cost.data.paidAmount) }}</p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-muted-foreground">Pourcentage</h4>
                                    <p>{{ cost.percentage }}%</p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-muted-foreground">Montant remboursé</h4>
                                    <p class="font-bold">{{ formatCurrency(cost.data.reimbursedAmount) }}</p>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-muted-foreground">Taux applicable</h4>
                                    <p>{{ cost.rate }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
                <CardFooter class="flex justify-end">
                    <!-- Actions buttons -->
                    <div v-if="canApprove || canReject || canEdit" class="flex flex-wrap gap-2 justify-end">
                        <Button
                            v-if="canApprove"
                            variant="outline"
                            @click="approveExpenseSheet"
                        >
                            <CheckIcon class="mr-2 h-4 w-4" />
                            Accepter
                        </Button>
                        <Button
                            v-if="canReject"
                            variant="destructive"
                            @click="openRejectModal"
                        >
                            <XIcon class="mr-2 h-4 w-4" />
                            Rejeter
                        </Button>
                        <Button
                            v-if="canEdit && ['pending', 'rejected'].includes(expenseSheet.status)"
                            variant="outline"
                            @click="editExpenseSheet"
                        >
                            <PencilIcon class="mr-2 h-4 w-4" />
                            Modifier
                        </Button>
                    </div>
                </CardFooter>
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

    // Ici, vous pouvez implémenter la logique pour envoyer la raison du rejet
    console.log('Rejecting expense sheet', props.expenseSheet.id, 'with reason:', rejectionReason.value);

    // Vous pourriez émettre un événement ou appeler une API ici
    // Par exemple:
    // emit('reject', { id: props.expenseSheet.id, reason: rejectionReason.value });

    // Fermer le modal après confirmation
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

// Obtenir la variante de badge selon le statut
const getStatusVariant = (status) => {
    switch (status) {
        case 'pending':
            return 'outline';
        case 'approved':
            return 'success';
        case 'rejected':
            return 'destructive';
        case 'paid':
            return 'default';
        default:
            return 'secondary';
    }
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
</script>
