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
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    BarChart3,
    Euro,
    MapPin
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

interface UserInDepartment {
    id: number;
    name: string;
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
        user?: string;
        dateStart?: string;
        dateEnd?: string;
    };
    departments: string[];
    usersInDepartment: UserInDepartment[];
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
const userFilter = ref(props.filters.user || 'all');
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
        user: userFilter.value !== 'all' ? userFilter.value : undefined,
        dateStart: dateStart.value || undefined,
        dateEnd: dateEnd.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['expenseSheets', 'usersInDepartment'],
    });
};

// Watch pour les changements de filtres (avec debounce pour la recherche)
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch([statusFilter, userFilter, dateStart, dateEnd], () => {
    applyFilters();
});

// Quand le département change, réinitialiser le filtre utilisateur et appliquer les filtres
watch(departmentFilter, () => {
    userFilter.value = 'all';
    applyFilters();
});

// ♻️ Reset des filtres
const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    departmentFilter.value = 'all';
    userFilter.value = 'all';
    dateStart.value = '';
    dateEnd.value = '';
    applyFilters();
};

// 🔎 Filtres actifs ?
const hasActiveFilters = computed(() => {
    return searchQuery.value !== '' ||
        statusFilter.value !== 'all' ||
        departmentFilter.value !== 'all' ||
        userFilter.value !== 'all' ||
        dateStart.value !== '' ||
        dateEnd.value !== '';
});

// 📊 Statistiques
interface Statistics {
    eurosByCategory: Record<string, number>;
    kmByCategory: Record<string, number>;
    totalEuros: number;
    totalKm: number;
    selectedYear: number;
    availableYears: number[];
}

const showStatisticsModal = ref(false);
const statisticsLoading = ref(false);
const statistics = ref<Statistics | null>(null);
const selectedYear = ref(new Date().getFullYear());

const fetchStatistics = async (year?: number) => {
    statisticsLoading.value = true;
    try {
        const yearParam = year ?? selectedYear.value;
        const response = await fetch(route('expense-sheet.statistics') + `?year=${yearParam}`);
        if (response.ok) {
            statistics.value = await response.json();
            selectedYear.value = statistics.value?.selectedYear ?? new Date().getFullYear();
        }
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
    } finally {
        statisticsLoading.value = false;
    }
};

const openStatisticsModal = () => {
    showStatisticsModal.value = true;
    selectedYear.value = new Date().getFullYear();
    fetchStatistics();
};

const closeStatisticsModal = () => {
    showStatisticsModal.value = false;
};

const goToPreviousYear = () => {
    selectedYear.value--;
    fetchStatistics(selectedYear.value);
};

const goToNextYear = () => {
    if (selectedYear.value < new Date().getFullYear()) {
        selectedYear.value++;
        fetchStatistics(selectedYear.value);
    }
};

const canGoToNextYear = computed(() => selectedYear.value < new Date().getFullYear());

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
                    <button @click="openStatisticsModal" class="px-3 sm:px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-md flex items-center justify-center gap-2 text-sm">
                        <BarChart3 class="h-4 w-4" />
                        Statistiques
                    </button>

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
                            <option value="draft">Brouillon</option>
                        </select>
                    </div>

                    <div class="space-y-1 sm:space-y-2">
                        <label for="department-filter" class="text-xs sm:text-sm font-medium">Département</label>
                        <select id="department-filter" v-model="departmentFilter" class="bg-white dark:bg-black w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm">
                            <option value="all">Tous les départements</option>
                            <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>

                    <div v-if="departmentFilter !== 'all' && usersInDepartment.length > 0" class="space-y-1 sm:space-y-2">
                        <label for="user-filter" class="text-xs sm:text-sm font-medium">Demandeur</label>
                        <select id="user-filter" v-model="userFilter" class="bg-white dark:bg-black w-full px-3 py-1.5 sm:py-2 border rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary text-xs sm:text-sm">
                            <option value="all">Tous les demandeurs</option>
                            <option v-for="u in usersInDepartment" :key="u.id" :value="u.id">{{ u.name }}</option>
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

        <!-- Modal Statistiques -->
        <Teleport to="body">
            <div v-if="showStatisticsModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-black/50" @click="closeStatisticsModal"></div>

                <!-- Modal Content -->
                <div class="relative bg-background rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-4 border-b">
                        <div class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5 text-primary" />
                            <h3 class="text-lg font-semibold">Mes statistiques</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Sélecteur d'année -->
                            <div class="flex items-center gap-1 bg-muted rounded-md">
                                <button @click="goToPreviousYear" class="p-2 hover:bg-muted-foreground/10 rounded-l-md transition-colors" :disabled="statisticsLoading">
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <span class="px-3 py-1 font-medium text-sm min-w-[60px] text-center">{{ selectedYear }}</span>
                                <button @click="goToNextYear" class="p-2 hover:bg-muted-foreground/10 rounded-r-md transition-colors" :disabled="!canGoToNextYear || statisticsLoading" :class="{ 'opacity-50 cursor-not-allowed': !canGoToNextYear }">
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>
                            <button @click="closeStatisticsModal" class="p-1 hover:bg-muted rounded-md transition-colors">
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                        <!-- Loading state -->
                        <div v-if="statisticsLoading" class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                        </div>

                        <!-- Statistics content -->
                        <div v-else-if="statistics" class="space-y-6">
                            <!-- Euros remboursés par catégorie -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <Euro class="h-5 w-5 text-green-600" />
                                    <h4 class="font-semibold text-base">Euros remboursés par catégorie</h4>
                                </div>
                                <p class="text-sm text-muted-foreground">Montants des notes de frais approuvées</p>

                                <div v-if="Object.keys(statistics.eurosByCategory).length > 0" class="space-y-2">
                                    <div v-for="(amount, category) in statistics.eurosByCategory" :key="'euro-' + category" class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                                        <span class="text-sm font-medium">{{ category }}</span>
                                        <span class="text-sm font-semibold text-green-600">{{ amount.toFixed(2) }} €</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-green-100 dark:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-800">
                                        <span class="text-sm font-semibold">Total</span>
                                        <span class="text-base font-bold text-green-700 dark:text-green-400">{{ statistics.totalEuros.toFixed(2) }} €</span>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-muted-foreground text-sm">
                                    Aucun remboursement enregistré
                                </div>
                            </div>

                            <!-- Séparateur -->
                            <hr class="border-border" />

                            <!-- KM par catégorie -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <MapPin class="h-5 w-5 text-blue-600" />
                                    <h4 class="font-semibold text-base">Kilomètres par catégorie</h4>
                                </div>
                                <p class="text-sm text-muted-foreground">Distances parcourues (notes approuvées)</p>

                                <div v-if="Object.keys(statistics.kmByCategory).length > 0" class="space-y-2">
                                    <div v-for="(km, category) in statistics.kmByCategory" :key="'km-' + category" class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                                        <span class="text-sm font-medium">{{ category }}</span>
                                        <span class="text-sm font-semibold text-blue-600">{{ km.toFixed(2) }} km</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <span class="text-sm font-semibold">Total</span>
                                        <span class="text-base font-bold text-blue-700 dark:text-blue-400">{{ statistics.totalKm.toFixed(2) }} km</span>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-muted-foreground text-sm">
                                    Aucun trajet enregistré
                                </div>
                            </div>
                        </div>

                        <!-- Error state -->
                        <div v-else class="text-center py-12 text-muted-foreground">
                            <p>Impossible de charger les statistiques.</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end p-4 border-t">
                        <button @click="closeStatisticsModal" class="px-4 py-2 bg-muted hover:bg-muted/80 rounded-md text-sm font-medium transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

