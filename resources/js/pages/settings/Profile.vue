<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Paramètre du profil',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
    bank_account_number: user.bank_account_number || '',
    address: user.address || '',
});

const showBankAccountRequired = ref(false);
const showAddressRequired = ref(false);

// Vérifier si le numéro de compte ou l'adresse sont manquants au chargement
onMounted(() => {
    if (!user.bank_account_number) {
        showBankAccountRequired.value = true;
        // Scroll vers le champ après un court délai
        nextTick(() => {
            const element = document.getElementById('bank_account_number');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.focus();
            }
        });
    }

    if (!user.address) {
        showAddressRequired.value = true;
        // Si le numéro de compte n'est pas manquant, scroll vers l'adresse
        if (user.bank_account_number) {
            nextTick(() => {
                const element = document.getElementById('address');
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    element.focus();
                }
            });
        }
    }
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showBankAccountRequired.value = false;
            showAddressRequired.value = false;
        }
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Information de votre profil" description="Mettez à jour vos informations de profil." />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nom</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Nom complet" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Adresse email</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Adresse email"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2" :class="{ 'p-4 border-2 border-red-500 rounded-lg bg-red-50': showBankAccountRequired }">
                        <Label for="bank_account_number" :class="{ 'text-red-600 font-semibold': showBankAccountRequired }">
                            Numéro de compte bancaire
                            <span v-if="showBankAccountRequired" class="text-red-600">*</span>
                        </Label>
                        <Input
                            id="bank_account_number"
                            type="text"
                            :class="[
                                'mt-1 block w-full',
                                showBankAccountRequired ? 'border-red-500 focus:border-red-600 focus:ring-red-500' : ''
                            ]"
                            v-model="form.bank_account_number"
                            autocomplete="off"
                            placeholder="BE00 0000 0000 0000"
                        />
                        <p v-if="showBankAccountRequired" class="text-sm font-medium text-red-600">
                            ⚠️ Ce champ est obligatoire pour le bon fonctionnement des nouvelles fonctionnalités.
                        </p>
                        <p v-else class="text-sm text-muted-foreground">
                            Ce numéro est stocké de manière sécurisée et chiffrée. Il sera utilisé pour le traitement de vos remboursements.
                        </p>
                        <InputError class="mt-2" :message="form.errors.bank_account_number" />
                    </div>

                    <div class="grid gap-2" :class="{ 'p-4 border-2 border-red-500 rounded-lg bg-red-50': showAddressRequired }">
                        <Label for="address" :class="{ 'text-red-600 font-semibold': showAddressRequired }">
                            Adresse complète
                            <span v-if="showAddressRequired" class="text-red-600">*</span>
                        </Label>
                        <Input
                            id="address"
                            type="text"
                            :class="[
                                'mt-1 block w-full',
                                showAddressRequired ? 'border-red-500 focus:border-red-600 focus:ring-red-500' : ''
                            ]"
                            v-model="form.address"
                            autocomplete="street-address"
                            placeholder="Rue, numéro, code postal, ville"
                        />
                        <p v-if="showAddressRequired" class="text-sm font-medium text-red-600">
                            ⚠️ Ce champ est obligatoire pour le bon fonctionnement des nouvelles fonctionnalités.
                        </p>
                        <p v-else class="text-sm text-muted-foreground">
                            Cette adresse est stockée de manière sécurisée et chiffrée. Elle sera utilisée pour le traitement de vos remboursements.
                        </p>
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Votre adresse email n'est pas encore vérifiée.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:!decoration-current dark:decoration-neutral-500"
                            >
                                Cliquez ici pour renvoyer le lien de vérification.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            Un nouveau lien de vérification a été envoyé à votre adresse email.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Sauvegarder</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Sauvegardé.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
