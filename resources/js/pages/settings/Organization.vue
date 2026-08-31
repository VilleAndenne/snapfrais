<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface OrganizationSettings {
    id: number;
    organizationName: string | null;
    dsf_recipient_email: string | null;
}

const props = defineProps<{ organization: OrganizationSettings }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Organisation',
        href: '/settings/organization',
    },
];

const form = useForm({
    dsf_recipient_email: props.organization.dsf_recipient_email ?? '',
});

const submit = () => {
    form.patch(route('organization.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Paramètres de l'organisation" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Paramètres de l'organisation"
                    :description="`Réglages appliqués à toute l'organisation ${organization.organizationName ?? ''}.`"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="dsf_recipient_email">Adresse email du service comptabilité (DSF)</Label>
                        <Input
                            id="dsf_recipient_email"
                            type="email"
                            v-model="form.dsf_recipient_email"
                            placeholder="comptabilite@exemple.be"
                            autocomplete="off"
                        />
                        <p class="text-sm text-muted-foreground">
                            Destinataire du PDF de demande de remboursement envoyé automatiquement lorsqu'une note de
                            frais approuvée contient des coûts traités par la Direction des Services Financiers. Sans
                            adresse, aucun envoi n'est effectué.
                        </p>
                        <InputError :message="form.errors.dsf_recipient_email" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Enregistrer</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-muted-foreground">Enregistré.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
