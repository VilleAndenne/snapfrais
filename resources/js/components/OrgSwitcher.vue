<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenuButton, useSidebar } from '@/components/ui/sidebar';
import { type OrganizationSwitcher, type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const { isMobile, state } = useSidebar();

const switcher = computed<OrganizationSwitcher | null>(() => page.props.organizationSwitcher);

function switchTo(id: number): void {
    if (id === switcher.value?.current) {
        return;
    }

    router.post(route('organizations.switch', id), {}, { preserveScroll: true });
}
</script>

<template>
    <DropdownMenu v-if="switcher">
        <DropdownMenuTrigger as-child>
            <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                <AppLogo />
                <ChevronsUpDown class="ml-auto size-4" />
            </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            class="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
            :side="isMobile ? 'bottom' : state === 'collapsed' ? 'right' : 'bottom'"
            align="start"
            :side-offset="4"
        >
            <DropdownMenuLabel class="text-xs text-muted-foreground">Organisations</DropdownMenuLabel>
            <DropdownMenuItem
                v-for="option in switcher.options"
                :key="option.id"
                class="cursor-pointer"
                @select="switchTo(option.id)"
            >
                <span class="truncate">{{ option.organizationName }}</span>
                <Check v-if="option.id === switcher.current" class="ml-auto size-4" />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
