<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import PatchNoteModal from '@/components/PatchNoteModal.vue';
import RequiredFieldsModal from '@/components/RequiredFieldsModal.vue';
import type { BreadcrumbItemType } from '@/types';

import { watch, ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useFlashStore } from '@/stores/flash';

// Icônes Lucide
import { CheckCircle, AlertTriangle, X as LucideX } from 'lucide-vue-next';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const flash = useFlashStore();

const show = ref(false);
const flashMessage = computed(() => flash.success || flash.error);
const isSuccess = computed(() => !!flash.success);

// Surveille les props flash transmises par Laravel (via Inertia)
watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash?.success) {
            flash.setSuccess(newFlash.success);
            show.value = true;
        }
        if (newFlash?.error) {
            flash.setError(newFlash.error);
            show.value = true;
        }

        // Fermeture auto après 5s
        if (newFlash?.success || newFlash?.error) {
            setTimeout(() => {
                show.value = false;
                flash.clear();
            }, 5000);
        }
    },
    { immediate: true }
);
</script>

<template>
    <!-- Layout principal -->
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>

    <!-- Notification Toast (Lucide) -->
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-50">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="show && flashMessage"
                    class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black/5"
                >
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="shrink-0 pt-0.5">
                                <CheckCircle v-if="isSuccess" class="size-6 text-green-500" />
                                <AlertTriangle v-else class="size-6 text-red-500" />
                            </div>
                            <div class="ml-3 w-0 flex-1 pt-0.5">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ isSuccess ? 'Succès' : 'Erreur' }}
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ flashMessage }}
                                </p>
                            </div>
                            <div class="ml-4 flex shrink-0">
                                <button
                                    type="button"
                                    @click="() => { show = false; flash.clear(); }"
                                    class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none"
                                >
                                    <span class="sr-only">Fermer</span>
                                    <LucideX class="size-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>

    <!-- Modal Patch Notes -->
    <PatchNoteModal />

    <!-- Modal Champs obligatoires (numéro de compte bancaire et adresse) -->
    <RequiredFieldsModal />
</template>
