<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>🎉 Nouveautés de l'application</DialogTitle>
                <DialogDescription v-if="patchNotes.length > 0">
                    {{ patchNotes.length }} nouvelle(s) mise(s) à jour disponible(s)
                </DialogDescription>
            </DialogHeader>

            <div v-if="patchNotes.length > 0" class="max-h-96 space-y-4 overflow-y-auto py-4">
                <div
                    v-for="(patchNote, index) in patchNotes"
                    :key="patchNote.id"
                    class="rounded border border-border bg-muted/30 p-4"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold">{{ patchNote.title }}</h3>
                            <div class="mt-2">
                                <MarkdownRenderer :content="patchNote.content" />
                            </div>
                            <div class="mt-2 text-xs text-muted-foreground">
                                {{ formatDate(patchNote.created_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button @click="markAllAsRead" :disabled="processing">
                    <Loader2Icon v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                    {{ processing ? 'Fermeture...' : 'J\'ai compris' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import axios from 'axios';
import { Loader2Icon } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const isOpen = ref(false);
const patchNotes = ref([]);
const processing = ref(false);

// Charger les patch notes non lues au montage du composant
onMounted(async () => {
    try {
        console.log('🔍 Chargement des patch notes non lues...');
        const response = await axios.get('/api/patch-notes/unread');
        console.log('📦 Patch notes reçues:', response.data);
        patchNotes.value = response.data;

        // Ouvrir le modal s'il y a des patch notes non lues
        if (patchNotes.value.length > 0) {
            console.log('✅ Ouverture du modal avec', patchNotes.value.length, 'patch note(s)');
            isOpen.value = true;
        } else {
            console.log('ℹ️ Aucune patch note non lue');
        }
    } catch (error) {
        console.error('❌ Erreur lors du chargement des patch notes:', error);
    }
});

// Marquer toutes les patch notes comme lues
const markAllAsRead = async () => {
    processing.value = true;

    try {
        // Marquer chaque patch note comme lue
        for (const patchNote of patchNotes.value) {
            await axios.post(`/api/patch-notes/${patchNote.id}/mark-as-read`);
        }

        // Fermer le modal
        isOpen.value = false;
    } catch (error) {
        console.error('Erreur lors du marquage des patch notes:', error);
    } finally {
        processing.value = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>