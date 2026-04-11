<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    accepted: false,
});

const submit = () => {
    form.post(route('terms.accept.store'));
};
</script>

<template>
    <Head title="Acceptation des conditions générales d'utilisation" />

    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-2xl">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                        <div class="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                            <AppLogoIcon class="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                        </div>
                        <span class="sr-only">SnapFrais</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">Mise à jour des conditions générales d'utilisation</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            Nos conditions générales d'utilisation ont été mises à jour. Pour continuer à utiliser
                            SnapFrais, vous devez prendre connaissance et accepter la nouvelle version.
                        </p>
                    </div>
                </div>

                <div class="rounded-lg border bg-card p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-base font-semibold">Conditions générales d'utilisation</h2>
                        <Link
                            :href="route('terms.show')"
                            target="_blank"
                            class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                        >
                            Ouvrir dans un nouvel onglet
                        </Link>
                    </div>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        Veuillez lire attentivement les conditions générales d'utilisation avant de les accepter. En
                        utilisant la plateforme, vous certifiez que l'ensemble des données que vous insérez dans
                        l'application sont sincères, exactes et conformes à la réalité.
                    </p>
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-6">
                    <Label for="accepted" class="flex items-start gap-3 text-sm">
                        <Checkbox id="accepted" v-model:checked="form.accepted" />
                        <span>
                            J'ai lu et j'accepte sans réserve les
                            <Link
                                :href="route('terms.show')"
                                target="_blank"
                                class="underline underline-offset-4 hover:text-foreground"
                            >
                                conditions générales d'utilisation
                            </Link>
                            de SnapFrais.
                        </span>
                    </Label>
                    <InputError :message="form.errors.accepted" />

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                        >
                            Se déconnecter
                        </Link>
                        <Button type="submit" :disabled="form.processing || !form.accepted">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            J'accepte et je continue
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
