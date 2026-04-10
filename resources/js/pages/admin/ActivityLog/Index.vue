<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Logs d'activité" />

        <div class="p-3 sm:p-4">
            <!-- Header -->
            <header class="mb-4">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Logs d'activité</h2>
                <p class="text-sm text-muted-foreground mt-1">Historique de toutes les actions effectuées sur les données sensibles</p>
            </header>

            <!-- Filtres -->
            <Card class="mb-4 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Recherche -->
                    <div>
                        <label class="text-sm font-medium mb-1.5 block">Recherche</label>
                        <Input
                            v-model="filters.search"
                            placeholder="Description..."
                            class="w-full"
                        >
                            <template #leading>
                                <SearchIcon class="h-4 w-4 text-muted-foreground" />
                            </template>
                        </Input>
                    </div>

                    <!-- Type de log -->
                    <div>
                        <label class="text-sm font-medium mb-1.5 block">Type de log</label>
                        <Select v-model="filters.log_name">
                            <SelectTrigger>
                                <SelectValue placeholder="Tous les types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Tous les types</SelectItem>
                                <SelectItem v-for="logName in logNames" :key="logName" :value="logName">
                                    {{ formatLogName(logName) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Événement -->
                    <div>
                        <label class="text-sm font-medium mb-1.5 block">Événement</label>
                        <Select v-model="filters.event">
                            <SelectTrigger>
                                <SelectValue placeholder="Tous les événements" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Tous les événements</SelectItem>
                                <SelectItem v-for="event in events" :key="event" :value="event">
                                    {{ formatEvent(event) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Type de modèle -->
                    <div>
                        <label class="text-sm font-medium mb-1.5 block">Type de modèle</label>
                        <Select v-model="filters.subject_type">
                            <SelectTrigger>
                                <SelectValue placeholder="Tous les modèles" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="null">Tous les modèles</SelectItem>
                                <SelectItem v-for="type in subjectTypes" :key="type" :value="type">
                                    {{ type }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div>
                        <label class="text-sm font-medium mb-1.5 block">Date de début</label>
                        <Input
                            v-model="filters.date_from"
                            type="date"
                            class="w-full"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1.5 block">Date de fin</label>
                        <Input
                            v-model="filters.date_to"
                            type="date"
                            class="w-full"
                        />
                    </div>
                </div>

                <!-- Bouton réinitialiser -->
                <div class="mt-3 flex justify-end">
                    <Button variant="outline" size="sm" @click="resetFilters" v-if="hasActiveFilters">
                        <XIcon class="h-4 w-4 mr-2" />
                        Réinitialiser les filtres
                    </Button>
                </div>
            </Card>

            <!-- Table des logs -->
            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="text-xs sm:text-sm">Date</TableHead>
                                <TableHead class="text-xs sm:text-sm">Type</TableHead>
                                <TableHead class="text-xs sm:text-sm">Événement</TableHead>
                                <TableHead class="text-xs sm:text-sm">Description</TableHead>
                                <TableHead class="text-xs sm:text-sm">Utilisateur</TableHead>
                                <TableHead class="w-[60px] text-xs sm:text-sm text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="activity in activities.data" :key="activity.id">
                                <TableCell class="text-xs whitespace-nowrap">
                                    {{ formatDate(activity.created_at) }}
                                </TableCell>
                                <TableCell class="text-xs">
                                    <Badge variant="secondary">{{ formatLogName(activity.log_name) }}</Badge>
                                </TableCell>
                                <TableCell class="text-xs">
                                    <Badge :variant="getEventVariant(activity.event)">
                                        {{ formatEvent(activity.event) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-xs">{{ activity.description }}</TableCell>
                                <TableCell class="text-xs">
                                    {{ activity.causer?.name || 'Système' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        @click="showDetails(activity)"
                                        class="h-8 w-8"
                                        title="Voir les détails"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="activities.data.length === 0">
                                <TableCell colspan="6" class="h-20 text-center text-xs sm:text-sm">
                                    Aucun log trouvé.
                                </TableCell>
                            </TableRow>
                        </TableBody>

                        <TableFooter v-if="activities.total > 0">
                            <TableRow>
                                <TableCell colspan="6" class="text-xs sm:text-sm">
                                    Total: {{ activities.total }} log{{ activities.total > 1 ? 's' : '' }}
                                </TableCell>
                            </TableRow>
                        </TableFooter>
                    </Table>
                </div>
            </Card>

            <!-- Pagination -->
            <div class="mt-4 flex flex-col xs:flex-row items-start xs:items-center justify-between gap-2" v-if="activities.total > 0">
                <div class="text-xs sm:text-sm text-muted-foreground">
                    Affichage de {{ activities.from }} à {{ activities.to }} sur {{ activities.total }} logs
                </div>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!activities.prev_page_url"
                        @click="goToPage(activities.current_page - 1)"
                    >
                        Précédent
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!activities.next_page_url"
                        @click="goToPage(activities.current_page + 1)"
                    >
                        Suivant
                    </Button>
                </div>
            </div>

            <!-- Dialog des détails -->
            <AlertDialog v-model:open="showDetailsDialog">
                <AlertDialogContent class="max-w-3xl max-h-[80vh] overflow-y-auto">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Détails du log</AlertDialogTitle>
                        <div v-if="selectedActivity" class="text-sm text-muted-foreground mt-2">
                            <Badge variant="outline" class="text-base">
                                {{ formatSubjectType(selectedActivity.subject_type) }} #{{ selectedActivity.subject_id }}
                            </Badge>
                        </div>
                    </AlertDialogHeader>
                    <div v-if="selectedActivity" class="space-y-4 text-sm">
                        <!-- Informations générales -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="font-medium">Date :</span>
                                <p class="text-muted-foreground">{{ formatDate(selectedActivity.created_at) }}</p>
                            </div>
                            <div>
                                <span class="font-medium">Type :</span>
                                <p class="text-muted-foreground">{{ formatLogName(selectedActivity.log_name) }}</p>
                            </div>
                            <div>
                                <span class="font-medium">Événement :</span>
                                <p class="text-muted-foreground">{{ formatEvent(selectedActivity.event) }}</p>
                            </div>
                            <div>
                                <span class="font-medium">Utilisateur :</span>
                                <p class="text-muted-foreground">{{ selectedActivity.causer?.name || 'Système' }}</p>
                            </div>
                        </div>

                        <div>
                            <span class="font-medium">Description :</span>
                            <p class="text-muted-foreground">{{ selectedActivity.description }}</p>
                        </div>

                        <!-- Attributs actuels du modèle -->
                        <div v-if="selectedActivity.subject" class="rounded-lg border border-border bg-muted/50 p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <InfoIcon class="h-4 w-4 text-primary" />
                                <span class="font-medium">Attributs actuels du modèle</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div v-for="(value, key) in getRelevantAttributes(selectedActivity.subject)" :key="key" class="flex flex-col">
                                    <span class="font-medium text-muted-foreground">{{ key }} :</span>
                                    <span class="text-foreground">{{ formatAttributeValue(value) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-lg border border-dashed border-border p-3 text-center text-xs text-muted-foreground">
                            Le modèle n'existe plus ou n'a pas été chargé
                        </div>

                        <!-- Anciennes valeurs -->
                        <div v-if="selectedActivity.properties?.old && Object.keys(selectedActivity.properties.old).length > 0">
                            <span class="font-medium">Anciennes valeurs :</span>
                            <div class="mt-1 p-3 bg-muted rounded-lg">
                                <div class="grid gap-2 text-xs">
                                    <div v-for="(value, key) in selectedActivity.properties.old" :key="key" class="flex justify-between border-b border-border pb-1 last:border-0">
                                        <span class="font-medium">{{ key }} :</span>
                                        <span class="text-muted-foreground">{{ formatAttributeValue(value) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nouvelles valeurs -->
                        <div v-if="selectedActivity.properties?.attributes && Object.keys(selectedActivity.properties.attributes).length > 0">
                            <span class="font-medium">Nouvelles valeurs :</span>
                            <div class="mt-1 p-3 bg-muted rounded-lg">
                                <div class="grid gap-2 text-xs">
                                    <div v-for="(value, key) in selectedActivity.properties.attributes" :key="key" class="flex justify-between border-b border-border pb-1 last:border-0">
                                        <span class="font-medium">{{ key }} :</span>
                                        <span class="text-foreground font-medium">{{ formatAttributeValue(value) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Propriétés supplémentaires (pour les événements d'authentification) -->
                        <div v-if="selectedActivity.properties && !selectedActivity.properties.old && !selectedActivity.properties.attributes">
                            <span class="font-medium">Propriétés :</span>
                            <div class="mt-1 p-3 bg-muted rounded-lg">
                                <div class="grid gap-2 text-xs">
                                    <div v-for="(value, key) in selectedActivity.properties" :key="key" class="flex justify-between border-b border-border pb-1 last:border-0">
                                        <span class="font-medium">{{ key }} :</span>
                                        <span class="text-muted-foreground">{{ formatAttributeValue(value) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <AlertDialogFooter>
                        <Button variant="outline" @click="showDetailsDialog = false">Fermer</Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { SearchIcon, EyeIcon, XIcon, InfoIcon } from 'lucide-vue-next';

// shadcn/ui
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableHeader,
    TableBody,
    TableFooter,
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
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogFooter,
} from '@/components/ui/alert-dialog';

// Layouts
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    activities: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    logNames: { type: Array, default: () => [] },
    subjectTypes: { type: Array, default: () => [] },
    events: { type: Array, default: () => [] },
});

const filters = ref({
    search: props.filters.search || '',
    log_name: props.filters.log_name || null,
    event: props.filters.event || null,
    subject_type: props.filters.subject_type || null,
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const showDetailsDialog = ref(false);
const selectedActivity = ref(null);

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Logs d\'activité' },
];

// Debounce utility
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const hasActiveFilters = computed(() => {
    return filters.value.search ||
        filters.value.log_name ||
        filters.value.event ||
        filters.value.subject_type ||
        filters.value.date_from ||
        filters.value.date_to;
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(date);
};

const formatLogName = (logName) => {
    const names = {
        'user': 'Utilisateur',
        'expense_sheet': 'Feuille de frais',
        'expense_sheet_cost': 'Coût',
        'form': 'Formulaire',
        'department': 'Département',
        'authentication': 'Authentification',
    };
    return names[logName] || logName;
};

const formatEvent = (event) => {
    const events = {
        'created': 'Créé',
        'updated': 'Modifié',
        'deleted': 'Supprimé',
        'login': 'Connexion',
        'logout': 'Déconnexion',
        'failed_login': 'Échec connexion',
        'password_reset': 'Reset mot de passe',
    };
    return events[event] || event;
};

const getEventVariant = (event) => {
    const variants = {
        'created': 'default',
        'updated': 'secondary',
        'deleted': 'destructive',
        'login': 'default',
        'logout': 'secondary',
        'failed_login': 'destructive',
        'password_reset': 'default',
    };
    return variants[event] || 'default';
};

const formatSubjectType = (subjectType) => {
    if (!subjectType) return 'N/A';
    const parts = subjectType.split('\\');
    return parts[parts.length - 1];
};

const formatAttributeValue = (value) => {
    if (value === null) return 'null';
    if (value === '') return '(vide)';
    if (typeof value === 'boolean') return value ? 'Oui' : 'Non';
    if (typeof value === 'object') return JSON.stringify(value);
    if (typeof value === 'string' && value.length > 100) {
        return value.substring(0, 100) + '...';
    }
    return value;
};

const getRelevantAttributes = (subject) => {
    if (!subject) return {};

    // Liste des attributs à ne pas afficher
    const excludedAttributes = [
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    const filtered = {};
    for (const [key, value] of Object.entries(subject)) {
        if (!excludedAttributes.includes(key)) {
            filtered[key] = value;
        }
    }

    return filtered;
};

const showDetails = (activity) => {
    selectedActivity.value = activity;
    showDetailsDialog.value = true;
};

const resetFilters = () => {
    filters.value = {
        search: '',
        log_name: null,
        event: null,
        subject_type: null,
        date_from: '',
        date_to: '',
    };
};

const goToPage = (page) => {
    router.get('/admin/activity-logs', {
        ...filters.value,
        page,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch filters and update URL
const updateFilters = debounce(() => {
    router.get('/admin/activity-logs', filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch(filters, updateFilters, { deep: true });
</script>
