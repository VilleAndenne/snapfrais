<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Patch Notes" />

        <div class="container mx-auto space-y-6 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-foreground">Patch Notes</h1>
                <Button @click="router.visit('/patch-notes/create')">
                    <PlusIcon class="mr-2 h-4 w-4" />
                    Créer une patch note
                </Button>
            </div>

            <!-- Liste des patch notes -->
            <div v-if="patchNotes.length > 0" class="space-y-4">
                <div
                    v-for="patchNote in patchNotes"
                    :key="patchNote.id"
                    class="rounded border border-border bg-card p-4"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold">{{ patchNote.title }}</h3>
                            <div class="mt-2">
                                <MarkdownRenderer :content="patchNote.content" />
                            </div>
                            <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                                <span>Créée par {{ patchNote.creator.name }}</span>
                                <span>Le {{ formatDate(patchNote.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message si pas de patch notes -->
            <div v-else class="rounded border border-dashed border-border p-12 text-center">
                <p class="text-muted-foreground">Aucune patch note pour le moment.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';

const props = defineProps({
    patchNotes: { type: Array, required: true },
});

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

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Patch Notes' },
];
</script>