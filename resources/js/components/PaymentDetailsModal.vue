<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type SharedData } from '@/types';

const page = usePage<SharedData>();

const missing = computed<string[]>(() => page.props.missingPaymentDetails ?? []);

// Sur la page de profil, l'agent dispose déjà des mêmes champs : la fenêtre
// ferait doublon.
const isOnProfilePage = computed(() => page.url.startsWith('/settings/profile'));

// Report volontaire : la fenêtre réapparaît à la prochaine ouverture.
const postponed = ref(false);

const isOpen = computed(() => missing.value.length > 0 && !isOnProfilePage.value && !postponed.value);

const form = useForm({
    bank_account_number: '',
    address: '',
});

// Les champs déjà remplis n'ont pas à être ressaisis : on ne demande que ce qui manque.
const needsBankAccount = computed(() => missing.value.includes('bank_account_number'));
const needsAddress = computed(() => missing.value.includes('address'));

watch(isOpen, (open) => {
    if (open) {
        form.clearErrors();
    }
});

const submit = () => {
    form.patch(route('payment-details.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="(value) => { if (!value) postponed = true; }">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Vos coordonnées de remboursement</DialogTitle>
                <DialogDescription>
                    Certains frais que vous avancez pour le compte de l'administration sont remboursés
                    directement par la Direction des Services Financiers, par virement. Ces informations
                    lui sont nécessaires pour vous payer.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div v-if="needsBankAccount" class="grid gap-2">
                    <Label for="modal_bank_account_number">Numéro de compte (IBAN)</Label>
                    <Input
                        id="modal_bank_account_number"
                        v-model="form.bank_account_number"
                        placeholder="BE68 5390 0754 7034"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.bank_account_number" />
                </div>

                <div v-if="needsAddress" class="grid gap-2">
                    <Label for="modal_address">Adresse complète</Label>
                    <Textarea
                        id="modal_address"
                        v-model="form.address"
                        rows="3"
                        placeholder="Rue, numéro, code postal et localité"
                    />
                    <InputError :message="form.errors.address" />
                </div>

                <p class="text-sm text-muted-foreground">
                    Ces données ne sont reprises que dans les demandes de remboursement adressées à la
                    Direction des Services Financiers. Elles ne figurent pas sur vos notes de frais et ne
                    sont visibles ni de votre responsable, ni des autres agents.
                </p>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="ghost" @click="postponed = true">Plus tard</Button>
                    <Button type="submit" :disabled="form.processing">Enregistrer</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
