<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useDarkMode } from '@/Composables/useDarkMode';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const { isDark, toggleDarkMode } = useDarkMode();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
        onSuccess: () => {
            // Rechargement pour token CSRF frais après login
            window.location.reload();
        },
    });
};
</script>

<template>
    <Head title="Se connecter - Askai" />

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
        <!-- Logo et Titre -->
        <div class="text-center mb-12">
            <!-- Logo (même que la page d'accueil) -->
            <Link :href="route('home')" class="inline-block mb-8">
                <div class="w-20 h-20 bg-gradient-to-br from-orange-400 via-red-400 to-pink-400 rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
            </Link>

            <!-- Nom de l'application -->
            <h1 class="text-5xl md:text-6xl font-light text-gray-900 dark:text-white mb-2 tracking-tight">
                Askai
            </h1>

            <p class="text-lg text-gray-600 dark:text-gray-300 font-light">
                Se connecter
            </p>
        </div>

        <!-- Message de statut -->
        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 text-center">
            {{ status }}
        </div>

        <!-- Formulaire de connexion -->
        <div class="w-full max-w-sm">
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Champ Email -->
                <div>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="Adresse email"
                        class="w-full px-4 py-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-gray-400 dark:focus:ring-gray-500 focus:border-transparent text-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">
                        {{ form.errors.email }}
                    </div>
                </div>

                <!-- Champ Mot de passe -->
                <div>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Mot de passe"
                        class="w-full px-4 py-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-gray-400 dark:focus:ring-gray-500 focus:border-transparent text-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                        required
                        autocomplete="current-password"
                    />
                    <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                        {{ form.errors.password }}
                    </div>
                </div>

                <!-- Se souvenir de moi -->
                <div class="flex items-center">
                    <input
                        id="remember"
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 text-gray-600 focus:ring-gray-400 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 rounded"
                    />
                    <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-300">
                        Se souvenir de moi
                    </label>
                </div>

                <!-- Bouton de connexion -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full flex items-center justify-center px-6 py-4 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 font-medium rounded-xl hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="form.processing">Connexion...</span>
                    <span v-else>Se connecter</span>
                </button>
            </form>

            <!-- Liens -->
            <div class="text-center space-y-3 mt-6">
                <div v-if="canResetPassword">
                    <Link
                        :href="route('password.request')"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:underline"
                    >
                        Mot de passe oublié ?
                    </Link>
                </div>

                <div>
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
        </div>
    </div>
</template>
