<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Building2, FileText, Folder, LayoutGrid, Library, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

import { usePage } from '@inertiajs/vue3';

const page = usePage();
const can = page.props.auth.can as {
    departments: boolean;
    users: boolean;
    forms: boolean;
    // Ajoute d'autres sections ici si tu veux gérer plus de droits
};


const mainNavItems: NavItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
        icon: LayoutGrid
    },
    {
        title: 'Feuilles de frais',
        href: '/expense-sheet',
        icon: FileText
    },
    ...(can.forms ? [{
        title: 'Formulaires',
        href: '/forms',
        icon: Library
    }] : []),
    ...(can.users ? [{
        title: 'Utilisateurs',
        href: '/users',
        icon: Users
    }] : []),
    ...(can.departments ? [{
        title: 'Départements',
        href: '/departments',
        icon: Building2
    }] : [])
];


const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/VilleAndenne/snapfrais',
        icon: Folder
    },
    {
        title: 'Documentation',
        href: 'https://github.com/VilleAndenne/snapfrais/wiki',
        icon: BookOpen
    }
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
