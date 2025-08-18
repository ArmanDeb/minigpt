<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDarkMode } from '@/Composables/useDarkMode';

defineProps({
    title: String,
});

const page = usePage();
const showingMobileMenu = ref(false);
const { isDark, toggleDarkMode } = useDarkMode();

// Récupérer les conversations depuis les props de la page
const conversations = computed(() => {
    return page.props.conversations || [];
});

// Séparer les conversations favoris et récentes
const favoriteConversations = computed(() => {
    return conversations.value.filter(conv => conv.is_favorite);
});

const recentConversations = computed(() => {
    return conversations.value.filter(conv => !conv.is_favorite);
});

const toggleFavorite = (conversation, event) => {
    event.preventDefault();
    event.stopPropagation();

    router.post(
        route('conversations.toggle-favorite', conversation.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Update the conversation in the local data
                conversation.is_favorite = !conversation.is_favorite;
            }
        }
    );
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="flex h-screen bg-white dark:bg-gray-900">
            <!-- Sidebar -->
            <div class="hidden md:flex md:w-64 md:flex-col">
                <div class="flex flex-col flex-grow pt-5 bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
                    <!-- Logo -->
                    <div class="flex items-center flex-shrink-0 px-4 mb-6">
                        <Link :href="route('conversations.create')" class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-orange-400 via-red-400 to-pink-400 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <span class="text-xl font-light text-gray-900 dark:text-white tracking-tight">
                                Askai
                            </span>
                        </Link>
                    </div>

                    <!-- Nouvelle conversation -->
                    <div class="px-4 mb-6">
                        <Link
                            :href="route('conversations.create')"
                            class="flex items-center w-full px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-orange-400 via-red-400 to-pink-400 rounded-xl hover:from-orange-500 hover:via-red-500 hover:to-pink-500 transition-all duration-200 shadow-sm"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nouvelle conversation
                        </Link>
                    </div>

                    <!-- Navigation -->
                    <nav class="px-4 space-y-2 mb-6">
                        <!-- Mes conversations -->
                        <Link
                            :href="route('conversations.index')"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200',
                                route().current('conversations.index')
                                    ? 'text-gray-900 dark:text-white bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600'
                                    : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm'
                            ]"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            Mes conversations
                        </Link>

                        <!-- Instructions personnalisées -->
                        <Link
                            :href="route('custom-instructions.index')"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200',
                                route().current('custom-instructions.index')
                                    ? 'text-gray-900 dark:text-white bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600'
                                    : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm'
                            ]"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 01.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Instructions personnalisées
                        </Link>
                    </nav>

                    <!-- Séparateur -->
                    <div class="px-4 mb-4">
                        <div class="border-t border-gray-200 dark:border-gray-600"></div>
                    </div>

                    <!-- Liste des conversations -->
                    <div class="flex-1 px-4 pb-4">
                        <!-- Section Favoris -->
                        <div v-if="favoriteConversations.length > 0" class="mb-4">
                            <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 px-2 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                Favoris
                            </h3>
                            <div class="space-y-1">
                                <div
                                    v-for="conversation in favoriteConversations.slice(0, 5)"
                                    :key="'fav-' + conversation.id"
                                    class="group relative"
                                >
                                    <Link
                                        :href="route('conversations.show', conversation.id)"
                                        :class="[
                                            'flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 pr-8',
                                            route().current('conversations.show', { conversation: conversation.id })
                                                ? 'text-gray-900 dark:text-white bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600'
                                                : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm'
                                        ]"
                                    >
                                        <svg class="w-4 h-4 mr-3 flex-shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        <span class="truncate">{{ conversation.title }}</span>
                                    </Link>
                                    <button
                                        @click="toggleFavorite(conversation, $event)"
                                        class="absolute top-2 right-2 text-yellow-400 hover:text-yellow-500 transition-colors opacity-70 hover:opacity-100"
                                        title="Retirer des favoris"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Section Récentes -->
                        <div v-if="recentConversations.length > 0">
                            <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 px-2">
                                Récentes
                            </h3>
                            <div class="space-y-1">
                                <div
                                    v-for="conversation in recentConversations.slice(0, favoriteConversations.length > 0 ? 5 : 10)"
                                    :key="'recent-' + conversation.id"
                                    class="group relative"
                                >
                                <Link
                                    :href="route('conversations.show', conversation.id)"
                                    :class="[
                                            'flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 pr-8',
                                        route().current('conversations.show', { conversation: conversation.id })
                                            ? 'text-gray-900 dark:text-white bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600'
                                            : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm'
                                    ]"
                                >
                                    <svg class="w-4 h-4 mr-3 flex-shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                    <span class="truncate">{{ conversation.title }}</span>
                                </Link>
                                    <button
                                        @click="toggleFavorite(conversation, $event)"
                                        class="absolute top-2 right-2 text-gray-300 hover:text-yellow-400 dark:text-gray-600 dark:hover:text-yellow-400 transition-colors opacity-0 group-hover:opacity-100"
                                        title="Ajouter aux favoris"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu utilisateur en bas -->
                    <div class="p-4 border-t border-gray-200 dark:border-gray-600">
                        <div class="relative">
                            <button
                                @click="showingMobileMenu = !showingMobileMenu"
                                class="flex items-center w-full px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 rounded-xl transition-all duration-200"
                            >
                                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <div class="flex-1 text-left">
                                    <div class="font-medium">{{ $page.props.auth.user.name }}</div>
                                </div>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"></path>
                                </svg>
                            </button>

                            <!-- Menu dropdown utilisateur -->
                            <div
                                v-show="showingMobileMenu"
                                @click.away="showingMobileMenu = false"
                                class="absolute bottom-full left-0 right-0 mb-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-2"
                            >
                                <Link
                                    :href="route('profile.show')"
                                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                                >
                                    Profil
                                </Link>

                                <!-- Toggle Dark Mode -->
                                <button
                                    @click="toggleDarkMode"
                                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                                >
                                    <svg v-if="!isDark" class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                    </svg>
                                    <svg v-else class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    {{ isDark ? 'Mode clair' : 'Mode sombre' }}
                                </button>

                                <div class="border-t border-gray-100 dark:border-gray-600 my-1"></div>
                                <button
                                    @click="logout"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                                >
                                    Se déconnecter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Header mobile -->
                <div class="md:hidden bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <Link :href="route('conversations.create')" class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-orange-400 via-red-400 to-pink-400 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <span class="text-xl font-light text-gray-900 dark:text-white tracking-tight">
                                Askai
                            </span>
                        </Link>

                        <button
                            @click="showingMobileMenu = !showingMobileMenu"
                            class="p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
                        >
                            <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{'hidden': showingMobileMenu, 'inline-flex': !showingMobileMenu }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{'hidden': !showingMobileMenu, 'inline-flex': showingMobileMenu }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Menu mobile -->
                <div
                    v-show="showingMobileMenu"
                    class="md:hidden bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700"
                >
                    <div class="px-4 pt-2 pb-3 space-y-2">
                        <!-- Nouvelle conversation mobile -->
                        <Link
                            :href="route('conversations.create')"
                            class="flex items-center w-full px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-orange-400 via-red-400 to-pink-400 rounded-xl"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nouvelle conversation
                        </Link>

                        <Link
                            :href="route('conversations.index')"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200',
                                route().current('conversations.index')
                                    ? 'text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800'
                                    : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800'
                            ]"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            Mes conversations
                        </Link>

                        <Link
                            :href="route('custom-instructions.index')"
                            :class="[
                                'flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200',
                                route().current('custom-instructions.index')
                                    ? 'text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800'
                                    : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800'
                            ]"
                        >
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 01.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Instructions personnalisées
                        </Link>

                        <!-- Conversations récentes mobile -->
                        <div v-if="conversations.length > 0" class="pt-4">
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 px-3">
                                    Récents
                                </h3>
                                <div class="space-y-1">
                                    <Link
                                        v-for="conversation in conversations.slice(0, 5)"
                                        :key="conversation.id"
                                        :href="route('conversations.show', conversation.id)"
                                        class="flex items-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded-xl transition-all duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-3 flex-shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        <span class="truncate">{{ conversation.title }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu utilisateur mobile -->
                    <div class="px-4 pt-4 pb-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div class="ml-3">
                                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <Link
                                :href="route('profile.show')"
                                class="block px-3 py-2 text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md transition-colors duration-200"
                            >
                                Profil
                            </Link>

                            <!-- Toggle Dark Mode Mobile -->
                            <button
                                @click="toggleDarkMode"
                                class="flex items-center w-full px-3 py-2 text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md transition-colors duration-200"
                            >
                                <svg v-if="!isDark" class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                {{ isDark ? 'Mode clair' : 'Mode sombre' }}
                            </button>

                            <button
                                @click="logout"
                                class="block w-full text-left px-3 py-2 text-base font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md transition-colors duration-200"
                            >
                                Se déconnecter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contenu principal -->
                <main class="flex-1 overflow-y-auto bg-white dark:bg-gray-900">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
