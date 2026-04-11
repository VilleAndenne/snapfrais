<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Statistiques" />

        <div class="p-3 sm:p-4 space-y-4">
            <!-- Header -->
            <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Statistiques</h2>
                    <p class="text-sm text-muted-foreground mt-1">Vue d'ensemble des notes de frais pour l'année {{ selectedYear }}</p>
                </div>

                <div class="w-full sm:w-48">
                    <Select :model-value="String(selectedYear)" @update:model-value="changeYear">
                        <SelectTrigger>
                            <SelectValue placeholder="Année" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="year in availableYears" :key="year" :value="String(year)">
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </header>

            <!-- KPIs -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm text-muted-foreground">Total notes</span>
                        <FileTextIcon class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <p class="text-2xl font-semibold mt-2">{{ kpis.totalSheets }}</p>
                </Card>

                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm text-muted-foreground">Montant approuvé</span>
                        <EuroIcon class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <p class="text-2xl font-semibold mt-2">{{ formatCurrency(kpis.totalApprovedAmount) }}</p>
                </Card>

                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm text-muted-foreground">Taux d'approbation</span>
                        <CheckCircle2Icon class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <p class="text-2xl font-semibold mt-2">{{ kpis.approvalRate }} %</p>
                </Card>

                <Card class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs sm:text-sm text-muted-foreground">Moyenne / note</span>
                        <TrendingUpIcon class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <p class="text-2xl font-semibold mt-2">{{ formatCurrency(kpis.averageApprovedAmount) }}</p>
                </Card>
            </div>

            <!-- Km KPI with transport filter -->
            <Card class="p-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <component :is="transportIcon" class="h-5 w-5 text-muted-foreground" />
                        <div>
                            <p class="text-xs sm:text-sm text-muted-foreground">Kilomètres parcourus ({{ transportLabel }})</p>
                            <p class="text-2xl font-semibold mt-1">{{ formatKm(currentTransportKm) }}</p>
                            <p class="text-xs text-muted-foreground mt-1">Total tous véhicules : {{ formatKm(kpis.totalApprovedKm) }}</p>
                        </div>
                    </div>

                    <div class="w-full sm:w-48">
                        <Select v-model="selectedTransport">
                            <SelectTrigger>
                                <SelectValue placeholder="Véhicule" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in transportOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </Card>

            <!-- Status breakdown -->
            <Card class="p-4">
                <h3 class="text-sm font-medium mb-3">Répartition par statut</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-lg border border-border p-3">
                        <div class="flex items-center gap-2">
                            <Badge variant="default">Approuvées</Badge>
                        </div>
                        <p class="text-xl font-semibold mt-2">{{ kpis.approvedSheets }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <div class="flex items-center gap-2">
                            <Badge variant="destructive">Refusées</Badge>
                        </div>
                        <p class="text-xl font-semibold mt-2">{{ kpis.refusedSheets }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <div class="flex items-center gap-2">
                            <Badge variant="secondary">En attente</Badge>
                        </div>
                        <p class="text-xl font-semibold mt-2">{{ kpis.pendingSheets }}</p>
                    </div>
                    <div class="rounded-lg border border-border p-3">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline">Brouillons</Badge>
                        </div>
                        <p class="text-xl font-semibold mt-2">{{ kpis.draftSheets }}</p>
                    </div>
                </div>
            </Card>

            <!-- Monthly chart -->
            <Card class="p-4">
                <h3 class="text-sm font-medium mb-4">Montants approuvés par mois</h3>
                <div v-if="maxMonthlyAmount > 0" class="flex items-end gap-2 h-48">
                    <div
                        v-for="entry in monthlyStats"
                        :key="entry.month"
                        class="flex-1 flex flex-col items-center gap-1 h-full"
                    >
                        <div class="flex-1 w-full flex items-end">
                            <div
                                class="w-full bg-primary rounded-t transition-all hover:opacity-80"
                                :style="{ height: (entry.total_amount / maxMonthlyAmount * 100) + '%' }"
                                :title="`${entry.label}: ${formatCurrency(entry.total_amount)} (${entry.sheets_count} note${entry.sheets_count > 1 ? 's' : ''})`"
                            />
                        </div>
                        <span class="text-[10px] sm:text-xs text-muted-foreground capitalize">{{ entry.label }}</span>
                    </div>
                </div>
                <div v-else class="h-48 flex items-center justify-center text-sm text-muted-foreground">
                    Aucune donnée pour cette année
                </div>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Top departments -->
                <Card>
                    <div class="p-4 border-b border-border">
                        <h3 class="text-sm font-medium">Top départements</h3>
                        <p class="text-xs text-muted-foreground mt-1">Montants approuvés</p>
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="text-xs">Département</TableHead>
                                <TableHead class="text-xs text-right">Notes</TableHead>
                                <TableHead class="text-xs text-right">Montant</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in byDepartment" :key="row.department">
                                <TableCell class="text-xs">{{ row.department }}</TableCell>
                                <TableCell class="text-xs text-right">{{ row.sheets_count }}</TableCell>
                                <TableCell class="text-xs text-right font-medium">{{ formatCurrency(row.total_amount) }}</TableCell>
                            </TableRow>
                            <TableRow v-if="byDepartment.length === 0">
                                <TableCell colspan="3" class="text-center text-xs text-muted-foreground h-16">
                                    Aucune donnée
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>

                <!-- Top users -->
                <Card>
                    <div class="p-4 border-b border-border">
                        <h3 class="text-sm font-medium">Top utilisateurs</h3>
                        <p class="text-xs text-muted-foreground mt-1">Montants approuvés</p>
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="text-xs">Utilisateur</TableHead>
                                <TableHead class="text-xs text-right">Notes</TableHead>
                                <TableHead class="text-xs text-right">Montant</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in topUsers" :key="row.user">
                                <TableCell class="text-xs">{{ row.user }}</TableCell>
                                <TableCell class="text-xs text-right">{{ row.sheets_count }}</TableCell>
                                <TableCell class="text-xs text-right font-medium">{{ formatCurrency(row.total_amount) }}</TableCell>
                            </TableRow>
                            <TableRow v-if="topUsers.length === 0">
                                <TableCell colspan="3" class="text-center text-xs text-muted-foreground h-16">
                                    Aucune donnée
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </div>

            <!-- By form -->
            <Card>
                <div class="p-4 border-b border-border">
                    <h3 class="text-sm font-medium">Par formulaire</h3>
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="text-xs">Formulaire</TableHead>
                            <TableHead class="text-xs text-right">Notes approuvées</TableHead>
                            <TableHead class="text-xs text-right">Montant total</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="row in byForm" :key="row.form">
                            <TableCell class="text-xs">{{ row.form }}</TableCell>
                            <TableCell class="text-xs text-right">{{ row.sheets_count }}</TableCell>
                            <TableCell class="text-xs text-right font-medium">{{ formatCurrency(row.total_amount) }}</TableCell>
                        </TableRow>
                        <TableRow v-if="byForm.length === 0">
                            <TableCell colspan="3" class="text-center text-xs text-muted-foreground h-16">
                                Aucune donnée
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BikeIcon, CarIcon, CheckCircle2Icon, EuroIcon, FileTextIcon, TrendingUpIcon } from 'lucide-vue-next';

import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell,
} from '@/components/ui/table';
import {
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectItem,
} from '@/components/ui/select';

import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    selectedYear: { type: Number, required: true },
    availableYears: { type: Array, default: () => [] },
    kpis: { type: Object, required: true },
    monthlyStats: { type: Array, default: () => [] },
    byDepartment: { type: Array, default: () => [] },
    byForm: { type: Array, default: () => [] },
    topUsers: { type: Array, default: () => [] },
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Statistiques' },
];

const maxMonthlyAmount = computed(() => {
    return props.monthlyStats.reduce((max, entry) => Math.max(max, entry.total_amount), 0);
});

const transportOptions = [
    { value: 'car', label: 'Voiture' },
    { value: 'bike', label: 'Vélo' },
];

const selectedTransport = ref('car');

const transportLabel = computed(() => {
    return transportOptions.find((option) => option.value === selectedTransport.value)?.label ?? '';
});

const transportIcon = computed(() => (selectedTransport.value === 'bike' ? BikeIcon : CarIcon));

const currentTransportKm = computed(() => {
    return props.kpis.kmByTransport?.[selectedTransport.value] ?? 0;
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0);
};

const formatKm = (value) => {
    return `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(value || 0)} km`;
};

const changeYear = (value) => {
    router.get('/admin/statistics', { year: value }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
