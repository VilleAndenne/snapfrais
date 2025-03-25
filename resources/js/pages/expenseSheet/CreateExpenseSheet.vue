<template>
    <AppLayout>
        <div class="container mx-auto p-6">
            <h1 class="text-xl font-bold mb-4">Créer une note de frais</h1>

            <form @submit.prevent="submitForm" class="space-y-4">
                <!-- Type principal -->
                <Select v-model="form.type">
                    <SelectTrigger>
                        <SelectValue placeholder="Type de remboursement" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="km">Kilométrage</SelectItem>
                        <SelectItem value="fixed">Fixe</SelectItem>
                        <SelectItem value="percentage">Pourcentage</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Liste des coûts associés -->
                <div v-for="(cost, index) in form.costs" :key="index" class="space-y-2 border p-4 rounded-md">
                    <Select v-model="cost.type">
                        <SelectTrigger>
                            <SelectValue placeholder="Type de coût" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="costType in costTypes" :key="costType.id" :value="costType.name">
                                {{ costType.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Input v-model="cost.date" type="date" />
                    <Input v-model.number="cost.amount" placeholder="Montant" />

                    <Button @click="removeCost(index)" variant="destructive">
                        Supprimer
                    </Button>
                </div>
                
                <Button @click="addCost" variant="outline">Ajouter un coût</Button>

                <!-- Montant total -->
                <Input v-model.number="form.total" placeholder="Montant total" />

                <div class="flex justify-end space-x-2">
                    <Button type="button" variant="outline" @click="cancelForm">
                        Annuler
                    </Button>
                    <Button type="submit">Créer</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, reactive } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Trash2Icon,
    PlusIcon,
    Loader2Icon
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    form: Object,
});

const form = useForm({
    costs: [],
});

console.log(props.form)

const submitForm = () => {
    form.post(route('expense-sheets.store'));
};

const cancelForm = () => {
    window.history.back();
};
</script>
