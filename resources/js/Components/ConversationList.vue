<script setup>
import { Link, router } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => []
    },
    currentConversationId: {
        type: Number,
        default: null
    }
});

const getLastActivity = (conversation) => {
    if (!conversation.last_activity_at) return '';

    return formatDistanceToNow(new Date(conversation.last_activity_at), {
        addSuffix: true,
        locale: fr
    });
};

const getPreview = (conversation) => {
    if (conversation.messages && conversation.messages.length > 0) {
        // Trouver le dernier message
        const lastMessage = [...conversation.messages].sort((a, b) =>
            new Date(b.created_at) - new Date(a.created_at)
        )[0];

        if (lastMessage) {
            const content = lastMessage.content || '';
            return content.length > 50
                ? content.substring(0, 50) + '...'
                : content;
        }
    }
    return 'Aucun message';
};

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

// Séparer les conversations favoris et récentes
const favoriteConversations = props.conversations.filter(conv => conv.is_favorite);
const recentConversations = props.conversations.filter(conv => !conv.is_favorite);
</script>

<template>
    <div class="w-full md:w-64 bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden">
        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div class="flex justify-between items-center">
                <h3 class="font-medium text-gray-700 dark:text-gray-300">Conversations</h3>
                <Link
                    :href="route('conversations.create')"
                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </Link>
            </div>
        </div>

        <div class="overflow-y-auto h-[500px]">
            <!-- Message quand il n'y a aucune conversation -->
            <div v-if="conversations.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                Aucune conversation
            </div>

            <!-- Section Favoris -->
            <div v-if="favoriteConversations.length > 0">
                <div class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Favoris
                    </h4>
            </div>
            <Link
                    v-for="conversation in favoriteConversations"
                    :key="'fav-' + conversation.id"
                :href="route('conversations.show', conversation.id)"
                    class="block p-4 border-b border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition relative"
                    :class="{ 'bg-indigo-50 dark:bg-indigo-900/20': currentConversationId === conversation.id }"
            >
                <div class="flex justify-between items-start">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 text-sm truncate pr-6">{{ conversation.title }}</h4>
                        <button
                            @click="toggleFavorite(conversation, $event)"
                            class="absolute top-4 right-4 text-yellow-400 hover:text-yellow-500 transition-colors"
                            title="Retirer des favoris"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ getPreview(conversation) }}</p>
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        {{ getLastActivity(conversation) }}
                    </div>
                </Link>
            </div>

            <!-- Section Récentes -->
            <div v-if="recentConversations.length > 0">
                <div v-if="favoriteConversations.length > 0" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Récentes</h4>
                </div>
                <Link
                    v-for="conversation in recentConversations"
                    :key="'recent-' + conversation.id"
                    :href="route('conversations.show', conversation.id)"
                    class="block p-4 border-b border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition relative"
                    :class="{ 'bg-indigo-50 dark:bg-indigo-900/20': currentConversationId === conversation.id }"
                >
                    <div class="flex justify-between items-start">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 text-sm truncate pr-6">{{ conversation.title }}</h4>
                        <button
                            @click="toggleFavorite(conversation, $event)"
                            class="absolute top-4 right-4 text-gray-300 hover:text-yellow-400 dark:text-gray-600 dark:hover:text-yellow-400 transition-colors"
                            title="Ajouter aux favoris"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ getPreview(conversation) }}</p>
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    {{ getLastActivity(conversation) }}
                </div>
            </Link>
            </div>
        </div>
    </div>
</template>
