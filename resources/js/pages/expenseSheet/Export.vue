<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, FileDown, ArrowRight } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const form = useForm({
    start_date: '',
    end_date: '',
});

const isLoading = ref(false);

const submit = () => {
    isLoading.value = true;
    // Simuler un délai de chargement
    setTimeout(() => {
        isLoading.value = false;
        // Redirection gérée par le lien
    }, 500);
};

const breadcrumbs = [
    {
        title: 'Notes de frais',
        href: route('expense-sheet.index')
    },
    {
        title: 'Exporter',
        href: route('expense-sheets.export')
    }
];
</script>

<template>
    <AppLayout title="Exporter les notes de frais" description="Exportez vos notes de frais par période" :breadcrumbs="breadcrumbs">
        <Head title="Exporter les notes de frais" />

        <div class="p-4 md:p-6 max-w-xl mx-auto">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileDown class="h-5 w-5" />
                        Exporter les notes de frais
                    </CardTitle>
                    <CardDescription>
                        Sélectionnez une période pour exporter vos notes de frais
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="start_date" class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    Date de début
                                </Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    v-model="form.start_date"
                                    required
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="end_date" class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    Date de fin
                                </Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    v-model="form.end_date"
                                    required
                                />
                            </div>
                        </div>
                    </form>
                </CardContent>

                <CardFooter class="flex justify-end">
                    <a
                        :href="route('expense-sheets.export', { start_date: form.start_date, end_date: form.end_date })"
                        :class="{ 'pointer-events-none opacity-70': !form.start_date || !form.end_date }"
                        target="_blank"
                    >
                        <Button
                            :disabled="!form.start_date || !form.end_date"
                            :loading="isLoading"
                            class="flex items-center gap-2"
                        >
                            <FileDown class="h-4 w-4" />
                            Exporter
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </a>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
