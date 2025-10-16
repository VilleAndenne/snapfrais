<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Paramètres de notifications',
        href: '/settings/notifications',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    notify_expense_sheet_to_approval: user.notify_expense_sheet_to_approval ?? true,
    notify_receipt_expense_sheet: user.notify_receipt_expense_sheet ?? true,
    notify_remind_approval: user.notify_remind_approval ?? true,
});

const submit = () => {
    form.patch(route('notifications.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Notification settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Préférences de notifications"
                    description="Choisissez les notifications par email que vous souhaitez recevoir."
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <Checkbox
                                id="notify_expense_sheet_to_approval"
                                v-model:checked="form.notify_expense_sheet_to_approval"
                                class="mt-1"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <Label
                                    for="notify_expense_sheet_to_approval"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Nouvelle note de frais à valider
                                </Label>
                                <p class="text-sm text-muted-foreground">
                                    Recevoir un email lorsqu'une nouvelle note de frais est soumise pour validation.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <Checkbox
                                id="notify_receipt_expense_sheet"
                                v-model:checked="form.notify_receipt_expense_sheet"
                                class="mt-1"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <Label
                                    for="notify_receipt_expense_sheet"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Accusé de réception de note de frais
                                </Label>
                                <p class="text-sm text-muted-foreground">
                                    Recevoir un email de confirmation lorsque votre note de frais a été reçue.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <Checkbox
                                id="notify_remind_approval"
                                v-model:checked="form.notify_remind_approval"
                                class="mt-1"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <Label
                                    for="notify_remind_approval"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Rappel de notes de frais en attente
                                </Label>
                                <p class="text-sm text-muted-foreground">
                                    Recevoir des rappels périodiques pour les notes de frais en attente de validation.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Sauvegarder</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Sauvegardé.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
