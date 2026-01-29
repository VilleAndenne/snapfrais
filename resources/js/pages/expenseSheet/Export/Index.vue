<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Calendar, FileDown, ArrowRight, Download, Clock, CheckCircle, XCircle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const form = useForm({
    start_date: '',
    end_date: '',
});

const props = defineProps({
    exports: Array
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

const getStatusBadge = (status) => {
    switch (status) {
        case 'completed':
            return { variant: 'default', icon: CheckCircle, text: 'Terminé' };
        case 'pending':
            return { variant: 'secondary', icon: Clock, text: 'En cours' };
        case 'failed':
            return { variant: 'destructive', icon: XCircle, text: 'Échec' };
        default:
            return { variant: 'secondary', icon: Clock, text: 'Inconnu' };
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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

        <div class="p-4 md:p-6 space-y-6">
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

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Historique des exports
                    </CardTitle>
                    <CardDescription>
                        Consultez l'historique de vos exports précédents
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Période</TableHead>
                                    <TableHead>Date d'export</TableHead>
                                    <TableHead>Créé par</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Taille</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="history in exports" :key="history.id">
                                    <TableCell class="font-medium">
                                        {{ history.start_date }} - {{ history.end_date }}
                                    </TableCell>
                                    <TableCell>
                                        {{ formatDate(history.created_at) }}
                                    </TableCell>
                                    <TableCell>
                                        {{ history.created_by_name }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadge(history.status).variant" class="flex items-center gap-1 w-fit">
                                            <component :is="getStatusBadge(history.status).icon" class="h-3 w-3" />
                                            {{ getStatusBadge(history.status).text }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {{ history.file_size || '-' }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <a
                                            v-if="history.status === 'completed'"
                                            class="flex items-center gap-1"
                                            target="_blank"
                                            :href="history.file_url"
                                        >
                                            <Download class="h-4 w-4" />
                                            Télécharger
                                        </a>
                                        <span v-else class="text-muted-foreground text-sm">-</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
