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
                    <Badge :variant="getStatusVariant(expenseSheet.status)">
                        {{ getStatusLabel(expenseSheet.status) }}
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
                            <DropdownMenuSeparator />
                            <DropdownMenuItem v-if="canApprove" @click="approveExpenseSheet">
                                <CheckIcon class="mr-2 h-4 w-4" />
                                Approuver
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="canReject" @click="rejectExpenseSheet">
                                <XIcon class="mr-2 h-4 w-4" />
                                Rejeter
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
                            <p class="mt-1">{{ expenseSheet.user.department?.name || 'Non spécifié' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground">Date de soumission</h3>
                            <p class="mt-1">{{ formatDate(expenseSheet.created_at) }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-muted-foreground">Dernière mise à jour</h3>
                            <p class="mt-1">{{ formatDate(expenseSheet.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Résumé des coûts -->
            <Card>
                <CardHeader>
                    <CardTitle>Résumé des coûts</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div class="flex justify-between py-2 font-medium">
                            <span>Total demandé</span>
                            <span>{{ formatCurrency(totalAmount) }}</span>
                        </div>
                        <Separator />
                        <div class="flex justify-between py-2 text-lg font-bold">
                            <span>Montant à rembourser</span>
                            <span>{{ formatCurrency(totalAmount) }}</span>
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

                            <!-- Affichage selon le type de coût -->
                            <div v-if="cost.type === 'km'" class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Distance</h4>
                                        <p>{{ cost.distance }} km</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Taux</h4>
                                        <p>{{ formatCurrency(cost.rate) }}/km</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Montant</h4>
                                        <p class="font-bold">{{ formatCurrency(cost.amount) }}</p>
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
                            </div>

                            <div v-else-if="cost.type === 'percentage'" class="space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Montant payé</h4>
                                        <p>{{ formatCurrency('0') }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Pourcentage</h4>
                                        <p>{{ cost.percentage }}%</p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-muted-foreground">Montant remboursé</h4>
                                        <p class="font-bold">{{ formatCurrency(cost.data.reimbursedAmount) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Prérequis -->
                            <div v-if="cost.requirements && Object.keys(cost.requirements).length"
                                 class="mt-4 pt-4 border-t">
                                <h4 class="text-sm font-medium mb-2">Justificatifs</h4>
                                <div class="space-y-2">
                                    <div v-for="(value, key) in cost.requirements" :key="key"
                                         class="flex items-center gap-2">
                                        <CheckCircleIcon v-if="value" class="h-5 w-5 text-green-500" />
                                        <XCircleIcon v-else class="h-5 w-5 text-red-500" />
                                        <span>{{ formatRequirementKey(key) }}</span>
                                        <Button
                                            v-if="value && typeof value === 'string' && value.startsWith('http')"
                                            variant="outline"
                                            size="sm"
                                            @click="openAttachment(value)"
                                        >
                                            <EyeIcon class="h-4 w-4 mr-1" />
                                            Voir
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Historique des approbations -->
            <Card v-if="expenseSheet.approvals && expenseSheet.approvals.length">
                <CardHeader>
                    <CardTitle>Historique des approbations</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="(approval, index) in expenseSheet.approvals" :key="index"
                             class="flex items-start gap-4">
                            <div class="mt-0.5">
                                <CheckCircleIcon v-if="approval.approved" class="h-5 w-5 text-green-500" />
                                <XCircleIcon v-else class="h-5 w-5 text-red-500" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <Avatar class="h-6 w-6">
                                        <AvatarFallback>{{ getInitials(approval.user.name) }}</AvatarFallback>
                                    </Avatar>
                                    <span class="font-medium">{{ approval.user.name }}</span>
                                    <span class="text-sm text-muted-foreground">{{ formatDate(approval.created_at)
                                        }}</span>
                                </div>
                                <p v-if="approval.comment" class="mt-1 text-sm">{{ approval.comment }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <div v-if="canApprove || canReject" class="flex justify-end gap-2 pt-4">
                <Button v-if="canReject" variant="outline" @click="showRejectDialog">
                    <XIcon class="mr-2 h-4 w-4" />
                    Rejeter
                </Button>
                <Button v-if="canApprove" @click="showApproveDialog">
                    <CheckIcon class="mr-2 h-4 w-4" />
                    Approuver
                </Button>
            </div>
        </div>

        <!-- Dialog d'approbation -->
        <Dialog v-model:open="approveDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Approuver la note de frais</DialogTitle>
                    <DialogDescription>
                        Vous êtes sur le point d'approuver cette note de frais. Vous pouvez ajouter un commentaire
                        optionnel.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="comment">Commentaire (optionnel)</Label>
                        <Textarea id="comment" v-model="approvalComment" placeholder="Votre commentaire..." />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="approveDialogOpen = false">Annuler</Button>
                    <Button @click="submitApproval(true)" :disabled="approvalProcessing">
                        <Loader2Icon v-if="approvalProcessing" class="mr-2 h-4 w-4 animate-spin" />
                        Approuver
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Dialog de rejet -->
        <Dialog v-model:open="rejectDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rejeter la note de frais</DialogTitle>
                    <DialogDescription>
                        Veuillez fournir une raison pour le rejet de cette note de frais.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="reject-reason">Raison du rejet</Label>
                        <Textarea
                            id="reject-reason"
                            v-model="approvalComment"
                            placeholder="Raison du rejet..."
                            required
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="rejectDialogOpen = false">Annuler</Button>
                    <Button
                        variant="destructive"
                        @click="submitApproval(false)"
                        :disabled="approvalProcessing || !approvalComment.trim()"
                    >
                        <Loader2Icon v-if="approvalProcessing" class="mr-2 h-4 w-4 animate-spin" />
                        Rejeter
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';

import {
    CheckIcon,
    XIcon,
    Loader2Icon,
    CheckCircleIcon,
    XCircleIcon,
    MoreVerticalIcon,
    PrinterIcon,
    DownloadIcon,
    EyeIcon
} from 'lucide-vue-next';

const props = defineProps({
    expenseSheet: {
        type: Object,
        required: true
    },
    canApprove: {
        type: Boolean,
        default: false
    },
    canReject: {
        type: Boolean,
        default: false
    }
});

// État pour les dialogues
const approveDialogOpen = ref(false);
const rejectDialogOpen = ref(false);
const approvalComment = ref('');
const approvalProcessing = ref(false);

// Calcul du montant total
const totalAmount = computed(() => {
    return props.expenseSheet.costs.reduce((total, cost) => {
        if (cost.type === 'km') {
            return total + (cost.total || 0);
        } else if (cost.type === 'percentage') {
            return total + (cost.total || 0);
        } else {
            return total + (cost.total || 0);
        }
    }, 0);
});

// Formatage de la date
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

// Formatage de la devise
const formatCurrency = (amount) => {
    if (amount === undefined || amount === null) return '0,00 €';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
};

// Obtenir les initiales d'un nom
const getInitials = (name) => {
    if (!name) return 'U';
    return name
        .split(' ')
        .map(part => part[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
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
const getStatusLabel = (status) => {
    switch (status) {
        case 'pending':
            return 'En attente';
        case 'approved':
            return 'Approuvée';
        case 'rejected':
            return 'Rejetée';
        case 'paid':
            return 'Payée';
        default:
            return 'Inconnu';
    }
};

// Formater la clé d'un prérequis
const formatRequirementKey = (key) => {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
};

// Ouvrir une pièce jointe
const openAttachment = (url) => {
    window.open(url, '_blank');
};

// Afficher le dialogue d'approbation
const showApproveDialog = () => {
    approvalComment.value = '';
    approveDialogOpen.value = true;
};

// Afficher le dialogue de rejet
const showRejectDialog = () => {
    approvalComment.value = '';
    rejectDialogOpen.value = true;
};

// Soumettre l'approbation ou le rejet
const submitApproval = (approved) => {
    approvalProcessing.value = true;

    const form = useForm({
        approved,
        comment: approvalComment.value
    });

    form.post(`/expense-sheet/${props.expenseSheet.id}/approve`, {
        onSuccess: () => {
            approveDialogOpen.value = false;
            rejectDialogOpen.value = false;
            approvalComment.value = '';
        },
        onFinish: () => {
            approvalProcessing.value = false;
        }
    });
};

// Approuver directement (sans dialogue)
const approveExpenseSheet = () => {
    approvalComment.value = '';
    submitApproval(true);
};

// Rejeter (ouvre toujours le dialogue pour le commentaire)
const rejectExpenseSheet = () => {
    showRejectDialog();
};

// Imprimer la note de frais
const printExpenseSheet = () => {
    window.print();
};

// Télécharger en PDF
const downloadPdf = () => {
    window.location.href = `/expense-sheet/${props.expenseSheet.id}/pdf`;
};
</script>

<style scoped>
@media print {
    .container {
        max-width: 100%;
        padding: 0;
    }
}
</style>

