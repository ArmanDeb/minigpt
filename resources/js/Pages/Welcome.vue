<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useDarkMode } from '@/Composables/useDarkMode';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const { isDark, toggleDarkMode } = useDarkMode();

// Rediriger automatiquement les utilisateurs connectés vers l'application
onMounted(() => {
    if (window.Laravel && window.Laravel.auth && window.Laravel.auth.user) {
        window.location.href = '/conversations/create';
    }
});
</script>

<template>
    <Head title="Askai - Votre Assistant IA Personnel" />
    <div class="min-h-screen bg-white dark:bg-gray-900 flex flex-col justify-center items-center px-4">
        <!-- Bouton Dark Mode en haut à droite -->
        <div class="absolute top-6 right-6">
            <button
                @click="toggleDarkMode"
                class="p-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200"
                :title="isDark ? 'Mode clair' : 'Mode sombre'"
            >
                <svg v-if="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </button>
        </div>
        <!-- Logo et Titre Principal -->
        <div class="text-center mb-16">
            <!-- Logo (inspiré du design Claude) -->
            <div class="mb-8 flex justify-center">
                <div class="w-20 h-20 bg-gradient-to-br from-orange-400 via-red-400 to-pink-400 rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
            </div>

            <!-- Nom de l'application -->
            <h1 class="text-6xl md:text-7xl font-light text-gray-900 dark:text-white mb-6 tracking-tight">
                Askai
            </h1>


        </div>

        <!-- Boutons de connexion -->
        <div class="w-full max-w-sm space-y-4" v-if="canLogin && !$page.props.auth.user">
            <!-- Bouton Se connecter simple -->
            <Link
                :href="route('login')"
                class="w-full flex items-center justify-center px-6 py-4 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-xl hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors duration-200"
            >
                Se connecter
            </Link>

            <!-- Lien d'inscription -->
            <div class="text-center pt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Pas encore de compte ?
                    <Link
                        :href="route('register')"
                        class="text-gray-900 dark:text-white hover:underline font-medium"
                    >
                        Créer un compte
                    </Link>
                </p>
            </div>
        </div>

        <!-- Si l'utilisateur est connecté -->
        <div v-else-if="$page.props.auth.user" class="w-full max-w-sm">
            <Link
                :href="route('conversations.create')"
                class="w-full flex items-center justify-center px-6 py-4 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-xl hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors duration-200"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                Continuer vers Askai
            </Link>
        </div>
    </div>
</template>
