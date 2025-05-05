<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import {
    FileText,
    CheckCircle,
    Clock,
    AlertTriangle,
    Calendar,
    Search,
    Filter,
    X,
    ChevronDown
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    expenseSheets: Array<{
        id: number;
        type: string;
        distance: number;
        route: string;
        total: number;
        approved: boolean | null;
        created_at: string;
        form: {
            name: string;
        };
        department?: {
            name: string;
        };
        user?: {
            name: string;
        };
    }>,
    canExport: boolean;
}>();

// 🔁 Mapping approved → statut lisible
const getStatusFromApproved = (approved: boolean | null): 'approved' | 'pending' | 'rejected' => {
    if (approved == true) return 'approved';
    if (approved == false) return 'rejected';
    return 'pending';
};

// 🏷️ Badge & label
const getStatusLabel = (sheet: { approved: boolean | null }) => {
    const status = getStatusFromApproved(sheet.approved);
    switch (status) {
        case 'approved':
            return { label: 'Approuvée', variant: 'success' };
        case 'pending':
            return { label: 'En attente', variant: 'warning' };
        case 'rejected':
            return { label: 'Rejetée', variant: 'destructive' };
        default:
            return { label: 'Inconnu', variant: 'default' };
    }
};

// 🎯 Icône statut
const getStatusIcon = (approved: boolean | null) => {
    const status = getStatusFromApproved(approved);
    switch (status) {
        case 'approved':
            return CheckCircle;
        case 'pending':
            return Clock;
        case 'rejected':
            return AlertTriangle;
        default:
            return FileText;
    }
};

// 📅 Format de date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
};

// 🎛️ Filtres
const searchQuery = ref('');
const statusFilter = ref('all');
const departmentFilter = ref('all');
const dateStart = ref('');
const dateEnd = ref('');
const isFilterOpen = ref(false);

// ♻️ Reset des filtres
const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    departmentFilter.value = 'all';
    dateStart.value = '';
    dateEnd.value = '';
};

// 🏢 Options des départements
const departmentOptions = computed(() => {
    const unique = new Set(props.expenseSheets.map(s => s.department?.name || 'Inconnu'));
    return [...Array.from(unique)];
});

// 📊 Filtrage principal
const filteredExpenseSheets = computed(() => {
    return props.expenseSheets.filter(sheet => {
        const status = getStatusFromApproved(sheet.approved);
        const matchesSearch = sheet.form.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all' || status === statusFilter.value;
        const matchesDepartment = departmentFilter.value === 'all' || sheet.department?.name === departmentFilter.value;

        const createdAt = new Date(sheet.created_at);
        const start = dateStart.value ? new Date(dateStart.value) : null;
        const end = dateEnd.value ? new Date(dateEnd.value) : null;
        const matchesDate = (!start || createdAt >= start) && (!end || createdAt <= end);

        return matchesSearch && matchesStatus && matchesDepartment && matchesDate;
    });
});

// 🔎 Filtres actifs ?
const hasActiveFilters = computed(() => {
    return searchQuery.value !== '' ||
        statusFilter.value !== 'all' ||
        departmentFilter.value !== 'all' ||
        dateStart.value !== '' ||
        dateEnd.value !== '';
});

const breadcrumbs = [
    {
        title: 'Feuilles de frais',
        href: route('expense-sheet.index')
    }
];
</script>

<template>
    <AppLayout title="Historique des notes de frais" description="Consultez l'historique de vos notes de frais." :breadcrumbs="breadcrumbs">
        <Head title="Historique des notes de frais" />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Notes de frais</h2>

                <div class="flex flex-col xs:flex-row gap-2 w-full sm:w-auto">
                    <Link v-if="canExport" :href="route('export')" class="px-3 sm:px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md flex items-center justify-center gap-2 text-sm">
                        <FileText class="h-4 w-4" />
                        Exporter
                    </Link>

                    <div class="relative w-full">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <input v-model="searchQuery" type="text" placeholder="Rechercher une note de frais..." class="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm" />
                        <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Panneau de filtres -->
            <div class="border rounded-lg overflow-hidden">
                <button @click="isFilterOpen = !isFilterOpen" class="w-full flex items-center justify-between p-3 sm:p-4 bg-muted/30 hover:bg-muted/50 transition-colors">
                    <div class="flex items-center gap-2">
                        <Filter class="h-4 w-4" />
                        <span class="font-medium text-sm sm:text-base">Filtres</span>
                        <Badge v-if="hasActiveFilters" variant="secondary" class="ml-2 text-xs">Filtres actifs</Badge>
                    </div>
                    <ChevronDown class="h-4 w-4 sm:h-5 sm:w-5 transition-transform" :class="{ 'transform rotate-180': isFilterOpen }" />
                </button>

                <div v-if="isFilterOpen" class="p-3 sm:p-4 grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 bg-background">
                    <div class="space-y-1 sm:space-y-2">
                        <label for="status-filter" class="text-xs sm:text-sm font-medium">Statut</label>
                        <select id="status-filter" v-model="statusFilter" class="w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm">
                            <option value="all">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="approved">Approuvée</option>
                            <option value="rejected">Rejetée</option>
                        </select>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label for="department-filter" class="text-xs sm:text-sm font-medium">Département</label>
                        <select id="department-filter" v-model="departmentFilter" class="w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm">
                            <option value="all">Tous les départements</option>
                            <option v-for="d in departmentOptions" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label for="date-start" class="text-xs sm:text-sm font-medium">Date de début</label>
                        <input id="date-start" v-model="dateStart" type="date" class="w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label for="date-end" class="text-xs sm:text-sm font-medium">Date de fin</label>
                        <input id="date-end" v-model="dateEnd" type="date" class="w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm" />
                    </div>

                    <div class="col-span-full flex justify-end">
                        <button @click="resetFilters" class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm bg-muted hover:bg-muted/80 rounded-md flex items-center gap-2 transition-colors" :disabled="!hasActiveFilters" :class="{ 'opacity-50 cursor-not-allowed': !hasActiveFilters }">
                            <X class="h-3 w-3 sm:h-4 sm:w-4" />
                            Réinitialiser les filtres
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <Badge variant="outline" class="px-2 sm:px-3 py-0.5 sm:py-1 text-xs">
                    {{ filteredExpenseSheets.length }} note(s) trouvée(s)
                </Badge>
            </div>

            <!-- Tableau -->
            <div class="overflow-x-auto border rounded-xl">
                <table class="min-w-full divide-y">
                    <thead class="bg-muted">
                    <tr>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs uppercase">Type</th>
                        <th class="hidden sm:table-cell px-3 sm:px-6 py-2 sm:py-3 text-left text-xs uppercase">Demandeur</th>
                        <th class="hidden md:table-cell px-3 sm:px-6 py-2 sm:py-3 text-left text-xs uppercase">Service</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs uppercase">Montant (€)</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-left text-xs uppercase">Statut</th>
                        <th class="hidden sm:table-cell px-3 sm:px-6 py-2 sm:py-3 text-left text-xs uppercase">Créé le</th>
                        <th class="px-3 sm:px-6 py-2 sm:py-3 text-right text-xs uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="sheet in filteredExpenseSheets" :key="sheet.id" class="hover:bg-muted/50 transition-colors">
                        <td class="px-3 sm:px-6 py-2 sm:py-4">
                            <div class="flex items-center">
                                <component :is="getStatusIcon(sheet.approved)" class="mr-1 sm:mr-2 h-4 sm:h-5 w-4 sm:w-5" />
                                <span class="text-xs sm:text-sm font-medium">{{ sheet.form.name }}</span>
                            </div>
                        </td>
                        <td class="hidden sm:table-cell px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm">{{ sheet.user?.name || 'Inconnu' }}</td>
                        <td class="hidden md:table-cell px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm">{{ sheet.department?.name || 'Inconnu' }}</td>
                        <td class="px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm font-semibold">{{ sheet.total }} €</td>
                        <td class="px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm">
                            <Badge :variant="getStatusLabel(sheet).variant" class="text-xs">
                                {{ getStatusLabel(sheet).label }}
                            </Badge>
                        </td>
                        <td class="hidden sm:table-cell px-3 sm:px-6 py-2 sm:py-4 text-xs sm:text-sm">
                            <div class="flex items-center">
                                <Calendar class="mr-1 h-3 sm:h-4 w-3 sm:w-4" />
                                {{ formatDate(sheet.created_at) }}
                            </div>
                        </td>
                        <td class="px-3 sm:px-6 py-2 sm:py-4 text-right text-xs sm:text-sm">
                            <Link :href="'/expense-sheet/' + sheet.id">
                                <button class="px-2 sm:px-3 py-1 border rounded-md text-xs sm:text-sm">Voir</button>
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div v-if="filteredExpenseSheets.length === 0" class="text-center p-4 sm:p-6 text-muted-foreground">
                    <FileText class="mx-auto h-8 sm:h-12 w-8 sm:w-12 mb-3 sm:mb-4" />
                    <p class="text-xs sm:text-sm">Aucune note de frais trouvée selon vos critères.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Assurer que les tableaux sont responsifs */
@media (max-width: 768px) {
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
}

/* Ajout d'une classe utilitaire pour les très petits écrans */
@media (min-width: 480px) {
    .xs\:flex-row {
        flex-direction: row;
    }
}
</style>
