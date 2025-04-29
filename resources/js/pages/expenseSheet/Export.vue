<template>
    <div class="max-w-md mx-auto p-6 bg-white rounded-2xl shadow">
        <h2 class="text-2xl font-bold mb-6">Exporter les notes de frais</h2>

        <form @submit.prevent="submit">
            <div class="mb-4">
                <label for="start_date" class="block text-sm font-medium mb-1">Date de début</label>
                <input
                    id="start_date"
                    type="date"
                    v-model="form.start_date"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200"
                    required
                />
<!--                <InputError :message="form.errors.start_date" class="mt-2" />-->
            </div>

            <div class="mb-6">
                <label for="end_date" class="block text-sm font-medium mb-1">Date de fin</label>
                <input
                    id="end_date"
                    type="date"
                    v-model="form.end_date"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-blue-200"
                    required
                />
<!--                <InputError :message="form.errors.end_date" class="mt-2" />-->
            </div>

            <div class="flex items-center justify-end">
                <a
                    :href="route('expense-sheets.export', { start_date: form.start_date, end_date: form.end_date })"
                    class="inline-block px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                    target="_blank"
                >
                    Exporter
                </a>
            </div>
        </form>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import {Button} from '@/components/ui/button'

const form = useForm({
    start_date: '',
    end_date: '',
});
</script>
