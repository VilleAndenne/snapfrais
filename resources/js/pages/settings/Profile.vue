<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    paymentDetails: {
        bank_account_number: string | null;
        address: string | null;
    };
}

const props = defineProps<Props>();

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
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

const paymentForm = useForm({
    bank_account_number: props.paymentDetails.bank_account_number ?? '',
    address: props.paymentDetails.address ?? '',
});

const submitPaymentDetails = () => {
    paymentForm.patch(route('payment-details.update'), {
        preserveScroll: true,
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

                <div class="border-t pt-6">
                    <HeadingSmall
                        title="Coordonnées de remboursement"
                        description="Utilisées par la Direction des Services Financiers pour vous rembourser les frais que vous avancez pour le compte de l'administration."
                    />

                    <form @submit.prevent="submitPaymentDetails" class="mt-6 space-y-6">
                        <div class="grid gap-2">
                            <Label for="bank_account_number">Numéro de compte (IBAN)</Label>
                            <Input
                                id="bank_account_number"
                                v-model="paymentForm.bank_account_number"
                                placeholder="BE68 5390 0754 7034"
                                autocomplete="off"
                            />
                            <InputError :message="paymentForm.errors.bank_account_number" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="address">Adresse complète</Label>
                            <Textarea
                                id="address"
                                v-model="paymentForm.address"
                                rows="3"
                                placeholder="Rue, numéro, code postal et localité"
                            />
                            <InputError :message="paymentForm.errors.address" />
                        </div>

                        <p class="text-sm text-muted-foreground">
                            Ces données ne figurent que sur les demandes adressées à la Direction des Services
                            Financiers. Elles n'apparaissent pas sur vos notes de frais et ne sont visibles ni de
                            votre responsable, ni des autres agents.
                        </p>

                        <div class="flex items-center gap-4">
                            <Button :disabled="paymentForm.processing">Sauvegarder</Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="paymentForm.recentlySuccessful" class="text-sm text-neutral-600">Sauvegardé.</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
