<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="SnapFrais - Gestion de Notes de Frais">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] lg:justify-center lg:p-8">
        <header class="mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-[#f53003] dark:text-[#FF4433]">
                        <path d="M21 7V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V7C3 4 4.5 2 8 2H16C19.5 2 21 4 21 7Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14.5 4.5V6.5C14.5 7.6 15.4 8.5 16.5 8.5H18.5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 13H12" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 17H16" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-xl font-bold">SnapFrais</span>
                </div>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props?.auth?.user"
                        :href="route('dashboard')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        >
                            Connexion
                        </Link>
                    </template>
                </div>
            </nav>
        </header>
        <div class="duration-750 starting:opacity-0 flex w-full items-center justify-center opacity-100 transition-opacity lg:grow">
            <main class="flex w-full max-w-[335px] flex-col overflow-hidden lg:max-w-4xl">
                <!-- Hero Section -->
                <section class="relative -mb-px w-full overflow-hidden rounded-t-lg bg-[#fff2f2] dark:bg-[#1D0002] lg:mb-0 lg:rounded-xl">
                    <div class="px-6 py-12 md:px-10 md:py-20 lg:px-16 lg:py-24">
                        <div class="max-w-[700px]">
                            <div class="inline-block rounded-lg bg-muted px-3 py-1 text-sm mb-4">
                                Simplifiez la gestion de vos notes de frais
                            </div>
                            <h1 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl lg:text-6xl/none mb-4">
                                Gérez vos notes de frais en toute simplicité
                            </h1>
                            <p class="text-muted-foreground md:text-xl mb-8">
                                SnapFrais vous permet de créer des formulaires personnalisés, de définir des exigences spécifiques et d'appliquer des taux de remboursement variables selon les dates.
                            </p>
                            <!-- <div class="flex flex-col sm:flex-row gap-4">
                                <Link
                                    href="/register"
                                    class="inline-block rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                                >
                                    Commencer gratuitement
                                </Link>
                                <Link
                                    href="#features"
                                    class="inline-block rounded-sm border border-[#19140035] px-5 py-2.5 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                >
                                    Découvrir les fonctionnalités
                                </Link>
                            </div> -->
                        </div>
                    </div>
                    <div class="absolute -bottom-1 -right-1 -z-10 h-[300px] w-[300px] rounded-full bg-[#f53003]/10 blur-3xl dark:bg-[#FF4433]/10 lg:h-[500px] lg:w-[500px]"></div>
                    <div class="absolute -top-1 -left-1 -z-10 h-[200px] w-[200px] rounded-full bg-[#f53003]/10 blur-3xl dark:bg-[#FF4433]/10 lg:h-[300px] lg:w-[300px]"></div>
                </section>

                <!-- Features Section -->
                <section id="features" class="w-full py-12 md:py-24 lg:py-32 bg-white dark:bg-[#161615] rounded-lg my-8 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                    <div class="container px-4 md:px-6">
                        <div class="flex flex-col items-center justify-center space-y-4 text-center">
                            <div class="space-y-2">
                                <h2 class="text-3xl font-bold tracking-tighter sm:text-5xl">Fonctionnalités principales</h2>
                                <p class="max-w-[900px] text-muted-foreground md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                                    Tout ce dont vous avez besoin pour gérer efficacement vos notes de frais
                                </p>
                            </div>
                        </div>
                        <div class="mx-auto grid max-w-5xl items-center gap-6 py-12 lg:grid-cols-3 lg:gap-12">
                            <div class="flex flex-col justify-center space-y-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#fff2f2] dark:bg-[#1D0002]">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-[#f53003] dark:text-[#FF4433]">
                                        <path d="M8 2V5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M16 2V5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.5 9.09H20.5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M11.995 13.7H12.005" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M8.294 13.7H8.304" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M8.294 16.7H8.304" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">Formulaires personnalisés</h3>
                                    <p class="text-muted-foreground">
                                        Créez des formulaires adaptés à vos besoins spécifiques avec des champs personnalisables.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col justify-center space-y-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#fff2f2] dark:bg-[#1D0002]">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-[#f53003] dark:text-[#FF4433]">
                                        <path d="M22 10V15C22 20 20 22 15 22H9C4 22 2 20 2 15V9C2 4 4 2 9 2H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22 10H18C15 10 14 9 14 6V2L22 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7 13H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M7 17H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">Exigences spécifiques</h3>
                                    <p class="text-muted-foreground">
                                        Définissez des exigences précises pour chaque type de dépense (fichiers, texte, justificatifs).
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col justify-center space-y-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#fff2f2] dark:bg-[#1D0002]">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-[#f53003] dark:text-[#FF4433]">
                                        <path d="M9.5 13.75C9.5 14.72 10.25 15.5 11.17 15.5H13.05C13.85 15.5 14.5 14.82 14.5 13.97C14.5 13.06 14.1 12.73 13.51 12.52L10.5 11.47C9.91 11.26 9.51001 10.94 9.51001 10.02C9.51001 9.18 10.16 8.49001 10.96 8.49001H12.84C13.76 8.49001 14.51 9.27001 14.51 10.24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 7.5V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22 12C22 17.52 17.52 22 12 22C6.48 22 2 17.52 2 12C2 6.48 6.48 2 12 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M17 3V7H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M22 2L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">Taux de remboursement variables</h3>
                                    <p class="text-muted-foreground">
                                        Appliquez des taux de remboursement différents selon les dates et les types de dépenses.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- How it works -->
                <section class="w-full py-12 md:py-24 lg:py-32 bg-white dark:bg-[#161615] rounded-lg my-8 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                    <div class="container px-4 md:px-6">
                        <div class="flex flex-col items-center justify-center space-y-4 text-center mb-12">
                            <div class="space-y-2">
                                <h2 class="text-3xl font-bold tracking-tighter sm:text-5xl">Comment ça marche</h2>
                                <p class="max-w-[900px] text-muted-foreground md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                                    Un processus simple en trois étapes
                                </p>
                            </div>
                        </div>
                        <div class="mx-auto max-w-5xl">
                            <ul class="mb-4 flex flex-col lg:mb-6">
                                <li
                                    class="relative flex items-center gap-4 py-4 before:absolute before:bottom-0 before:left-[0.4rem] before:top-1/2 before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                                >
                                    <span class="relative bg-white py-1 dark:bg-[#161615]">
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        >
                                            <span class="text-[#f53003] dark:text-[#FF4433] font-bold">1</span>
                                        </span>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-bold">Créez vos formulaires</h3>
                                        <p class="text-muted-foreground">
                                            Définissez les types de dépenses et les informations nécessaires pour chaque formulaire.
                                        </p>
                                    </div>
                                </li>
                                <li
                                    class="relative flex items-center gap-4 py-4 before:absolute before:bottom-0 before:left-[0.4rem] before:top-0 before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                                >
                                    <span class="relative bg-white py-1 dark:bg-[#161615]">
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        >
                                            <span class="text-[#f53003] dark:text-[#FF4433] font-bold">2</span>
                                        </span>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-bold">Configurez les exigences</h3>
                                        <p class="text-muted-foreground">
                                            Spécifiez les documents requis et les taux de remboursement selon les périodes.
                                        </p>
                                    </div>
                                </li>
                                <li
                                    class="relative flex items-center gap-4 py-4 before:absolute before:bottom-1/2 before:left-[0.4rem] before:top-0 before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                                >
                                    <span class="relative bg-white py-1 dark:bg-[#161615]">
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        >
                                            <span class="text-[#f53003] dark:text-[#FF4433] font-bold">3</span>
                                        </span>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-bold">Gérez vos notes de frais</h3>
                                        <p class="text-muted-foreground">
                                            Suivez, approuvez et analysez les dépenses en temps réel avec des rapports détaillés.
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Benefits -->
                <section class="w-full py-12 md:py-24 lg:py-32 bg-[#fff2f2] dark:bg-[#1D0002] rounded-lg my-8">
                    <div class="container px-4 md:px-6">
                        <div class="grid gap-10 px-10 md:gap-16 lg:grid-cols-2">
                            <div class="space-y-4">
                                <div class="inline-block rounded-lg bg-white dark:bg-[#161615] px-3 py-1 text-sm">Avantages</div>
                                <h2 class="lg:leading-tighter text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl">
                                    Pourquoi choisir SnapFrais ?
                                </h2>
                                <p class="text-muted-foreground md:text-xl">
                                    Notre solution vous fait gagner du temps et de l'argent en simplifiant la gestion des notes de frais.
                                </p>
                                <Link
                                    href="/register"
                                    class="inline-block rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                                >
                                    Essayer gratuitement
                                </Link>
                            </div>
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-start space-x-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-[#161615] text-[#f53003] dark:text-[#FF4433]">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M22 11.1V6.9C22 3.4 20.6 2 17.1 2H12.9C9.4 2 8 3.4 8 6.9V8H11.1C14.6 8 16 9.4 16 12.9V16H17.1C20.6 16 22 14.6 22 11.1Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M16 17.1V12.9C16 9.4 14.6 8 11.1 8H6.9C3.4 8 2 9.4 2 12.9V17.1C2 20.6 3.4 22 6.9 22H11.1C14.6 22 16 20.6 16 17.1Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M6.08 15L8.03 16.95L11.92 13.05" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold">Gain de temps</h3>
                                        <p class="text-muted-foreground">Automatisez le processus de gestion des notes de frais et réduisez le temps administratif.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-[#161615] text-[#f53003] dark:text-[#FF4433]">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.5 13.75C9.5 14.72 10.25 15.5 11.17 15.5H13.05C13.85 15.5 14.5 14.82 14.5 13.97C14.5 13.06 14.1 12.73 13.51 12.52L10.5 11.47C9.91 11.26 9.51001 10.94 9.51001 10.02C9.51001 9.18 10.16 8.49001 10.96 8.49001H12.84C13.76 8.49001 14.51 9.27001 14.51 10.24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12 7.5V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M22 12C22 17.52 17.52 22 12 22C6.48 22 2 17.52 2 12C2 6.48 6.48 2 12 2C17.52 2 22 6.48 22 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold">Économies</h3>
                                        <p class="text-muted-foreground">Contrôlez mieux vos dépenses et évitez les erreurs de remboursement coûteuses.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-[#161615] text-[#f53003] dark:text-[#FF4433]">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.55 21.67C18.84 20.54 22 16.64 22 12C22 6.48 17.56 2 12 2C5.33 2 2 7.56 2 7.56M2 7.56V3M2 7.56H4.01H6.44" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M2 12C2 17.52 6.48 22 12 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="3 3"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold">Flexibilité</h3>
                                        <p class="text-muted-foreground">Adaptez les formulaires et les taux selon vos besoins spécifiques et l'évolution de votre entreprise.</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-[#161615] text-[#f53003] dark:text-[#FF4433]">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8 2V5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M16 2V5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M3.5 9.09H20.5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M19.21 15.77L15.6701 19.31C15.5301 19.45 15.4 19.71 15.37 19.9L15.18 21.25C15.11 21.74 15.45 22.0801 15.94 22.0101L17.29 21.82C17.48 21.79 17.75 21.66 17.88 21.52L21.4201 17.9801C22.0301 17.3701 22.3201 16.6601 21.4201 15.7601C20.5301 14.8701 19.82 15.16 19.21 15.77Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M18.7 16.28C19 17.36 19.84 18.2 20.92 18.5" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5V12" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold">Conformité</h3>
                                        <p class="text-muted-foreground">Assurez-vous que toutes les notes de frais sont conformes aux politiques de l'entreprise et aux exigences légales.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
        <footer class="flex flex-col gap-2 sm:flex-row py-6 w-full shrink-0 items-center px-4 md:px-6 border-t">
            <p class="text-xs text-muted-foreground">
                &copy; {{new Date().getFullYear()}} SnapFrais. Tous droits réservés.
            </p>
            <!-- <nav class="sm:ml-auto flex gap-4 sm:gap-6">
                <Link href="#" class="text-xs hover:underline underline-offset-4">
                    Conditions d'utilisation
                </Link>
                <Link href="#" class="text-xs hover:underline underline-offset-4">
                    Politique de confidentialité
                </Link>
                <Link href="#" class="text-xs hover:underline underline-offset-4">
                    Contact
                </Link>
            </nav> -->
        </footer>
    </div>
</template>
