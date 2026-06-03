<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="user.name" />

        <div class="p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">
            <!-- En-tête -->
            <div class="flex items-start justify-between gap-3 sm:gap-4">
                <div class="flex items-start gap-3 sm:gap-4 min-w-0">
                    <Avatar class="h-12 w-12 sm:h-14 sm:w-14 shrink-0 border bg-background">
                        <AvatarFallback class="bg-muted text-foreground font-medium">
                            {{ initials }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 space-y-1">
                        <h1 class="text-xl sm:text-2xl font-semibold text-foreground truncate">
                            {{ user.name }}
                        </h1>
                        <a :href="`mailto:${user.email}`" class="inline-flex items-center gap-1.5 text-xs sm:text-sm text-muted-foreground hover:text-foreground transition-colors">
                            <MailIcon class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ user.email }}</span>
                        </a>
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            <Badge v-if="user.super_admin" variant="default">Super admin</Badge>
                            <Badge v-if="user.is_admin && !user.super_admin" variant="secondary">Administrateur</Badge>
                            <Badge v-if="user.is_head" variant="outline">Responsable de département</Badge>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <DropdownMenu v-if="canUpdate || canDelete || canImpersonate">
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="icon" class="shrink-0">
                            <MoreVerticalIcon class="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuItem v-if="canUpdate" @click="editUser">
                            <PencilIcon class="mr-2 h-4 w-4" />
                            Modifier l'utilisateur
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="canUpdate" @click="confirmSendPasswordReset">
                            <MailIcon class="mr-2 h-4 w-4" />
                            Envoyer un lien de réinitialisation
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="canImpersonate" @click="impersonateUser">
                            <UserIcon class="mr-2 h-4 w-4" />
                            Impersonner
                        </DropdownMenuItem>
                        <template v-if="canDelete && !user.deleted_at">
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="confirmDelete" class="text-destructive focus:text-destructive">
                                <TrashIcon class="mr-2 h-4 w-4" />
                                Supprimer
                            </DropdownMenuItem>
                        </template>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <!-- Bannière compte supprimé -->
            <div
                v-if="user.deleted_at"
                class="flex items-start gap-3 rounded-lg border-l-4 border-destructive bg-destructive/5 px-4 py-3"
            >
                <TrashIcon class="h-4 w-4 text-destructive mt-0.5 shrink-0" />
                <div class="space-y-0.5">
                    <h3 class="text-sm font-medium text-destructive">Compte supprimé</h3>
                    <p class="text-xs text-destructive/90">
                        Supprimé le {{ formatDate(user.deleted_at) }}. L'utilisateur ne peut plus se connecter.
                    </p>
                </div>
            </div>

            <!-- Départements -->
            <section class="space-y-3">
                <h2 class="text-base sm:text-lg font-semibold">Départements</h2>
                <div v-if="user.departments?.length" class="flex flex-wrap gap-2">
                    <span
                        v-for="dept in user.departments"
                        :key="dept.id"
                        class="inline-flex items-center gap-1.5 rounded-full border bg-muted/40 px-3 py-1 text-xs"
                    >
                        {{ dept.name }}
                        <Badge v-if="dept.pivot?.is_head" variant="default" class="text-[10px]">Responsable</Badge>
                        <Badge v-else variant="outline" class="text-[10px]">Membre</Badge>
                    </span>
                </div>
                <div v-else class="rounded-lg border border-dashed py-6 px-4 text-center text-sm text-muted-foreground">
                    Aucun département rattaché.
                </div>
            </section>

            <!-- NDF récentes -->
            <section class="space-y-3">
                <div>
                    <h2 class="text-base sm:text-lg font-semibold">Notes de frais récentes</h2>
                    <p class="text-xs text-muted-foreground">Les {{ expenseSheets.length }} dernières soumissions</p>
                </div>

                <!-- Mobile : cartes -->
                <div v-if="expenseSheets.length > 0" class="md:hidden space-y-3">
                    <Link
                        v-for="sheet in expenseSheets"
                        :key="sheet.id"
                        :href="route('expense-sheet.show', sheet.id)"
                        class="block"
                    >
                        <div class="border rounded-lg p-4 bg-card hover:bg-muted/50 transition-colors space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2 flex-1 min-w-0">
                                    <component :is="statusIcon(sheet)" class="h-5 w-5 flex-shrink-0 mt-0.5" />
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-medium text-sm truncate">{{ sheet.form?.name ?? 'Note de frais' }}</h3>
                                        <p class="text-xs text-muted-foreground">{{ sheet.department?.name ?? '—' }}</p>
                                    </div>
                                </div>
                                <Badge :variant="statusVariant(sheet)" class="text-xs flex-shrink-0">
                                    {{ statusLabel(sheet) }}
                                </Badge>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Montant</span>
                                    <span class="font-semibold text-sm tabular-nums">{{ formatAmount(sheet.total) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Créé le</span>
                                    <div class="flex items-center gap-1">
                                        <CalendarIcon class="h-3 w-3" />
                                        <span class="tabular-nums">{{ formatDate(sheet.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Desktop : tableau -->
                <div v-if="expenseSheets.length > 0" class="hidden md:block overflow-x-auto border rounded-xl">
                    <table class="min-w-full divide-y">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Type</th>
                                <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Département</th>
                                <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Montant</th>
                                <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Statut</th>
                                <th class="px-4 lg:px-6 py-3 text-left text-xs uppercase">Créé le</th>
                                <th class="px-4 lg:px-6 py-3 text-right text-xs uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="sheet in expenseSheets"
                                :key="sheet.id"
                                class="hover:bg-muted/50 transition-colors"
                            >
                                <td class="px-4 lg:px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <component :is="statusIcon(sheet)" class="h-5 w-5 flex-shrink-0" />
                                        <span class="text-sm font-medium">{{ sheet.form?.name ?? 'Note de frais' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 lg:px-6 py-4 text-sm">{{ sheet.department?.name ?? '—' }}</td>
                                <td class="px-4 lg:px-6 py-4 text-sm font-semibold tabular-nums">{{ formatAmount(sheet.total) }}</td>
                                <td class="px-4 lg:px-6 py-4">
                                    <Badge :variant="statusVariant(sheet)" class="text-xs">
                                        {{ statusLabel(sheet) }}
                                    </Badge>
                                </td>
                                <td class="px-4 lg:px-6 py-4 text-sm">
                                    <div class="flex items-center gap-1 text-muted-foreground">
                                        <CalendarIcon class="h-4 w-4" />
                                        <span class="tabular-nums">{{ formatDate(sheet.created_at) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 lg:px-6 py-4 text-right">
                                    <Link :href="route('expense-sheet.show', sheet.id)">
                                        <button class="px-3 py-1.5 border rounded-md text-sm hover:bg-muted transition-colors">
                                            Voir
                                        </button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty -->
                <div v-if="expenseSheets.length === 0" class="rounded-lg border border-dashed border-border py-10 px-4 text-center">
                    <FileTextIcon class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                    <p class="text-sm font-medium">Aucune note de frais</p>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Cet utilisateur n'a encore soumis aucune note de frais.
                    </p>
                </div>
            </section>

            <!-- Modal confirmation envoi lien -->
            <AlertDialog v-model:open="showResetDialog">
                <AlertDialogContent class="max-w-[90vw] sm:max-w-md">
                    <AlertDialogHeader>
                        <AlertDialogTitle class="text-base sm:text-lg">Envoyer un lien de réinitialisation ?</AlertDialogTitle>
                        <AlertDialogDescription class="text-xs sm:text-sm">
                            Un e-mail sera envoyé à <span class="font-medium text-foreground">{{ user.email }}</span> lui permettant de définir un nouveau mot de passe. Le message précisera qu'un administrateur est à l'origine de la demande.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="flex-col xs:flex-row gap-2">
                        <Button variant="outline" @click="showResetDialog = false" class="w-full xs:w-auto">Annuler</Button>
                        <Button @click="sendPasswordReset" :disabled="isSendingReset" class="w-full xs:w-auto">
                            <LoaderIcon v-if="isSendingReset" class="mr-2 h-4 w-4 animate-spin" />
                            Envoyer
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <!-- Modal suppression -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent class="max-w-[90vw] sm:max-w-md">
                    <AlertDialogHeader>
                        <AlertDialogTitle class="text-base sm:text-lg">Supprimer cet utilisateur ?</AlertDialogTitle>
                        <AlertDialogDescription class="text-xs sm:text-sm">
                            Cette action ne peut pas être annulée. L'utilisateur "{{ user.name }}" sera définitivement supprimé.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter class="flex-col xs:flex-row gap-2">
                        <Button variant="outline" @click="showDeleteDialog = false" class="w-full xs:w-auto">Annuler</Button>
                        <Button variant="destructive" @click="deleteUser" :disabled="isDeleting" class="w-full xs:w-auto">
                            <LoaderIcon v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
                            Supprimer
                        </Button>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, usePage, router, Link } from '@inertiajs/vue3'
import {
    AlertTriangleIcon,
    CalendarIcon,
    CheckCircleIcon,
    ClockIcon,
    FileTextIcon,
    LoaderIcon,
    MailIcon,
    MoreVerticalIcon,
    PencilIcon,
    TrashIcon,
    UserIcon,
} from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
} from '@/components/ui/alert-dialog'

const page = usePage()
const user = computed(() => page.props.user)
const expenseSheets = computed(() => page.props.expenseSheets ?? [])
const canUpdate = computed(() => page.props.canUpdate)
const canDelete = computed(() => page.props.canDelete)
const canImpersonate = computed(() => page.props.canImpersonate)

const showDeleteDialog = ref(false)
const isDeleting = ref(false)
const showResetDialog = ref(false)
const isSendingReset = ref(false)

const breadcrumbs = computed(() => [
    { title: 'Utilisateurs', href: route('users.index') },
    { title: user.value.name, href: route('users.show', user.value.id) },
])

const initials = computed(() => {
    return (user.value.name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join('')
})

const formatDate = (value) => {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    })
}

const formatAmount = (value) => {
    const n = Number(value ?? 0)
    return n.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })
}

const statusLabel = (sheet) => {
    if (sheet.is_draft) return 'Brouillon'
    if (sheet.approved === true) return 'Validée'
    if (sheet.approved === false) return 'Refusée'
    return 'En attente'
}

const statusVariant = (sheet) => {
    if (sheet.is_draft) return 'outline'
    if (sheet.approved === true) return 'default'
    if (sheet.approved === false) return 'destructive'
    return 'secondary'
}

const statusIcon = (sheet) => {
    if (sheet.is_draft) return FileTextIcon
    if (sheet.approved === true) return CheckCircleIcon
    if (sheet.approved === false) return AlertTriangleIcon
    return ClockIcon
}

const editUser = () => router.visit(route('users.edit', user.value.id))
const impersonateUser = () => router.post(route('impersonate.start', user.value.id))

const confirmSendPasswordReset = () => {
    showResetDialog.value = true
}

const sendPasswordReset = () => {
    isSendingReset.value = true
    router.post(route('users.send-password-reset', user.value.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSendingReset.value = false
            showResetDialog.value = false
        },
    })
}

const confirmDelete = () => {
    showDeleteDialog.value = true
}

const deleteUser = () => {
    isDeleting.value = true
    router.delete(route('users.destroy', user.value.id), {
        onFinish: () => {
            isDeleting.value = false
            showDeleteDialog.value = false
        },
    })
}
</script>
