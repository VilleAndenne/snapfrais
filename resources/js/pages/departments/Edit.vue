<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Modifier le département" />
        <div class="p-6 space-y-6">
            <header class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold tracking-tight">Modifier le département</h2>
                <Button variant="outline" @click="goBack">
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour
                </Button>
            </header>

            <Card>
                <CardHeader>
                    <CardTitle>Informations du département</CardTitle>
                    <CardDescription>
                        Modifiez les détails du département et gérez les utilisateurs assignés.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitForm" class="space-y-6">
                        <!-- Nom du département -->
                        <div class="space-y-2">
                            <Label for="name">Nom du département</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Nom du département"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

<!-- Département parent -->
<div class="space-y-2">
    <Label for="parent">Service supérieur</Label>

    <Select v-model="form.parent_id">
        <SelectTrigger
            id="parent"
            class="w-full"
            :class="{ 'border-destructive': form.errors.parent_id }"
        >
            <SelectValue
                :placeholder="'Aucun (département racine)'"
                :defaultValue="form.parent_id"
            />
        </SelectTrigger>

        <SelectContent>
            <SelectItem :value="'null'">Aucun (département racine)</SelectItem>
            <SelectItem
                v-for="dept in availableParentDepartments"
                :key="dept.id"
                :value="String(dept.id)"
            >
                {{ dept.name }}
            </SelectItem>
        </SelectContent>
    </Select>

    <p v-if="form.errors.parent_id" class="text-sm text-destructive">
        {{ form.errors.parent_id }}
    </p>
    <p class="text-sm text-muted-foreground">
        Laissez vide si ce département est au plus haut niveau de la hiérarchie.
    </p>
</div>


                        <!-- Liste des utilisateurs -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <Label>Utilisateurs du département</Label>
                                <div class="flex items-center gap-2">
                                    <Input
                                        v-model="userSearch"
                                        placeholder="Rechercher un utilisateur..."
                                        class="w-[200px]"
                                    >
                                        <template #leading>
                                            <SearchIcon class="h-4 w-4 text-muted-foreground" />
                                        </template>
                                    </Input>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="toggleSelectAll"
                                    >
                                        {{ allSelected ? 'Désélectionner tout' : 'Sélectionner tout' }}
                                    </Button>
                                </div>
                            </div>

                            <Card>
                                <div class="max-h-[300px] overflow-y-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead class="w-[50px]"></TableHead>
                                                <TableHead>Nom</TableHead>
                                                <TableHead>Email</TableHead>
                                                <TableHead>Rôle</TableHead>
                                                <TableHead class="w-[120px]">Responsable</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="user in filteredUsers" :key="user.id">
                                                <TableCell>
                                                    <Checkbox
                                                        :id="`user-${user.id}`"
                                                        :checked="isUserSelected(user.id)"
                                                        @update:checked="toggleUser(user.id)"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Label :for="`user-${user.id}`"
                                                           class="flex items-center gap-2 cursor-pointer">
                                                        <Avatar class="h-8 w-8">
                                                            <AvatarImage :src="user.avatar" :alt="user.name" />
                                                            <AvatarFallback>{{ getUserInitials(user.name) }}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        {{ user.name }}
                                                    </Label>
                                                </TableCell>
                                                <TableCell>{{ user.email }}</TableCell>
                                                <TableCell>{{ user.role }}</TableCell>
                                                <TableCell>
                                                    <div class="flex justify-center">
                                                        <Checkbox
                                                            :id="`head-${user.id}`"
                                                            :disabled="!isUserSelected(user.id)"
                                                            :checked="isUserHead(user.id)"
                                                            @update:checked="toggleUserHead(user.id, $event)"
                                                        />
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-if="filteredUsers.length === 0">
                                                <TableCell colspan="5" class="h-24 text-center">
                                                    Aucun utilisateur trouvé.
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </Card>
                            <p v-if="form.errors.users" class="text-sm text-destructive">{{ form.errors.users }}</p>
                            <p class="text-sm text-muted-foreground">
                                Cochez la case "Responsable" pour désigner un utilisateur comme responsable du département.
                            </p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="goBack">Annuler</Button>
                            <Button type="submit" :disabled="form.processing">
                                <LoaderIcon v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Enregistrer
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    SearchIcon,
    LoaderIcon
} from 'lucide-vue-next';

// Import explicite des composants shadcn/ui
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent
} from '@/components/ui/card';
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell
} from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Avatar,
    AvatarImage,
    AvatarFallback
} from '@/components/ui/avatar';
import AppLayout from '@/layouts/AppLayout.vue';
import Select from '@/components/ui/select/Select.vue';
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/components/ui/select/SelectValue.vue';
import SelectContent from '@/components/ui/select/SelectContent.vue';
import SelectItem from '@/components/ui/select/SelectItem.vue';

const breadcrumbs = [
    { title: 'Départements', href: route('departments.index') },
    { title: 'Modifier le département' }
];

// Props
const props = defineProps({
    department: {
        type: Object,
        required: true
    },
    departments: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    }
});

// État
const userSearch = ref('');

// Transformer les utilisateurs du département en format attendu par le contrôleur
const transformDepartmentUsers = () => {
    if (!props.department.users) return [];

    return props.department.users.map(user => {
        // Vérifier toutes les formes possibles de is_head
        const isHead = user.pivot?.is_head === 1 ||
            user.pivot?.is_head === true ||
            user.pivot?.is_head === "1";

        return {
            id: user.id,
            is_head: isHead
        };
    });
};

// Formulaire Inertia
const form = useForm({
    name: props.department.name,
    parent_id: props.department.parent_id,
    users: transformDepartmentUsers()
});

// Départements disponibles comme parents (exclure le département actuel et ses enfants)
const availableParentDepartments = computed(() => {
    // Fonction récursive pour trouver tous les enfants d'un département
    const findAllChildren = (departmentId) => {
        const children = props.departments.filter(d => d.parent_id === departmentId).map(d => d.id);
        const allChildren = [...children];

        children.forEach(childId => {
            allChildren.push(...findAllChildren(childId));
        });

        return allChildren;
    };

    const childrenIds = findAllChildren(props.department.id);
    return props.departments.filter(d => d.id !== props.department.id && !childrenIds.includes(d.id));
});

// Filtrer les utilisateurs en fonction de la recherche
const filteredUsers = computed(() => {
    if (!userSearch.value) return props.users;

    const query = userSearch.value.toLowerCase();
    return props.users.filter(user =>
        user.name.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query) ||
        user.role?.toLowerCase().includes(query)
    );
});

// Vérifier si un utilisateur est sélectionné
const isUserSelected = (userId) => {
    return form.users.some(user => user.id === userId);
};

// Vérifier si un utilisateur est responsable
const isUserHead = (userId) => {
    const user = form.users.find(user => user.id === userId);
    return user ? user.is_head : false;
};

// Vérifier si tous les utilisateurs sont sélectionnés
const allSelected = computed(() => {
    return filteredUsers.value.length > 0 &&
        filteredUsers.value.every(user => isUserSelected(user.id));
});

// Basculer la sélection d'un utilisateur
const toggleUser = (userId) => {
    const index = form.users.findIndex(user => user.id === userId);
    if (index === -1) {
        // Ajouter l'utilisateur
        form.users.push({
            id: userId,
            is_head: false
        });
    } else {
        // Supprimer l'utilisateur
        form.users.splice(index, 1);
    }
};

// Basculer le statut de responsable d'un utilisateur
const toggleUserHead = (userId, isHead) => {
    const index = form.users.findIndex(user => user.id === userId);
    if (index !== -1) {
        // Si on active un responsable, désactiver les autres
        if (isHead) {
            form.users.forEach(user => {
                user.is_head = user.id === userId;
            });
        } else {
            form.users[index].is_head = false;
        }
    }
};

// Sélectionner/désélectionner tous les utilisateurs
const toggleSelectAll = () => {
    if (allSelected.value) {
        // Désélectionner tous les utilisateurs filtrés
        form.users = form.users.filter(
            user => !filteredUsers.value.some(filteredUser => filteredUser.id === user.id)
        );
    } else {
        // Sélectionner tous les utilisateurs filtrés
        const currentUserIds = form.users.map(user => user.id);

        // Ajouter uniquement les utilisateurs qui ne sont pas déjà sélectionnés
        filteredUsers.value.forEach(user => {
            if (!currentUserIds.includes(user.id)) {
                form.users.push({
                    id: user.id,
                    is_head: false
                });
            }
        });
    }
};

// Obtenir les initiales d'un utilisateur
const getUserInitials = (name) => {
    return name
        .split(' ')
        .map(part => part[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

// Soumettre le formulaire
const submitForm = () => {
    // Assurez-vous que les données sont correctement formatées
    const formattedUsers = form.users.map(user => ({
        id: user.id,
        is_head: user.is_head ? 1 : 0  // Convertir en 1/0 pour le backend
    }));

    form.users = formattedUsers;
    form.put(route('departments.update', props.department.id));
};
// Retourner à la liste
const goBack = () => {
    window.history.back();
};
</script>
