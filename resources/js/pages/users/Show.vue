<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="user.name" />

        <div class="container mx-auto space-y-4 sm:space-y-6 p-3 sm:p-4">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3 sm:gap-4 min-w-0">
                    <Avatar class="h-12 w-12 sm:h-14 sm:w-14 shrink-0">
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
                            <Badge v-if="user.is_head" variant="outline">Chef de département</Badge>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bannière compte supprimé -->
            <div
                v-if="user.deleted_at"
                class="flex items-start gap-3 rounded-lg border-l-4 border-destructive bg-gradient-to-r from-destructive/10 to-destructive/5 px-4 py-3 shadow-sm"
            >
                <div class="flex-shrink-0 rounded-full bg-destructive/10 p-1.5">
                    <TrashIcon class="h-4 w-4 text-destructive" />
                </div>
                <div class="flex-1 space-y-0.5">
                    <h3 class="text-sm font-medium text-destructive">Compte supprimé</h3>
                    <p class="text-xs text-destructive/90">
                        Supprimé le {{ formatDate(user.deleted_at) }}. L'utilisateur ne peut plus se connecter.
                    </p>
                </div>
            </div>

            <!-- Résumé inline -->
            <div class="rounded-lg border border-border bg-card">
                <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-border">
                    <div class="p-4 space-y-1">
                        <p class="text-[11px] uppercase tracking-wider text-muted-foreground font-medium">Montant validé</p>
                        <p class="text-lg sm:text-xl font-semibold tabular-nums">{{ formatAmount(stats.approved_total) }}</p>
                    </div>
                    <div class="p-4 space-y-1">
                        <p class="text-[11px] uppercase tracking-wider text-muted-foreground font-medium">Validées</p>
                        <p class="text-lg sm:text-xl font-semibold tabular-nums">
                            {{ stats.approved }}<span class="text-sm text-muted-foreground font-normal"> / {{ stats.total }}</span>
                        </p>
                    </div>
                    <div class="p-4 space-y-1">
                        <p class="text-[11px] uppercase tracking-wider text-muted-foreground font-medium">En attente</p>
                        <p class="text-lg sm:text-xl font-semibold tabular-nums">{{ stats.pending }}</p>
                    </div>
                    <div class="p-4 space-y-1">
                        <p class="text-[11px] uppercase tracking-wider text-muted-foreground font-medium">Refusées</p>
                        <p class="text-lg sm:text-xl font-semibold tabular-nums">{{ stats.rejected }}</p>
                    </div>
                </div>
            </div>

            <!-- Deux colonnes -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Colonne gauche : NDF (table façon expense-sheet index) -->
                <div class="lg:col-span-2 space-y-3">
                    <div class="flex items-end justify-between">
                        <div>
                            <h2 class="text-base sm:text-lg font-semibold">Notes de frais récentes</h2>
                            <p class="text-xs text-muted-foreground">Les {{ expenseSheets.length }} dernières soumissions</p>
                        </div>
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
                </div>

                <!-- Colonne droite : panneau latéral -->
                <aside class="space-y-4">
                    <!-- Actions rapides -->
                    <section class="rounded-lg border border-border bg-card">
                        <header class="px-4 py-2.5 border-b border-border">
                            <h3 class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Actions rapides</h3>
                        </header>
                        <div class="p-2">
                            <button
                                v-if="canUpdate"
                                @click="confirmSendPasswordReset"
                                :disabled="isSendingReset"
                                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-md text-sm text-left transition-colors hover:bg-muted disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <LoaderIcon v-if="isSendingReset" class="h-4 w-4 text-muted-foreground shrink-0 animate-spin" />
                                <MailIcon v-else class="h-4 w-4 text-muted-foreground shrink-0" />
                                <span class="flex-1">Envoyer un lien de réinitialisation</span>
                                <ChevronRightIcon class="h-3.5 w-3.5 text-muted-foreground/60" />
                            </button>
                            <button
                                v-if="canUpdate"
                                @click="editUser"
                                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-md text-sm text-left transition-colors hover:bg-muted"
                            >
                                <PencilIcon class="h-4 w-4 text-muted-foreground shrink-0" />
                                <span class="flex-1">Modifier l'utilisateur</span>
                                <ChevronRightIcon class="h-3.5 w-3.5 text-muted-foreground/60" />
                            </button>
                            <button
                                v-if="canImpersonate"
                                @click="impersonateUser"
                                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-md text-sm text-left transition-colors hover:bg-muted"
                            >
                                <UserIcon class="h-4 w-4 text-muted-foreground shrink-0" />
                                <span class="flex-1">Impersonner</span>
                                <ChevronRightIcon class="h-3.5 w-3.5 text-muted-foreground/60" />
                            </button>
                            <div v-if="canDelete && !user.deleted_at" class="border-t border-border my-1" />
                            <button
                                v-if="canDelete && !user.deleted_at"
                                @click="confirmDelete"
                                class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-md text-sm text-left transition-colors text-destructive hover:bg-destructive/10"
                            >
                                <TrashIcon class="h-4 w-4 shrink-0" />
                                <span class="flex-1">Supprimer</span>
                            </button>
                        </div>
                    </section>

                    <!-- Compte -->
                    <section class="rounded-lg border border-border bg-card">
                        <header class="px-4 py-2.5 border-b border-border">
                            <h3 class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Compte</h3>
                        </header>
                        <dl class="px-4 py-3 space-y-2.5 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Créé le</dt>
                                <dd class="font-medium tabular-nums">{{ formatDate(user.created_at) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Email vérifié</dt>
                                <dd>
                                    <span v-if="user.email_verified_at" class="font-medium tabular-nums">{{ formatDate(user.email_verified_at) }}</span>
                                    <span v-else class="text-xs text-destructive">Non vérifié</span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">CGU acceptées</dt>
                                <dd>
                                    <span v-if="user.terms_accepted_at" class="font-medium tabular-nums">{{ formatDate(user.terms_accepted_at) }}</span>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <!-- Départements -->
                    <section class="rounded-lg border border-border bg-card">
                        <header class="px-4 py-2.5 border-b border-border flex items-center justify-between">
                            <h3 class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Départements</h3>
                            <span v-if="user.departments?.length" class="text-xs text-muted-foreground tabular-nums">{{ user.departments.length }}</span>
                        </header>
                        <div class="px-4 py-3">
                            <ul v-if="user.departments?.length" class="space-y-1.5">
                                <li
                                    v-for="dept in user.departments"
                                    :key="dept.id"
                                    class="flex items-center justify-between gap-2 text-sm"
                                >
                                    <span class="truncate">{{ dept.name }}</span>
                                    <Badge v-if="dept.pivot?.is_head" variant="default" class="text-[10px] shrink-0">Chef</Badge>
                                    <Badge v-else variant="outline" class="text-[10px] shrink-0">Membre</Badge>
                                </li>
                            </ul>
                            <p v-else class="text-xs text-muted-foreground">Aucun département.</p>
                        </div>
                    </section>

                    <!-- Notifications -->
                    <section class="rounded-lg border border-border bg-card">
                        <header class="px-4 py-2.5 border-b border-border">
                            <h3 class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Notifications</h3>
                        </header>
                        <ul class="px-4 py-3 space-y-2.5 text-sm">
                            <li class="flex items-center justify-between gap-3">
                                <span class="text-muted-foreground">NDF à approuver</span>
                                <NotifDot :enabled="!!user.notify_expense_sheet_to_approval" />
                            </li>
                            <li class="flex items-center justify-between gap-3">
                                <span class="text-muted-foreground">Accusé de réception</span>
                                <NotifDot :enabled="!!user.notify_receipt_expense_sheet" />
                            </li>
                            <li class="flex items-center justify-between gap-3">
                                <span class="text-muted-foreground">Rappel d'approbation</span>
                                <NotifDot :enabled="!!user.notify_remind_approval" />
                            </li>
                        </ul>
                    </section>
                </aside>
            </div>

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
import { computed, h, ref } from 'vue'
import { Head, usePage, router, Link } from '@inertiajs/vue3'
import {
    AlertTriangleIcon,
    CalendarIcon,
    CheckCircleIcon,
    ChevronRightIcon,
    ClockIcon,
    FileTextIcon,
    LoaderIcon,
    MailIcon,
    PencilIcon,
    TrashIcon,
    UserIcon,
} from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
} from '@/components/ui/alert-dialog'

const NotifDot = (props) =>
    props.enabled
        ? h('span', { class: 'inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400' }, [
              h('span', { class: 'h-1.5 w-1.5 rounded-full bg-emerald-500' }),
              'Activé',
          ])
        : h('span', { class: 'inline-flex items-center gap-1.5 text-xs text-muted-foreground' }, [
              h('span', { class: 'h-1.5 w-1.5 rounded-full bg-muted-foreground/40' }),
              'Désactivé',
          ])
NotifDot.props = ['enabled']

const page = usePage()
const user = computed(() => page.props.user)
const expenseSheets = computed(() => page.props.expenseSheets ?? [])
const stats = computed(() => page.props.stats ?? {})
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
