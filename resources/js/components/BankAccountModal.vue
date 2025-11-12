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

// Afficher le modal si l'utilisateur n'a pas de numéro de compte ET qu'il n'est pas sur la page de profil
watch([() => user.value?.bank_account_number, isOnProfilePage], ([bankAccount, onProfilePage]) => {
    if (!bankAccount && !onProfilePage) {
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
                        Pour améliorer le traitement de vos demandes de remboursement, nous avons besoin de votre numéro de compte bancaire.
                    </p>
                    <p>
                        Cette information sera stockée de manière sécurisée et chiffrée dans notre système.
                    </p>
                    <p class="font-medium">
                        Merci de bien vouloir compléter cette information dans votre profil.
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
