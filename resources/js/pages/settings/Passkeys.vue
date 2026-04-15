<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { KeyRound, Loader2, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type Passkey = {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string | null;
};

defineProps<{
    passkeys: Passkey[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: "Clés d'accès",
        href: '/settings/passkeys',
    },
];

const isSupported = ref(true);
const isRegistering = ref(false);
const generalError = ref<string | null>(null);
const deletingId = ref<number | null>(null);

const form = useForm({
    name: '',
});

const canSubmit = computed(() => isSupported.value && !isRegistering.value && form.name.trim().length > 0);

onMounted(() => {
    isSupported.value = typeof window.browserSupportsWebAuthn === 'function' && window.browserSupportsWebAuthn();
});

const formatDate = (value: string | null): string => {
    if (!value) {
        return 'Jamais';
    }

    return new Date(value).toLocaleString('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};

const registerPasskey = async () => {
    generalError.value = null;
    form.clearErrors();

    if (!isSupported.value) {
        generalError.value = "Votre navigateur ne prend pas en charge les clés d'accès.";
        return;
    }

    isRegistering.value = true;

    try {
        const response = await fetch(route('passkeys.options'), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('options_failed');
        }

        const optionsJSON = await response.json();
        const passkey = await window.startRegistration({ optionsJSON });

        form
            .transform((data) => ({
                name: data.name,
                options: JSON.stringify(optionsJSON),
                passkey: JSON.stringify(passkey),
            }))
            .post(route('passkeys.store'), {
                preserveScroll: true,
                onSuccess: () => form.reset('name'),
                onFinish: () => {
                    isRegistering.value = false;
                },
            });
    } catch (error) {
        console.error('[passkey] registration failed', error);
        isRegistering.value = false;
        const domError = error as DOMException;
        const name = domError?.name;
        if (name === 'NotAllowedError' || name === 'AbortError') {
            generalError.value = "Enregistrement annulé.";
        } else if (name === 'SecurityError') {
            generalError.value = "Le domaine n'est pas autorisé pour les clés d'accès. Utilisez « localhost » plutôt qu'une adresse IP en local.";
        } else if (domError?.message) {
            generalError.value = `Erreur : ${domError.message}`;
        } else {
            generalError.value = "Impossible de générer la clé d'accès. Veuillez réessayer.";
        }
    }
};

const deletePasskey = (id: number) => {
    deletingId.value = id;
    router.delete(route('passkeys.destroy', { passkey: id }), {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Clés d'accès" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Clés d'accès"
                    description="Gérez les clés d'accès (passkeys) associées à votre compte. Elles permettent une connexion sécurisée sans mot de passe."
                />

                <div
                    v-if="!isSupported"
                    class="rounded-md border border-amber-500/40 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    Votre navigateur actuel ne prend pas en charge les clés d'accès. Utilisez un navigateur compatible (Chrome, Safari, Firefox récent) pour en enregistrer.
                </div>

                <form v-if="isSupported" @submit.prevent="registerPasskey" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="passkey-name">Nom de la clé d'accès</Label>
                        <Input
                            id="passkey-name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            maxlength="50"
                            placeholder="Ex. MacBook Pro, iPhone, clé YubiKey…"
                            :disabled="isRegistering"
                        />
                        <InputError :message="form.errors.name" />
                        <p v-if="generalError" class="text-sm text-destructive">{{ generalError }}</p>
                    </div>

                    <Button type="submit" :disabled="!canSubmit">
                        <Loader2 v-if="isRegistering" class="h-4 w-4 animate-spin" />
                        <KeyRound v-else class="h-4 w-4" />
                        Ajouter une clé d'accès
                    </Button>
                </form>

                <div class="space-y-3">
                    <h3 class="text-sm font-medium">Clés enregistrées</h3>

                    <p v-if="passkeys.length === 0" class="text-sm text-muted-foreground">
                        Aucune clé d'accès enregistrée pour le moment.
                    </p>

                    <ul v-else class="divide-y rounded-md border">
                        <li
                            v-for="passkey in passkeys"
                            :key="passkey.id"
                            class="flex items-center justify-between gap-4 px-4 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ passkey.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Dernière utilisation : {{ formatDate(passkey.last_used_at) }}
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                :disabled="deletingId === passkey.id"
                                aria-label="Supprimer la clé d'accès"
                                @click="deletePasskey(passkey.id)"
                            >
                                <Loader2 v-if="deletingId === passkey.id" class="h-4 w-4 animate-spin" />
                                <Trash2 v-else class="h-4 w-4" />
                            </Button>
                        </li>
                    </ul>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
