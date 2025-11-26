<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationData {
    current_page: number;
    from: number;
    last_page: number;
    links: PaginationLink[];
    path: string;
    per_page: number;
    to: number;
    total: number;
}

const props = defineProps<{
    pagination: PaginationData;
}>();

const getPageNumber = (label: string): string => {
    if (label === '&laquo; Previous' || label === 'Précédent') return '';
    if (label === 'Next &raquo;' || label === 'Suivant') return '';
    return label;
};
</script>

<template>
    <div v-if="pagination.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
        <div class="text-sm text-muted-foreground order-2 sm:order-1">
            Page {{ pagination.current_page }}/{{ pagination.last_page }}
        </div>

        <nav class="flex items-center gap-1 order-1 sm:order-2">
            <Link
                v-for="(link, index) in pagination.links"
                :key="index"
                :href="link.url || ''"
                :class="[
                    'px-3 py-2 text-sm rounded-md transition-colors',
                    link.active
                        ? 'bg-primary text-primary-foreground font-medium'
                        : link.url
                            ? 'hover:bg-muted'
                            : 'opacity-50 cursor-not-allowed',
                    index === 0 || index === pagination.links.length - 1 ? 'flex items-center gap-1' : ''
                ]"
                :preserve-scroll="true"
                :only="['expenseSheets']"
            >
                <ChevronLeft v-if="index === 0" class="h-4 w-4" />
                <span v-if="index === 0">Précédent</span>
                <span v-else-if="index === pagination.links.length - 1">Suivant</span>
                <span v-else v-html="getPageNumber(link.label)"></span>
                <ChevronRight v-if="index === pagination.links.length - 1" class="h-4 w-4" />
            </Link>
        </nav>
    </div>
</template>
