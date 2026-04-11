<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <Head title="Conditions générales d'utilisation" />

    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                <Link :href="route('home')" class="flex items-center gap-2 font-medium">
                    <div class="flex h-8 w-8 items-center justify-center">
                        <AppLogoIcon />
                    </div>
                    <span class="text-base font-semibold">SnapFrais</span>
                </Link>
                <Link
                    :href="route('login')"
                    class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                >
                    Retour à la connexion
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
            <article class="space-y-8">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold tracking-tight">Conditions générales d'utilisation</h1>
                    <p class="text-sm text-muted-foreground">Dernière mise à jour : 11 avril 2026</p>
                </div>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">1. Objet</h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        Les présentes conditions générales d'utilisation (ci-après « CGU ») ont pour objet de définir les
                        modalités et conditions dans lesquelles les utilisateurs accèdent et utilisent l'application
                        SnapFrais, dédiée à la gestion des notes de frais.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">2. Acceptation des conditions</h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        L'accès et l'utilisation de SnapFrais sont conditionnés à l'acceptation pleine et entière des
                        présentes CGU. En se connectant à l'application, l'utilisateur reconnaît avoir pris connaissance
                        des CGU et les accepte sans réserve.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">3. Accès au service</h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        L'accès à SnapFrais est réservé aux utilisateurs disposant d'un compte valide créé par
                        l'administration. L'utilisateur s'engage à conserver confidentiels ses identifiants de connexion
                        et à ne pas les communiquer à un tiers.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">4. Utilisation du service</h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        L'utilisateur s'engage à utiliser SnapFrais conformément à sa destination, dans le respect des
                        lois et règlements en vigueur ainsi que des politiques internes de son organisation. Toute
                        utilisation frauduleuse, abusive ou contraire aux présentes CGU pourra entraîner la suspension
                        ou la suppression du compte concerné.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">5. Données personnelles</h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        Les données personnelles collectées dans le cadre de l'utilisation de SnapFrais sont traitées
                        conformément à la réglementation applicable, notamment au Règlement Général sur la Protection
                        des Données (RGPD). Elles sont utilisées exclusivement pour les besoins de la gestion des notes
                        de frais.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">6. Responsabilité</h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        L'utilisateur est seul responsable de l'exactitude des informations qu'il saisit dans
                        l'application, notamment des montants déclarés et des justificatifs téléversés. SnapFrais ne
                        saurait être tenu responsable des conséquences d'une déclaration erronée ou frauduleuse.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">7. Certification des données</h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        En utilisant la plateforme, l'utilisateur certifie que l'ensemble des données qu'il insère dans
                        l'application sont sincères, exactes et conformes à la réalité. Toute déclaration inexacte,
                        incomplète ou frauduleuse engage la pleine et entière responsabilité de l'utilisateur.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">8. Modification des CGU</h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        Les présentes CGU peuvent être modifiées à tout moment. La version applicable est celle en
                        vigueur au moment de la connexion de l'utilisateur. Il est recommandé de consulter régulièrement
                        cette page.
                    </p>
                </section>
            </article>
        </main>

        <footer class="border-t">
            <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4 text-xs text-muted-foreground">
                <span>&copy; {{ new Date().getFullYear() }} SnapFrais. Tous droits réservés.</span>
                <Link :href="route('login')" class="underline underline-offset-4 hover:text-foreground">
                    Connexion
                </Link>
            </div>
        </footer>
    </div>
</template>
