<template>
    <div class="p-8 text-foreground bg-white">
        <!-- En-tête -->
        <div class="mb-8 flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold">Note de frais #{{ expenseSheet.id }}</h1>
                <p class="text-sm text-muted-foreground">Créée le {{ formatDate(expenseSheet.created_at) }}</p>
            </div>
            <div>
                <Badge :variant="getStatusLabel(expenseSheet).variant">
                    {{ getStatusLabel(expenseSheet).label }}
                </Badge>
            </div>
        </div>

        <!-- Refus -->
        <div v-if="expenseSheet.approved === false"
             class="border border-destructive bg-destructive/10 p-4 rounded mb-8">
            <h2 class="text-lg font-semibold text-destructive mb-2">Note de frais refusée</h2>
            <p><strong>Refusée par :</strong> {{ expenseSheet.validated_by.name }}</p>
            <p><strong>Motif :</strong> {{ expenseSheet.refusal_reason }}</p>
        </div>

        <!-- Informations générales -->
        <section class="mb-8">
            <h2 class="text-2xl font-semibold mb-4">Informations générales</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-semibold text-muted-foreground">Demandeur</p>
                    <p>{{ expenseSheet.user.name }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-muted-foreground">Département</p>
                    <p>{{ expenseSheet.department?.name || 'Non spécifié' }}</p>
                </div>
            </div>
        </section>

        <!-- Détails des coûts -->
        <section>
            <h2 class="text-2xl font-semibold mb-6">Détails des coûts</h2>
            <div v-for="(cost, index) in expenseSheet.costs" :key="index" class="mb-6 border p-4 rounded">
                <h3 class="text-xl font-bold mb-2">{{ cost.form_cost.name }}</h3>
                <p class="text-sm italic mb-2">{{ getActiveRate(cost, cost.date) }} / {{ cost.type }}</p>
                <p class="mb-2">{{ cost.description }}</p>

                <p class="text-sm"><strong>Date du coût :</strong> {{ formatDate(cost.date) }}</p>

                <!-- Route -->
                <div v-if="cost.route" class="mt-2">
                    <p class="font-semibold">Route :</p>
                    <ul class="list-disc pl-5">
                        <li><strong>Départ :</strong> {{ cost.route.departure }}</li>
                        <li v-if="cost.route.steps && cost.route.steps.length">
                            <strong>Étapes :</strong>
                            <ul class="list-decimal pl-5">
                                <li v-for="(step, idx) in cost.route.steps" :key="idx">{{ step.address }}</li>
                            </ul>
                        </li>
                        <li><strong>Arrivée :</strong> {{ cost.route.arrival }}</li>
                        <li><strong>Distance Google :</strong> {{ cost.route.google_km }} km</li>
                        <li v-if="cost.route.manual_km"><strong>Distance manuelle :</strong> {{ cost.route.manual_km }}
                            km
                        </li>
                        <li v-if="cost.route.justification"><strong>Justification :</strong> {{ cost.route.justification
                            }}
                        </li>
                    </ul>
                </div>

                <!-- Annexes -->
                <div v-if="cost.requirements" class="mt-2">
                    <p class="font-semibold">Annexes :</p>
                    <ul class="list-disc pl-5">
                        <li v-for="(requirement, key) in parseRequirements(cost.requirements)" :key="key">
                            <strong>{{ key }} :</strong> <span v-if="requirement.file">[Fichier]</span><span
                            v-else>{{ requirement.value }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Montants -->
                <div class="mt-2">
                    <p><strong>Montant payé :</strong> {{ formatCurrency(cost.amount) }}</p>
                    <p><strong>Montant dû estimé :</strong> {{ formatCurrency(cost.total) }}</p>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">import { ref, computed } from 'vue';
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

<style scoped>
/* Pour éviter des effets indésirables sur PDF */
p, h1, h2, h3, ul, li, div {
    page-break-inside: avoid;
}
</style>
