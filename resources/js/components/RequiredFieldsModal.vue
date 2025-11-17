<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { type SharedData } from '@/types';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);

const isOpen = ref(false);

// Vérifier si on est sur la page de profil
const isOnProfilePage = computed(() => {
    return page.url.startsWith('/settings/profile');
});

// Vérifier les champs manquants
const missingFields = computed(() => {
    const fields: string[] = [];
    if (!user.value?.bank_account_number) {
        fields.push('votre numéro de compte bancaire');
    }
    if (!user.value?.address) {
        fields.push('votre adresse complète');
    }
    return fields;
});

const hasMissingFields = computed(() => missingFields.value.length > 0);

// Message formaté pour la liste des champs manquants
const missingFieldsMessage = computed(() => {
    if (missingFields.value.length === 0) return '';
    if (missingFields.value.length === 1) return missingFields.value[0];
    if (missingFields.value.length === 2) {
        return missingFields.value.join(' et ');
    }
    return missingFields.value.slice(0, -1).join(', ') + ' et ' + missingFields.value[missingFields.value.length - 1];
});

// Afficher le modal si des champs sont manquants ET qu'on n'est pas sur la page de profil
watch([hasMissingFields, isOnProfilePage], ([missing, onProfilePage]) => {
    if (missing && !onProfilePage) {
        isOpen.value = true;
    } else {
        isOpen.value = false;
    }
}, { immediate: true });

const goToProfile = () => {
    isOpen.value = false;
    router.visit(route('profile.edit'));
};
</script>

<template>
    <AlertDialog :open="isOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Information importante</AlertDialogTitle>
                <AlertDialogDescription class="space-y-3">
                    <p>
                        Pour améliorer le traitement de vos demandes de remboursement, nous avons besoin de {{ missingFieldsMessage }}.
                    </p>
                    <p>
                        Ces informations seront stockées de manière sécurisée et chiffrée dans notre système.
                    </p>
                    <p class="font-medium">
                        Merci de bien vouloir compléter {{ missingFields.length > 1 ? 'ces informations' : 'cette information' }} dans votre profil.
                    </p>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogAction @click="goToProfile">
                    J'ai compris
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
