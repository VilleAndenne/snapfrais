<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<SharedData>();

const label = computed<string>(() => {
    const user = page.props.auth?.user;
    const isAdmin = Boolean(user?.is_admin || user?.super_admin);
    const organizationName = page.props.organization?.organizationName;

    return isAdmin && organizationName ? organizationName : 'SnapFrais';
});
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-white text-sidebar-primary-foreground">
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm sm:text-base">
        <span class="mb-0.5 truncate font-semibold leading-none">{{ label }}</span>
    </div>
</template>
