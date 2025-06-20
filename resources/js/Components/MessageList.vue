<script setup>
import { ref, nextTick, watch, onMounted } from 'vue';
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    messages: {
        type: Array,
        default: () => []
    }
});

const formatDate = (dateString) => {
    if (!dateString) return '';

    return formatDistanceToNow(new Date(dateString), {
        addSuffix: true,
        locale: fr
    });
};
</script>

<template>
    <div class="w-full">
        <div v-if="messages.length === 0" class="text-center py-12">
            <div class="mb-4">
                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 dark:text-gray-400 text-lg">Commencez votre conversation</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Tapez votre message ci-dessous pour démarrer</p>
        </div>

        <div v-else class="space-y-8">
            <div
                v-for="message in messages"
                :key="message.id"
                :class="[
                    'flex w-full',
                    message.role === 'user' ? 'justify-end' : 'justify-start'
                ]"
            >
                <div
                    :class="[
                        'max-w-[85%] lg:max-w-[75%]',
                        message.role === 'user' ? 'order-2' : 'order-1'
                    ]"
                >
                    <!-- Avatar et nom -->
                    <div
                        :class="[
                            'flex items-center mb-2',
                            message.role === 'user' ? 'justify-end' : 'justify-start'
                        ]"
                    >
                        <div
                            v-if="message.role === 'assistant'"
                            class="w-8 h-8 bg-gradient-to-br from-orange-400 via-red-400 to-pink-400 rounded-full flex items-center justify-center mr-3"
                        >
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>

                        <div
                            :class="[
                                'flex items-center',
                                message.role === 'user' ? 'flex-row-reverse' : 'flex-row'
                            ]"
                        >
                            <span
                                :class="[
                                    'text-sm font-medium',
                                    message.role === 'user' ? 'text-gray-700 dark:text-gray-300' : 'text-gray-900 dark:text-white'
                                ]"
                            >
                                {{ message.role === 'user' ? 'Vous' : 'Askai' }}
                            </span>
                            <span
                                :class="[
                                    'text-xs text-gray-500 dark:text-gray-400',
                                    message.role === 'user' ? 'mr-2' : 'ml-2'
                                ]"
                            >
                                {{ formatDate(message.created_at) }}
                            </span>
                        </div>

                        <div
                            v-if="message.role === 'user'"
                            class="w-8 h-8 bg-gradient-to-br from-orange-400 via-red-400 to-pink-500 rounded-full flex items-center justify-center ml-3 shadow-md"
                        >
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>

                                        <!-- Contenu du message -->
                    <div
                        :class="[
                            'rounded-2xl px-6 py-4 shadow-lg border',
                            message.role === 'user'
                                ? 'bg-gradient-to-br from-orange-400 via-red-400 to-pink-500 dark:from-orange-500 dark:via-red-500 dark:to-pink-600 border-orange-300 dark:border-orange-600 text-white shadow-orange-200/50 dark:shadow-orange-900/30'
                                : 'bg-white dark:bg-gray-800 border-gray-200/60 dark:border-gray-700/60 text-gray-900 dark:text-white'
                        ]"
                    >
                        <div class="prose prose-sm max-w-none">
                            <div class="whitespace-pre-wrap leading-relaxed">{{ message.content }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Styles pour la prose */
.prose {
    color: inherit;
}

.prose p {
    margin-bottom: 1em;
}

.prose p:last-child {
    margin-bottom: 0;
}

.prose ul, .prose ol {
    margin: 1em 0;
    padding-left: 1.5em;
}

.prose li {
    margin: 0.5em 0;
}

.prose code {
    background-color: rgba(0, 0, 0, 0.1);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.prose pre {
    background-color: rgba(0, 0, 0, 0.05);
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1em 0;
}

.prose blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1rem;
    margin: 1em 0;
    font-style: italic;
    color: #6b7280;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
    font-weight: 600;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.prose h1 { font-size: 1.5em; }
.prose h2 { font-size: 1.25em; }
.prose h3 { font-size: 1.125em; }
</style>
