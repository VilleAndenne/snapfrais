<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { getStatusLabel } from '@/utils/formatters';
import {
    FileText,
    CheckCircle,
    Clock,
    AlertTriangle,
    Calendar,
    Search,
    Filter
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    expenseSheets: Array<{
        id: number;
        type: string;
        distance: number;
        route: string;
        total: number;
        status: string;
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
    }>;
}>();

const searchQuery = ref('');
const statusFilter = ref('all');
const departmentFilter = ref('all');
const dateStart = ref('');
const dateEnd = ref('');

const getStatusIcon = (status) => {
    switch (status) {
        case 'approved': return CheckCircle;
        case 'pending': return Clock;
        case 'rejected': return AlertTriangle;
        default: return FileText;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
};

const departmentOptions = computed(() => {
    const unique = new Set(props.expenseSheets.map(s => s.department?.name || 'Inconnu'));
    return [...Array.from(unique)];
});

const filteredExpenseSheets = computed(() => {
    return props.expenseSheets.filter(sheet => {
        const matchesSearch = sheet.form.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all' || sheet.status === statusFilter.value;
        const matchesDepartment = departmentFilter.value === 'all' || sheet.department?.name === departmentFilter.value;

        const createdAt = new Date(sheet.created_at);
        const start = dateStart.value ? new Date(dateStart.value) : null;
        const end = dateEnd.value ? new Date(dateEnd.value) : null;

        const matchesDate =
            (!start || createdAt >= start) &&
            (!end || createdAt <= end);

        return matchesSearch && matchesStatus && matchesDepartment && matchesDate;
    });
});
</script>

<template>
    <AppLayout title="Historique des notes de frais" description="Consultez l'historique de vos notes de frais.">
        <Head title="Historique des notes de frais" />

        <div class="p-6 space-y-6">
            <h2 class="text-2xl font-semibold tracking-tight">Notes de frais</h2>

            <!-- Filtres -->
            <div class="flex flex-wrap gap-4 items-center">
                <div class="relative flex-grow max-w-xs">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <input v-model="searchQuery" type="text" placeholder="Recherche..." class="pl-10 pr-4 py-2 border rounded-md w-full" />
                </div>

                <div class="flex items-center gap-2">
                    <Filter class="h-4 w-4 text-muted-foreground" />
                    <select v-model="statusFilter" class="px-3 py-2 border rounded-md">
                        <option value="all">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="approved">Approuvée</option>
                        <option value="rejected">Rejetée</option>
                    </select>
                </div>

                <div>
                    <select v-model="departmentFilter" class="px-3 py-2 border rounded-md">
                        <option value="all">Tous les départements</option>
                        <option v-for="d in departmentOptions" :key="d" :value="d">{{ d }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted-foreground">Du</label>
                    <input v-model="dateStart" type="date" class="px-3 py-2 border rounded-md" />
                    <label class="text-sm text-muted-foreground">au</label>
                    <input v-model="dateEnd" type="date" class="px-3 py-2 border rounded-md" />
                </div>
            </div>

            <!-- Résultat -->
            <div>
                <Badge variant="outline" class="px-3 py-1">
                    {{ filteredExpenseSheets.length }} note(s) trouvée(s)
                </Badge>
            </div>

            <!-- Tableau -->
            <div class="overflow-x-auto border rounded-xl">
                <table class="min-w-full divide-y">
                    <thead class="bg-muted">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs uppercase">Demandeur</th>
                        <th class="px-6 py-3 text-left text-xs uppercase">Service</th>
                        <th class="px-6 py-3 text-left text-xs uppercase">Montant (€)</th>
                        <th class="px-6 py-3 text-left text-xs uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs uppercase">Créé le</th>
                        <th class="px-6 py-3 text-right text-xs uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="sheet in filteredExpenseSheets" :key="sheet.id" class="hover:bg-muted/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <component :is="getStatusIcon(sheet.status)" class="mr-2 h-5 w-5" />
                                <span class="text-sm font-medium">{{ sheet.form.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            {{ sheet.user?.name || 'Inconnu' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            {{ sheet.department?.name || 'Inconnu' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold">{{ sheet.total }} €</td>
                        <td class="px-6 py-4 text-sm">
                            <Badge :variant="getStatusLabel(sheet).variant">
                                {{ getStatusLabel(sheet).label }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 text-sm flex items-center">
                            <Calendar class="mr-1 h-4 w-4" />
                            {{ formatDate(sheet.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <Link :href="'/expense-sheet/' + sheet.id" class="bg-primary text-white px-3 py-1.5 rounded-md text-sm hover:bg-primary/90">
                                Voir
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <div v-if="filteredExpenseSheets.length === 0" class="text-center p-6 text-muted-foreground">
                    <FileText class="mx-auto h-12 w-12 mb-4" />
                    Aucune note de frais trouvée selon vos critères.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
