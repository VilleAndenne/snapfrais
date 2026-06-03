<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Modifier le département" />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- En-tête -->
            <div class="space-y-1">
                <h2 class="text-xl sm:text-2xl font-semibold tracking-tight">Modifier le département</h2>
                <p class="text-sm text-muted-foreground">
                    Mettez à jour le service, sa place dans la hiérarchie et ses membres.
                </p>
            </div>

            <form @submit.prevent="submitForm" class="max-w-2xl space-y-6">
                <!-- Carte principale -->
                <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                    <!-- Bandeau d'aperçu en direct -->
                    <div class="flex items-center gap-3 sm:gap-4 border-b bg-muted/30 px-5 sm:px-8 py-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full border bg-background shrink-0">
                            <Building2Icon class="h-6 w-6 text-muted-foreground" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium truncate">{{ form.name || 'Département' }}</p>
                            <p class="text-sm text-muted-foreground truncate">
                                {{ parentName ? `Sous « ${parentName} »` : 'Département racine' }}
                            </p>
                        </div>
                        <Badge v-if="form.users.length" variant="secondary" class="shrink-0">
                            {{ form.users.length }} membre{{ form.users.length > 1 ? 's' : '' }}
                        </Badge>
                    </div>

                    <!-- Corps -->
                    <div class="p-5 sm:p-8 space-y-6">
                        <!-- Nom -->
                        <div class="space-y-2">
                            <Label for="name">Nom du département</Label>
                            <div class="relative">
                                <Building2Icon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Nom du département"
                                    :class="['pl-10', form.errors.name ? 'border-destructive focus-visible:ring-destructive' : '']"
                                />
                            </div>
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <!-- Service supérieur -->
                        <div class="space-y-2">
                            <Label for="parent">Service supérieur</Label>
                            <Select v-model="form.parent_id">
                                <SelectTrigger id="parent" class="w-full" :class="{ 'border-destructive': form.errors.parent_id }">
                                    <SelectValue :placeholder="'Aucun (département racine)'" :defaultValue="form.parent_id" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Aucun (département racine)</SelectItem>
                                    <SelectItem v-for="dept in availableParentDepartments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.parent_id" class="text-sm text-destructive">{{ form.errors.parent_id }}</p>
                            <p class="text-xs text-muted-foreground">
                                Laissez vide si ce département est au plus haut niveau de la hiérarchie.
                            </p>
                        </div>

                        <!-- Membres -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <Label>Membres</Label>
                                    <p class="text-xs text-muted-foreground">Assignez des utilisateurs et désignez les responsables.</p>
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="openUserModal">
                                    <UsersIcon class="h-4 w-4 mr-2" />
                                    Gérer
                                </Button>
                            </div>

                            <div v-if="form.users.length" class="flex flex-wrap gap-2">
                                <span
                                    v-for="member in selectedUsersDetails"
                                    :key="member.id"
                                    class="inline-flex items-center gap-1.5 rounded-full border bg-muted/40 py-1 pl-3 pr-2 text-xs"
                                >
                                    {{ member.name }}
                                    <Badge v-if="member.is_head" variant="default" class="text-[10px]">Responsable</Badge>
                                    <button type="button" @click="removeUser(member.id)" class="rounded-full p-0.5 text-muted-foreground hover:bg-muted hover:text-destructive transition-colors">
                                        <XIcon class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>
                            <button
                                v-else
                                type="button"
                                @click="openUserModal"
                                class="w-full rounded-lg border border-dashed py-6 text-center text-sm text-muted-foreground hover:bg-muted/50 hover:text-foreground transition-colors"
                            >
                                <UsersIcon class="mx-auto h-5 w-5 mb-1 opacity-60" />
                                Aucun membre assigné — cliquez pour en ajouter
                            </button>
                            <p v-if="form.errors.users" class="text-sm text-destructive">{{ form.errors.users }}</p>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex flex-col-reverse xs:flex-row items-stretch xs:items-center gap-3">
                    <Button type="submit" :disabled="form.processing" class="w-full xs:w-auto">
                        <LoaderIcon v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                    </Button>
                    <Button type="button" variant="outline" @click="goBack" class="w-full xs:w-auto">
                        Annuler
                    </Button>
                </div>
            </form>
        </div>

        <!-- Modale de sélection des membres -->
        <Dialog v-model:open="showUserModal">
            <DialogContent class="max-w-[90vw] sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Gérer les membres</DialogTitle>
                    <DialogDescription>
                        Cochez les utilisateurs à rattacher. Cochez « Responsable du service » pour en faire un chef.
                    </DialogDescription>
                </DialogHeader>

                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <SearchIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <input
                            v-model="userSearch"
                            type="text"
                            placeholder="Rechercher un utilisateur..."
                            class="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm"
                        />
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="toggleSelectAll" class="shrink-0">
                        {{ allFilteredSelected ? 'Tout désélectionner' : 'Tout sélectionner' }}
                    </Button>
                </div>

                <div class="max-h-[55vh] overflow-y-auto -mx-1 px-1">
                    <p v-if="props.users.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Aucun utilisateur disponible.
                    </p>
                    <p v-else-if="filteredModalUsers.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Aucun utilisateur ne correspond à « {{ userSearch }} ».
                    </p>
                    <ul v-else class="divide-y">
                        <li
                            v-for="user in filteredModalUsers"
                            :key="user.id"
                            class="flex items-center justify-between gap-3 py-2.5"
                        >
                            <label class="flex items-center gap-2.5 text-sm cursor-pointer min-w-0">
                                <input
                                    type="checkbox"
                                    :checked="draft[user.id]?.selected"
                                    @change="toggleSelected(user.id)"
                                    class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                                />
                                <span class="min-w-0">
                                    <span class="block truncate">{{ user.name }}</span>
                                    <span class="block text-xs text-muted-foreground truncate">{{ user.email }}</span>
                                </span>
                            </label>

                            <label
                                class="flex items-center gap-2 text-xs whitespace-nowrap"
                                :class="draft[user.id]?.selected ? 'cursor-pointer text-foreground' : 'opacity-40 cursor-not-allowed'"
                            >
                                <input
                                    type="checkbox"
                                    :checked="draft[user.id]?.is_head"
                                    :disabled="!draft[user.id]?.selected"
                                    @change="toggleHead(user.id)"
                                    class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
                                />
                                Responsable du service
                            </label>
                        </li>
                    </ul>
                </div>

                <DialogFooter class="flex-col xs:flex-row gap-2">
                    <Button type="button" variant="outline" @click="showUserModal = false" class="w-full xs:w-auto">
                        Annuler
                    </Button>
                    <Button type="button" @click="applyUsers" class="w-full xs:w-auto">
                        Valider
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { SearchIcon, LoaderIcon, Building2Icon, UsersIcon, XIcon } from 'lucide-vue-next';

// shadcn/ui
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import Select from '@/components/ui/select/Select.vue';
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/components/ui/select/SelectValue.vue';
import SelectContent from '@/components/ui/select/SelectContent.vue';
import SelectItem from '@/components/ui/select/SelectItem.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    { title: 'Départements', href: route('departments.index') },
    { title: 'Modifier le département' },
];

const props = defineProps({
    department: {
        type: Object,
        required: true,
    },
    departments: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
});

// Transformer les utilisateurs du département en format attendu par le contrôleur
const transformDepartmentUsers = () => {
    if (!props.department.users) return [];

    return props.department.users.map((user) => ({
        id: user.id,
        is_head: user.pivot?.is_head === 1 || user.pivot?.is_head === true || user.pivot?.is_head === '1',
    }));
};

// Formulaire Inertia
const form = useForm({
    name: props.department.name,
    parent_id: props.department.parent_id,
    users: transformDepartmentUsers(),
});

const parentName = computed(() =>
    props.departments.find((d) => d.id === form.parent_id)?.name ?? null
);

// Départements disponibles comme parents (exclure le département actuel et ses enfants)
const availableParentDepartments = computed(() => {
    const findAllChildren = (departmentId) => {
        const children = props.departments.filter((d) => d.parent_id === departmentId).map((d) => d.id);
        const allChildren = [...children];
        children.forEach((childId) => {
            allChildren.push(...findAllChildren(childId));
        });
        return allChildren;
    };

    const childrenIds = findAllChildren(props.department.id);
    return props.departments.filter((d) => d.id !== props.department.id && !childrenIds.includes(d.id));
});

const selectedUsersDetails = computed(() =>
    form.users.map((u) => ({
        id: u.id,
        name: props.users.find((user) => user.id === u.id)?.name ?? `#${u.id}`,
        is_head: u.is_head,
    }))
);

const removeUser = (id) => {
    form.users = form.users.filter((u) => u.id !== id);
};

// === Modale membres ===
const showUserModal = ref(false);
const draft = ref({}); // { [id]: { selected, is_head } }
const userSearch = ref('');

const filteredModalUsers = computed(() => {
    const term = userSearch.value.trim().toLowerCase();
    if (!term) {
        return props.users;
    }
    return props.users.filter(
        (user) =>
            user.name.toLowerCase().includes(term) ||
            user.email?.toLowerCase().includes(term)
    );
});

const allFilteredSelected = computed(() =>
    filteredModalUsers.value.length > 0 &&
    filteredModalUsers.value.every((u) => draft.value[u.id]?.selected)
);

const openUserModal = () => {
    const state = {};
    for (const user of props.users) {
        const existing = form.users.find((u) => u.id === user.id);
        state[user.id] = {
            selected: !!existing,
            is_head: existing?.is_head ?? false,
        };
    }
    draft.value = state;
    userSearch.value = '';
    showUserModal.value = true;
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

const toggleSelectAll = () => {
    const target = !allFilteredSelected.value;
    for (const user of filteredModalUsers.value) {
        draft.value[user.id].selected = target;
        if (!target) {
            draft.value[user.id].is_head = false;
        }
    }
};

const applyUsers = () => {
    form.users = Object.entries(draft.value)
        .filter(([, v]) => v.selected)
        .map(([id, v]) => ({ id: Number(id), is_head: v.is_head }));
    showUserModal.value = false;
};

// Soumettre le formulaire
const submitForm = () => {
    form.put(route('departments.update', props.department.id));
};

// Retourner à la liste
const goBack = () => {
    window.history.back();
};
</script>
