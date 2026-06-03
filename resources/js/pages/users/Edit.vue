<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <Head :title="`Modifier ${user.name}`" />

            <!-- En-tête -->
            <div class="space-y-1">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Modifier l'utilisateur</h2>
                <p class="text-sm text-muted-foreground">
                    Mettez à jour les informations, le rôle et les départements de cet utilisateur.
                </p>
            </div>

            <form @submit.prevent="updateUser" class="max-w-2xl space-y-6">
                <!-- Carte principale -->
                <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                    <!-- Bandeau d'aperçu en direct -->
                    <div class="flex items-center gap-3 sm:gap-4 border-b bg-muted/30 px-5 sm:px-8 py-4">
                        <Avatar class="h-12 w-12 shrink-0 border bg-background">
                            <AvatarFallback class="bg-muted text-foreground font-medium text-base">
                                {{ initials || '?' }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium truncate">{{ form.name || 'Utilisateur' }}</p>
                            <p class="text-sm text-muted-foreground truncate">{{ form.email || 'email@exemple.com' }}</p>
                        </div>
                        <Badge v-if="form.is_admin" variant="secondary" class="shrink-0">Administrateur</Badge>
                    </div>

                    <!-- Corps -->
                    <div class="p-5 sm:p-8 space-y-6">
                        <!-- Champ Nom -->
                        <div class="space-y-2">
                            <Label for="name">Nom</Label>
                            <div class="relative">
                                <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Nom de l'utilisateur"
                                    :class="['pl-10', form.errors.name ? 'border-destructive focus-visible:ring-destructive' : '']"
                                    required
                                />
                            </div>
                            <p v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Champ Email -->
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <div class="relative">
                                <MailIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    placeholder="email@exemple.com"
                                    :class="['pl-10', form.errors.email ? 'border-destructive focus-visible:ring-destructive' : '']"
                                    required
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-sm text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Rôle administrateur (interrupteur) -->
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="form.is_admin"
                            @click="form.is_admin = !form.is_admin"
                            class="w-full flex items-center gap-3 rounded-lg border p-4 text-left transition-colors"
                            :class="form.is_admin ? 'border-primary/50 bg-primary/5' : 'hover:bg-muted/50'"
                        >
                            <ShieldCheckIcon class="h-5 w-5 shrink-0" :class="form.is_admin ? 'text-primary' : 'text-muted-foreground'" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium">Administrateur de l'application</p>
                                <p class="text-xs text-muted-foreground">Accès complet à la gestion des utilisateurs et des paramètres.</p>
                            </div>
                            <span
                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors"
                                :class="form.is_admin ? 'bg-primary' : 'bg-muted-foreground/30'"
                            >
                                <span
                                    class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"
                                    :class="form.is_admin ? 'translate-x-5' : 'translate-x-0.5'"
                                />
                            </span>
                        </button>

                        <!-- Départements -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <Label>Départements</Label>
                                    <p class="text-xs text-muted-foreground">Rattachez l'utilisateur à un ou plusieurs services.</p>
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="openDepartmentModal">
                                    <BuildingIcon class="h-4 w-4 mr-2" />
                                    Gérer
                                </Button>
                            </div>

                            <div v-if="form.departments.length" class="flex flex-wrap gap-2">
                                <span
                                    v-for="dept in selectedDepartmentsDetails"
                                    :key="dept.id"
                                    class="inline-flex items-center gap-1.5 rounded-full border bg-muted/40 py-1 pl-3 pr-2 text-xs"
                                >
                                    {{ dept.name }}
                                    <Badge v-if="dept.is_head" variant="default" class="text-[10px]">Responsable</Badge>
                                    <button type="button" @click="removeDepartment(dept.id)" class="rounded-full p-0.5 text-muted-foreground hover:bg-muted hover:text-destructive transition-colors">
                                        <XIcon class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>
                            <button
                                v-else
                                type="button"
                                @click="openDepartmentModal"
                                class="w-full rounded-lg border border-dashed py-6 text-center text-sm text-muted-foreground hover:bg-muted/50 hover:text-foreground transition-colors"
                            >
                                <BuildingIcon class="mx-auto h-5 w-5 mb-1 opacity-60" />
                                Aucun département rattaché — cliquez pour en ajouter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex flex-col-reverse xs:flex-row items-stretch xs:items-center gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        :class="{ 'opacity-50': form.processing }"
                        class="w-full xs:w-auto"
                    >
                        <LoaderIcon v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelEdit"
                        class="w-full xs:w-auto"
                    >
                        Annuler
                    </Button>
                </div>
            </form>
        </div>

        <!-- Modale de sélection des départements -->
        <Dialog v-model:open="showDepartmentModal">
            <DialogContent class="max-w-[90vw] sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Rattacher à des départements</DialogTitle>
                    <DialogDescription>
                        Cochez les départements à rattacher. Cochez « Responsable du service » pour en faire le chef.
                    </DialogDescription>
                </DialogHeader>

                <div class="relative">
                    <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <input
                        v-model="departmentSearch"
                        type="text"
                        placeholder="Rechercher un département..."
                        class="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm"
                    />
                </div>

                <div class="max-h-[55vh] overflow-y-auto -mx-1 px-1">
                    <p v-if="departments.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Aucun département disponible.
                    </p>
                    <p v-else-if="filteredDepartments.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Aucun département ne correspond à « {{ departmentSearch }} ».
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="dept in filteredDepartments"
                            :key="dept.id"
                            class="flex items-center justify-between gap-3 py-2.5"
                        >
                            <label class="flex items-center gap-2.5 text-sm cursor-pointer min-w-0">
                                <input
                                    type="checkbox"
                                    :checked="draft[dept.id]?.selected"
                                    @change="toggleSelected(dept.id)"
                                    class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                                />
                                <span class="truncate">{{ dept.name }}</span>
                            </label>

                            <label
                                class="flex items-center gap-2 text-xs whitespace-nowrap"
                                :class="draft[dept.id]?.selected ? 'cursor-pointer text-foreground' : 'opacity-40 cursor-not-allowed'"
                            >
                                <input
                                    type="checkbox"
                                    :checked="draft[dept.id]?.is_head"
                                    :disabled="!draft[dept.id]?.selected"
                                    @change="toggleHead(dept.id)"
                                    class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                                />
                                Responsable du service
                            </label>
                        </li>
                    </ul>
                </div>

                <DialogFooter class="flex-col xs:flex-row gap-2">
                    <Button type="button" variant="outline" @click="showDepartmentModal = false" class="w-full xs:w-auto">
                        Annuler
                    </Button>
                    <Button type="button" @click="applyDepartments" class="w-full xs:w-auto">
                        Valider
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { MailIcon, BuildingIcon, XIcon, SearchIcon, UserIcon, ShieldCheckIcon, LoaderIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const user = page.props.user;
const departments = computed(() => page.props.departments ?? []);

// Initialisation du formulaire avec les données de l'utilisateur
const form = useForm({
    name: user.name,
    email: user.email,
    is_admin: !!user.is_admin,
    departments: (user.departments ?? []).map((d) => ({
        id: d.id,
        is_head: !!d.pivot?.is_head,
    })),
});

const initials = computed(() =>
    (form.name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join('')
);

// === Modale départements ===
const showDepartmentModal = ref(false);
const draft = ref({}); // { [id]: { selected, is_head } }
const departmentSearch = ref('');

const filteredDepartments = computed(() => {
    const term = departmentSearch.value.trim().toLowerCase();
    if (!term) {
        return departments.value;
    }
    return departments.value.filter((dept) => dept.name.toLowerCase().includes(term));
});

const selectedDepartmentsDetails = computed(() =>
    form.departments.map((d) => ({
        id: d.id,
        name: departments.value.find((dep) => dep.id === d.id)?.name ?? `#${d.id}`,
        is_head: d.is_head,
    }))
);

const openDepartmentModal = () => {
    const state = {};
    for (const dept of departments.value) {
        const existing = form.departments.find((d) => d.id === dept.id);
        state[dept.id] = {
            selected: !!existing,
            is_head: existing?.is_head ?? false,
        };
    }
    draft.value = state;
    departmentSearch.value = '';
    showDepartmentModal.value = true;
};

const toggleSelected = (id) => {
    const entry = draft.value[id];
    entry.selected = !entry.selected;
    if (!entry.selected) {
        entry.is_head = false;
    }
};

const toggleHead = (id) => {
    const entry = draft.value[id];
    if (!entry.selected) {
        return;
    }
    entry.is_head = !entry.is_head;
};

const applyDepartments = () => {
    form.departments = Object.entries(draft.value)
        .filter(([, v]) => v.selected)
        .map(([id, v]) => ({ id: Number(id), is_head: v.is_head }));
    showDepartmentModal.value = false;
};

const removeDepartment = (id) => {
    form.departments = form.departments.filter((d) => d.id !== id);
};

// Définition des breadcrumbs pour la navigation
const breadcrumbs = [
    {
        title: 'Utilisateurs',
        href: '/users',
    },
    {
        title: `Modifier ${user.name}`,
        href: `/users/${user.id}/edit`,
    },
];

// Fonction pour mettre à jour l'utilisateur
const updateUser = () => {
    form.put(`/users/${user.id}`);
};

// Fonction pour annuler l'édition et retourner à la liste
const cancelEdit = () => {
    router.visit('/users');
};
</script>
