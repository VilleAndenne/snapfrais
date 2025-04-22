<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { getStatusLabel } from '../utils/formatters';
import { ref, computed } from 'vue';
import { 
  FileText, 
  CheckCircle, 
  Clock, 
  AlertTriangle, 
  ChevronRight, 
  Eye, 
  Filter, 
  Search,
  Calendar
} from 'lucide-vue-next';

const props = defineProps<{
  forms: Array<{
    id: number;
    name: string;
    description: string;
  }>;
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
  }>;
  expenseToValidate: Array<{
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
    user?: {
      name: string;
    };
  }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tableau de bord',
    href: '/dashboard'
  }
];

const goToForm = (formId) => {
  router.visit('/expense-sheet/' + formId + '/create');
};

const searchQuery = ref('');
const statusFilter = ref('all');

const filteredExpenseSheets = computed(() => {
  return props.expenseSheets.filter(sheet => {
    const matchesSearch = sheet.form.name.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesStatus = statusFilter.value === 'all' || sheet.status === statusFilter.value;
    return matchesSearch && matchesStatus;
  });
});

const filteredExpenseToValidate = computed(() => {
  return props.expenseToValidate.filter(sheet => {
    const matchesSearch = sheet.form.name.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesStatus = statusFilter.value === 'all' || sheet.status === statusFilter.value;
    return matchesSearch && matchesStatus;
  });
});

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const getStatusIcon = (status) => {
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
</script>

<template>
  <Head title="Tableau de bord" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
      <!-- Formulaires disponibles -->
      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-bold">Formulaires disponibles</h2>
        </div>
        
        <div class="grid auto-rows-min gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          <div v-for="form in forms" :key="form.id" @click="goToForm(form.id)"
            class="group relative cursor-pointer overflow-hidden rounded-xl border border-border bg-card p-5 shadow-sm transition-all duration-300 hover:border-primary/30 hover:shadow-md">
            <div class="flex flex-col h-full">
              <h3 class="text-lg font-semibold text-card-foreground group-hover:text-primary transition-colors">{{ form.name }}</h3>
              <p class="mt-2 text-sm text-muted-foreground flex-grow">{{ form.description }}</p>
              <div class="mt-4 flex justify-end">
                <div class="rounded-full bg-primary/10 p-2 text-primary transition-all group-hover:bg-primary group-hover:text-primary-foreground">
                  <ChevronRight class="h-4 w-4" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Filtres -->
      <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
        <div class="relative flex-grow max-w-md">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Rechercher une note de frais..."
            class="h-10 w-full rounded-md border border-input bg-background pl-10 pr-4 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>
        <div class="flex items-center gap-2">
          <Filter class="h-4 w-4 text-muted-foreground" />
          <select
            v-model="statusFilter"
            class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            <option value="all">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="approved">Approuvé</option>
            <option value="rejected">Rejeté</option>
          </select>
        </div>
      </div>

      <!-- Mes notes de frais -->
      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-bold">Mes notes de frais</h2>
          <Badge variant="outline" class="px-3 py-1">
            {{ filteredExpenseSheets.length }} note(s)
          </Badge>
        </div>
        
        <div class="rounded-xl border border-border bg-card overflow-hidden">
          <div v-if="filteredExpenseSheets.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
              <thead class="bg-muted">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    Type
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    Montant (€)
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    Statut
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    Créé le
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border bg-card">
                <tr v-for="sheet in filteredExpenseSheets" :key="sheet.id" 
                    class="hover:bg-muted/50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <component :is="getStatusIcon(sheet.status)" 
                                class="mr-2 h-5 w-5" 
                                :class="{
                                  'text-warning': sheet.status === 'pending',
                                  'text-success': sheet.status === 'approved',
                                  'text-destructive': sheet.status === 'rejected',
                                  'text-muted-foreground': !sheet.status
                                }" />
                      <span class="text-sm font-medium text-card-foreground">{{ sheet.form.name }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-semibold text-card-foreground">{{ sheet.total.toFixed(2) }} €</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <Badge :variant="getStatusLabel(sheet).variant">
                      {{ getStatusLabel(sheet).label }}
                    </Badge>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center text-sm text-muted-foreground">
                      <Calendar class="mr-1 h-4 w-4" />
                      {{ formatDate(sheet.created_at) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <Link :href="'/expense-sheet/' + sheet.id" class="inline-flex items-center rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                      <Eye class="mr-1.5 h-4 w-4" />
                      Voir
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-12 px-4 text-center">
            <FileText class="h-12 w-12 text-muted-foreground mb-4" />
            <h3 class="text-lg font-medium text-card-foreground">Aucune note de frais</h3>
            <p class="mt-1 text-sm text-muted-foreground">
              Vous n'avez pas encore créé de note de frais ou aucune ne correspond à vos filtres.
            </p>
          </div>
        </div>
      </section>

      <!-- Notes de frais à valider -->
      <section class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-bold">Notes de frais à valider</h2>
          <Badge variant="secondary" class="px-3 py-1">
            {{ filteredExpenseToValidate.length }} en attente
          </Badge>
        </div>
        
        <div class="rounded-xl border border-secondary/50 bg-secondary/10 overflow-hidden">
          <div v-if="filteredExpenseToValidate.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-secondary/20">
              <thead class="bg-secondary/20">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-secondary-foreground/70">
                    Type
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-secondary-foreground/70">
                    Demandeur
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-secondary-foreground/70">
                    Montant (€)
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-secondary-foreground/70">
                    Statut
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-secondary-foreground/70">
                    Créé le
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-secondary-foreground/70">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary/20 bg-background/80">
                <tr v-for="sheet in filteredExpenseToValidate" :key="sheet.id" 
                    class="hover:bg-secondary/10 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <component :is="getStatusIcon(sheet.status)" 
                                class="mr-2 h-5 w-5" 
                                :class="{
                                  'text-warning': sheet.status === 'pending',
                                  'text-success': sheet.status === 'approved',
                                  'text-destructive': sheet.status === 'rejected',
                                  'text-muted-foreground': !sheet.status
                                }" />
                      <span class="text-sm font-medium text-foreground">{{ sheet.form.name }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-foreground">{{ sheet.user?.name || 'Utilisateur inconnu' }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-semibold text-foreground">{{ sheet.total.toFixed(2) }} €</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <Badge :variant="getStatusLabel(sheet).variant">
                      {{ getStatusLabel(sheet).label }}
                    </Badge>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center text-sm text-muted-foreground">
                      <Calendar class="mr-1 h-4 w-4" />
                      {{ formatDate(sheet.created_at) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <div class="flex justify-end gap-2">
                      <Link :href="'/expense-sheet/' + sheet.id + '/validate'" class="inline-flex items-center rounded-md bg-secondary px-3 py-1.5 text-sm font-medium text-secondary-foreground shadow-sm hover:bg-secondary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary">
                        <CheckCircle class="mr-1.5 h-4 w-4" />
                        Valider
                      </Link>
                      <Link :href="'/expense-sheet/' + sheet.id" class="inline-flex items-center rounded-md bg-accent px-3 py-1.5 text-sm font-medium text-accent-foreground shadow-sm hover:bg-accent/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                        <Eye class="mr-1.5 h-4 w-4" />
                        Voir
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-12 px-4 text-center">
            <CheckCircle class="h-12 w-12 text-secondary mb-4" />
            <h3 class="text-lg font-medium text-foreground">Aucune note de frais à valider</h3>
            <p class="mt-1 text-sm text-secondary-foreground/80">
              Vous n'avez pas de notes de frais en attente de validation ou aucune ne correspond à vos filtres.
            </p>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Animations pour les transitions */
.hover\:shadow-md {
  transition: box-shadow 0.3s ease, transform 0.2s ease, border-color 0.3s ease;
}

.hover\:shadow-md:hover {
  transform: translateY(-2px);
}

/* Assurer que les tableaux sont responsifs */
@media (max-width: 768px) {
  table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
  }
}
</style>