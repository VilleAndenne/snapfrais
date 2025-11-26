<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
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
import Pagination from '@/components/Pagination.vue';

interface ExpenseSheet {
    id: number;
    type: string;
    distance: number;
    route: string;
    total: number;
    approved: boolean | null;
    is_draft: boolean;
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
}

interface PaginatedData {
    data: ExpenseSheet[];
    current_page: number;
    from: number;
    last_page: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

const props = defineProps<{
    expenseSheets: PaginatedData;
    canExport: boolean;
    filters: {
        search?: string;
        status?: string;
        department?: string;
        dateStart?: string;
        dateEnd?: string;
    };
}>();

// 🔁 Mapping approved → statut lisible
const getStatusFromApproved = (approved: boolean | null, isDraft: boolean): 'approved' | 'pending' | 'rejected' | 'draft' => {
    if (isDraft) return 'draft';
    if (approved == true) return 'approved';
    if (approved == false) return 'rejected';
    return 'pending';
};

// 🏷️ Badge & label
const getStatusLabel = (sheet: { approved: boolean | null, is_draft: boolean }) => {
    const status = getStatusFromApproved(sheet.approved, sheet.is_draft);
    switch (status) {
        case 'approved':
            return { label: 'Approuvée', variant: 'success' };
        case 'pending':
            return { label: 'En attente', variant: 'warning' };
        case 'rejected':
            return { label: 'Rejetée', variant: 'destructive' };
        case 'draft':
            return { label: 'Brouillon', variant: 'secondary' };
        default:
            return { label: 'Inconnu', variant: 'default' };
    }
};

// 🎯 Icône statut
const getStatusIcon = (approved: boolean | null, isDraft: boolean) => {
    const status = getStatusFromApproved(approved, isDraft);
    switch (status) {
        case 'approved':
            return CheckCircle;
        case 'pending':
            return Clock;
        case 'rejected':
            return AlertTriangle;
        case 'draft':
            return FileText;
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

// 🎛️ Filtres - Initialisation avec les valeurs du serveur
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const departmentFilter = ref(props.filters.department || 'all');
const dateStart = ref(props.filters.dateStart || '');
const dateEnd = ref(props.filters.dateEnd || '');
const isFilterOpen = ref(false);

// Debounce pour la recherche
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// Fonction pour appliquer les filtres côté serveur
const applyFilters = () => {
    router.get(route('expense-sheet.index'), {
        search: searchQuery.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        department: departmentFilter.value !== 'all' ? departmentFilter.value : undefined,
        dateStart: dateStart.value || undefined,
        dateEnd: dateEnd.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['expenseSheets'],
    });
};

// Watch pour les changements de filtres (avec debounce pour la recherche)
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch([statusFilter, departmentFilter, dateStart, dateEnd], () => {
    applyFilters();
});

// ♻️ Reset des filtres
const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    departmentFilter.value = 'all';
    dateStart.value = '';
    dateEnd.value = '';
    applyFilters();
};

// 🏢 Options des départements
const departmentOptions = computed(() => {
    const unique = new Set(props.expenseSheets.data.map(s => s.department?.name || 'Inconnu'));
    return [...Array.from(unique)];
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
                        <select id="status-filter" v-model="statusFilter" class="bg-white dark:bg-black  w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm">
                            <option value="all">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="approved">Approuvée</option>
                            <option value="rejected">Rejetée</option>
                        </select>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label for="department-filter" class="text-xs sm:text-sm font-medium">Département</label>
                        <select id="department-filter" v-model="departmentFilter" class="bg-white dark:bg-black w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm">
                            <option value="all">Tous les départements</option>
                            <option v-for="d in departmentOptions" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label for="date-start" class="text-xs sm:text-sm font-medium">Date de début</label>
                        <input id="date-start" v-model="dateStart" type="date" class="bg-white dark:bg-black w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm" />
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label for="date-end" class="text-xs sm:text-sm font-medium">Date de fin</label>
                        <input id="date-end" v-model="dateEnd" type="date" class="bg-white dark:bg-black w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm" />
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
                    {{ expenseSheets.total }} note(s) trouvée(s)
                </Badge>
            </div>

            <!-- Vue Mobile (cartes) - visible uniquement sur mobile -->
            <div class="md:hidden space-y-3">
                <Link
                    v-for="sheet in expenseSheets.data"
                    :key="sheet.id"
                    :href="'/expense-sheet/' + sheet.id"
                    class="block"
                >
                    <div class="border rounded-lg p-4 bg-card hover:bg-muted/50 transition-colors space-y-3">
                        <!-- Header de la carte -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-2 flex-1 min-w-0">
                                <component :is="getStatusIcon(sheet.approved, sheet.is_draft)" class="h-5 w-5 flex-shrink-0 mt-0.5" />
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-sm truncate">{{ sheet.form.name }}</h3>
                                    <p class="text-xs text-muted-foreground">{{ sheet.user?.name || 'Inconnu' }}</p>
                                </div>
                            </div>
                            <Badge :variant="getStatusLabel(sheet).variant" class="text-xs flex-shrink-0">
                                {{ getStatusLabel(sheet).label }}
                            </Badge>
                        </div>

                        <!-- Informations -->
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Département</span>
                                <span class="font-medium">{{ sheet.department?.name || 'Inconnu' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Montant</span>
                                <span class="font-semibold text-sm">{{ sheet.total }} €</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Date de création</span>
                                <div class="flex items-center gap-1">
                                    <Calendar class="h-3 w-3" />
                                    <span>{{ formatDate(sheet.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton d'action -->
                        <div class="pt-2 border-t">
                            <button class="w-full px-3 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md text-sm font-medium transition-colors">
                                Voir les détails
                            </button>
                        </div>
                    </div>
                </Link>

                <div v-if="expenseSheets.data.length === 0" class="text-center p-8 text-muted-foreground border rounded-lg">
                    <FileText class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-sm">Aucune note de frais trouvée selon vos critères.</p>
                </div>

                <!-- Pagination Mobile -->
                <Pagination
                    v-if="expenseSheets.data.length > 0"
                    :pagination="expenseSheets"
                />
            </div>

            <!-- Vue Desktop (tableau) - visible sur tablette et + -->
            <div class="hidden md:block overflow-x-auto border rounded-xl">
                <table class="min-w-full divide-y">
                    <thead class="bg-muted">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Type</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Demandeur</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Service</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Montant (€)</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Statut</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Créé le</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="sheet in expenseSheets.data" :key="sheet.id" class="hover:bg-muted/50 transition-colors">
                        <td class="px-4 lg:px-6 py-4">
                            <div class="flex items-center gap-2">
                                <component :is="getStatusIcon(sheet.approved, sheet.is_draft)" class="h-5 w-5 flex-shrink-0" />
                                <span class="text-sm font-medium">{{ sheet.form.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 lg:px-6 py-4 text-sm">{{ sheet.user?.name || 'Inconnu' }}</td>
                        <td class="px-4 lg:px-6 py-4 text-sm">{{ sheet.department?.name || 'Inconnu' }}</td>
                        <td class="px-4 lg:px-6 py-4 text-sm font-semibold">{{ sheet.total }} €</td>
                        <td class="px-4 lg:px-6 py-4">
                            <Badge :variant="getStatusLabel(sheet).variant" class="text-xs">
                                {{ getStatusLabel(sheet).label }}
                            </Badge>
                        </td>
                        <td class="px-4 lg:px-6 py-4 text-sm">
                            <div class="flex items-center gap-1">
                                <Calendar class="h-4 w-4" />
                                {{ formatDate(sheet.created_at) }}
                            </div>
                        </td>
                        <td class="px-4 lg:px-6 py-4 text-right">
                            <Link :href="'/expense-sheet/' + sheet.id">
                                <button class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors">
                                    Voir
                                </button>
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div v-if="expenseSheets.data.length === 0" class="text-center p-8 text-muted-foreground">
                    <FileText class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p class="text-sm">Aucune note de frais trouvée selon vos critères.</p>
                </div>
            </div>

            <!-- Pagination Desktop -->
            <Pagination
                v-if="expenseSheets.data.length > 0"
                :pagination="expenseSheets"
                class="hidden md:block"
            />
        </div>
    </AppLayout>
</template>

