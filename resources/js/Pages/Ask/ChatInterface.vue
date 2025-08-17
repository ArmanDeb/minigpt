<script setup>
import { ref, computed, nextTick, watch, onMounted } from 'vue';
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';
import { useStream } from '@laravel/stream-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConversationList from '@/Components/ConversationList.vue';
import MessageList from '@/Components/MessageList.vue';
import MessageForm from '@/Components/MessageForm.vue';
import ModelSelector from '@/Components/ModelSelector.vue';
import axios from 'axios';

const props = defineProps({
    models: {
        type: Array,
        required: true
    },
    selectedModel: {
        type: String,
        default: ''
    },
    conversations: {
        type: Array,
        default: () => []
    },
    conversation: {
        type: Object,
        default: null
    },
    mode: {
        type: String,
        default: 'create' // 'create', 'show', 'list'
    },
    initialMessage: {
        type: String,
        default: ''
    }
});

const page = usePage();
const form = useForm({
    message: '',
    model: props.selectedModel || (props.models.length > 0 ? props.models[0].id : '')
});

// Initialisation des variables d'état
const currentConversation = ref(props.conversation || null);
const messages = ref(props.conversation?.messages || []);
const isLoading = ref(false);
const error = ref(null);
const messageListRef = ref(null);
const messagesContainer = ref(null);
const localConversations = ref([...props.conversations]);
const shouldResetForm = ref(false);

// Variables pour la sélection multiple
const selectedConversations = ref(new Set());
const isMultiSelectMode = ref(false);

// Variables pour le tri
const sortBy = ref('recent'); // 'recent', 'favorites'

// Fonction pour nettoyer l'URL des paramètres de requête
const cleanUrlParameters = () => {
    const url = new URL(window.location.href);
    url.searchParams.delete('initial_message');
    window.history.replaceState({}, document.title, url.toString());
};

// Fonction pour réinitialiser le formulaire
const resetMessageForm = () => {
    shouldResetForm.value = true;
};

// Fonction appelée quand la réinitialisation est terminée
const onResetComplete = () => {
    shouldResetForm.value = false;
};

// Fonction pour toggle les favoris
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
                // Force re-render of the conversations list
                localConversations.value = [...localConversations.value];
            }
        }
    );
};

// Fonction pour faire défiler vers le bas
const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    } else {
        // Si la référence n'est pas encore disponible, réessayer après un court délai
        setTimeout(() => {
            if (messagesContainer.value) {
                messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
            }
        }, 100);
    }
};

// Liste combinée des conversations (existantes + nouvelle)
const allConversations = computed(() => {
    if (currentConversation.value && !localConversations.value.some(conv => conv.id === currentConversation.value.id)) {
        return [currentConversation.value, ...localConversations.value];
    }
    return localConversations.value;
});

// Conversations triées selon le critère sélectionné
const sortedConversations = computed(() => {
    const conversations = [...props.conversations];

    if (sortBy.value === 'favorites') {
        // Trier par favoris d'abord, puis par dernière activité
        return conversations.sort((a, b) => {
            if (a.is_favorite && !b.is_favorite) return -1;
            if (!a.is_favorite && b.is_favorite) return 1;
            // Si même statut de favori, trier par dernière activité
            return new Date(b.last_activity_at || b.updated_at) - new Date(a.last_activity_at || a.updated_at);
        });
    }

    // Tri par défaut : récent
    return conversations.sort((a, b) => {
        return new Date(b.last_activity_at || b.updated_at) - new Date(a.last_activity_at || a.updated_at);
    });
});

const pageTitle = computed(() => {
    if (props.mode === 'list') return 'Conversations';
    if (props.mode === 'show' && currentConversation.value) {
        // S'assurer que le titre est réactif
        return currentConversation.value.title || 'Nouvelle conversation';
    }
    return 'Nouvelle conversation';
});

const displayedResponse = computed(() => {
    return page.props.flash.message || '';
});

const getSelectedModelName = computed(() => {
    // Utiliser le modèle du formulaire qui est maintenant synchronisé
    const modelId = form.model || (props.models.length > 0 ? props.models[0].id : '');

    // Trouver le nom du modèle à partir de son ID
    const model = props.models.find(m => m.id === modelId);
    return model ? model.name : modelId;
});

// URL dynamique pour le stream
const getStreamUrl = computed(() => {
    if (!currentConversation.value?.id) return null;
    return `/conversations/${currentConversation.value.id}/stream`;
});

// Synchroniser le modèle entre l'en-tête et le sélecteur
watch(() => currentConversation.value, (newConversation) => {
    if (newConversation && newConversation.model) {
        // Mettre à jour le modèle du formulaire avec celui de la conversation sauvegardée
        form.model = newConversation.model;
    }
}, { immediate: true });

// Mise à jour des messages quand la conversation change
watch(() => props.conversation, (newConversation) => {
    if (newConversation) {
        currentConversation.value = newConversation;
        messages.value = newConversation.messages || [];
        nextTick(() => scrollToBottom());
    }
});

// Faire défiler vers le bas quand les messages changent
watch(() => messages.value.length, () => {
    nextTick(() => scrollToBottom());
});

// Faire défiler vers le bas quand le contenu des messages change (streaming)
watch(() => messages.value, () => {
    nextTick(() => scrollToBottom());
}, { deep: true });

// Faire défiler vers le bas quand la conversation change
watch(() => currentConversation.value, () => {
    nextTick(() => scrollToBottom());
});

// Faire défiler vers le bas quand les messages d'une conversation spécifique changent
watch(() => props.conversation?.messages, (newMessages) => {
    if (newMessages && newMessages.length > 0) {
        messages.value = newMessages;
        nextTick(() => scrollToBottom());
    }
}, { deep: true });

// Recréer le stream quand l'URL change
watch(() => getStreamUrl.value, (newUrl, oldUrl) => {
    if (newUrl !== oldUrl) {
        console.log('Stream URL changed:', newUrl);
        // Le stream sera recréé automatiquement lors du prochain envoi de message
    }
});

// Vérifier s'il y a un message initial à envoyer
onMounted(() => {
    // Faire défiler vers le bas quand la page est chargée
    nextTick(() => scrollToBottom());

    if (props.initialMessage && currentConversation.value) {
        // Vérifier si la conversation a déjà des messages (pour éviter de renvoyer le message initial lors d'un rafraîchissement)
        const hasExistingMessages = messages.value.length > 0;

        // N'envoyer le message initial que si la conversation est vide
        if (!hasExistingMessages) {
            // Envoyer le message initial après le chargement de la page
            setTimeout(async () => {
                try {
                    await streamMessage(currentConversation.value.id, props.initialMessage);

                    // Forcer la mise à jour du titre immédiatement après l'envoi
                    await nextTick();
                } catch (error) {
                    console.error('Erreur lors de l\'envoi du message initial:', error);
                } finally {
                    // Nettoyer l'URL après avoir envoyé le message
                    cleanUrlParameters();
                }
            }, 500);
        } else {
            // Si la page est rafraîchie avec un message initial dans l'URL, nettoyer l'URL
            cleanUrlParameters();
        }
    }
});

// Mettre à jour le modèle de la conversation lorsque l'utilisateur en change
const changeModel = (modelId) => {
    form.model = modelId;
    // Ne plus sauvegarder immédiatement le modèle côté serveur
    // Il sera sauvegardé uniquement lors de l'envoi d'un message
};

// Configuration du Stream avec useStream
let streamInstance = null;

// Fonction pour créer un EventSource personnalisé avec CSRF token
const createEventSourceWithCSRF = (url) => {
    // Pour EventSource, on ne peut pas ajouter de headers directement
    // On ajoute donc le token dans l'URL
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const separator = url.includes('?') ? '&' : '?';
    const urlWithToken = `${url}${separator}csrf_token=${csrfToken}`;

    return new EventSource(urlWithToken);
};

const setupStream = () => {
    if (!getStreamUrl.value) return;

    // Recréer le stream avec la nouvelle URL
    streamInstance = useStream(
        getStreamUrl.value, // Utiliser directement la valeur de l'URL
        {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            onData: (data) => {
                // Concaténer chaque chunk au dernier message
                const lastMessage = messages.value[messages.value.length - 1];
                if (lastMessage && lastMessage.role === 'assistant') {
                    // Récupérer le contenu actuel et ajouter le nouveau chunk
                    const currentContent = lastMessage.content || '';
                    lastMessage.content = currentContent + data;
                    nextTick(() => scrollToBottom());
                }
            },
            onFinish: () => {
                form.reset('message');

                // Mettre à jour la conversation dans la liste locale
                if (currentConversation.value) {
                    const index = localConversations.value.findIndex(conv => conv.id === currentConversation.value.id);
                    if (index !== -1) {
                        currentConversation.value.last_activity_at = new Date().toISOString();
                        localConversations.value[index] = { ...currentConversation.value };

                        // Réordonner les conversations par date d'activité
                        localConversations.value.sort((a, b) =>
                            new Date(b.last_activity_at) - new Date(a.last_activity_at)
                        );
                    }
                }
            },
            onError: (error) => {
                console.error('Erreur streaming:', error);
            }
        }
    );

    return streamInstance;
};

// Initialiser le stream
const { isStreaming, send: sendStream } = setupStream() || { isStreaming: ref(false), send: () => {} };

// Fonction pour envoyer un message via le stream
const streamMessage = async (conversationId, userMessage) => {
    // 1. Ajouter le message utilisateur
    const userMsg = {
        id: Date.now(), // ID temporaire
        conversation_id: conversationId,
        role: 'user',
        content: userMessage,
        created_at: new Date().toISOString(),
    };
    messages.value.push(userMsg);

    // 2. Ajouter un message vide pour l'assistant - sera forcément le dernier
    const assistantMsg = {
        id: Date.now() + 1, // ID temporaire
        conversation_id: conversationId,
        role: 'assistant',
        content: '',
        created_at: new Date().toISOString(),
    };
    messages.value.push(assistantMsg);

    // Réinitialiser le formulaire immédiatement après l'ajout du message
    resetMessageForm();

    scrollToBottom();

            try {
            const makeRequest = async (retryCount = 0) => {
                // Get fresh CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                const response = await fetch(`/conversations/${conversationId}/stream`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'text/event-stream'
                    },
                    body: JSON.stringify({
                        message: userMessage,
                        model: form.model
                    })
                });

                // Handle 419 errors - redirect to login (token is persistent now)
                if (response.status === 419) {
                    console.log('Session expired, redirecting to login...');
                    window.location.href = '/login';
                    return;
                }

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response;
            };

            // Utiliser fetch directement pour plus de contrôle
            const response = await makeRequest();

        // Traiter la réponse en streaming
        const reader = response.body.getReader();
        const decoder = new TextDecoder();

        let done = false;
        while (!done) {
            const { value, done: readerDone } = await reader.read();
            done = readerDone;

            if (done) break;

            // Décoder le chunk et l'ajouter au message de l'assistant
            const chunk = decoder.decode(value);
            const lastMessage = messages.value[messages.value.length - 1];
            if (lastMessage && lastMessage.role === 'assistant') {
                lastMessage.content += chunk;
                nextTick(() => scrollToBottom());
            }
        }

        // Sauvegarder le modèle utilisé pour ce message et mettre à jour la conversation
        if (currentConversation.value) {
            // Sauvegarder le modèle côté serveur maintenant qu'un message a été envoyé
            try {
                await axios.post(route('conversations.update-model', currentConversation.value.id), {
                    model: form.model
                });

                // Mettre à jour le modèle dans l'objet conversation local
                currentConversation.value.model = form.model;
            } catch (error) {
                console.error('Erreur lors de la mise à jour du modèle:', error);
            }

            const index = localConversations.value.findIndex(conv => conv.id === currentConversation.value.id);
            if (index !== -1) {
                currentConversation.value.last_activity_at = new Date().toISOString();
                localConversations.value[index] = { ...currentConversation.value };

                // Réordonner les conversations par date d'activité
                localConversations.value.sort((a, b) =>
                    new Date(b.last_activity_at) - new Date(a.last_activity_at)
                );
            }

            // Vérifier si c'est le premier message de la conversation
            const isFirstMessage = currentConversation.value.title === 'Nouvelle conversation';

            // Si c'est le premier message, mettre à jour le titre de la conversation
            if (isFirstMessage) {
                try {
                    const titleResponse = await axios.post(
                        route('conversations.update-title', currentConversation.value.id),
                        {
                            message: userMessage
                        }
                    );

                    if (titleResponse.data.success) {
                        // Mettre à jour le titre de la conversation localement de manière réactive
                        const newTitle = titleResponse.data.conversation.title;
                        currentConversation.value = {
                            ...currentConversation.value,
                            title: newTitle
                        };

                        // Mettre à jour la conversation dans la liste locale
                        const index = localConversations.value.findIndex(conv => conv.id === currentConversation.value.id);
                        if (index !== -1) {
                            localConversations.value[index] = {
                                ...localConversations.value[index],
                                title: newTitle
                            };
                        }

                        // Forcer la mise à jour du titre dans la sidebar via Inertia
                        router.reload({ only: ['conversations'] });
                    }
                } catch (error) {
                    console.error('Erreur lors de la mise à jour du titre:', error);
                }
            }
        }
    } catch (error) {
        console.error('Erreur streaming:', error);
    }
};

const submitForm = async (formData) => {
    if (isLoading.value || isStreaming.value) return;

    try {
        if (props.mode === 'create' && !currentConversation.value) {
            isLoading.value = true;
            error.value = null;

            // Créer une conversation vide d'abord
            const response = await axios.post(
                route('conversations.store.empty'),
                {
                    model: formData.model
                },
                {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            // Stocker le message initial pour l'envoyer après la redirection
            const initialMessage = formData.message;
            formData.reset('message');

            // Récupérer la conversation vide
            const emptyConversation = response.data.conversation;

            // Rediriger vers la page de conversation
            router.visit(route('conversations.show', emptyConversation.id) + `?initial_message=${encodeURIComponent(initialMessage)}`, {
                onSuccess: () => {
                    isLoading.value = false;
                }
            });
        } else if (currentConversation.value) {
            // Utiliser le streaming pour les messages dans une conversation existante
            await streamMessage(currentConversation.value.id, formData.message);
        } else if (props.mode === 'index') {
            // Mode simple question/réponse
            isLoading.value = true;
            formData.post(route('ask'), {
                preserveScroll: true,
                onSuccess: () => {
                    formData.reset('message');
                    isLoading.value = false;
                }
            });
        }
    } catch (e) {
        error.value = e.response?.data?.error || 'Une erreur est survenue';
        console.error('Erreur:', e);
        isLoading.value = false;
    }
};

const deleteConversation = (conversationId) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) {
        router.delete(route('conversations.destroy', conversationId), {
            data: {
                from_page: props.mode // Envoyer le contexte de la page
            },
            onSuccess: () => {
                // Si on est sur la page de liste, pas besoin de redirection
                if (props.mode === 'list') {
                    // Rester sur la même page, la liste se mettra à jour automatiquement
                    return;
                }

                // Si on supprime la conversation courante depuis sa page de détail
                if (currentConversation.value?.id === conversationId) {
                    router.visit(route('conversations.create'));
                }
            }
        });
    }
};

// Fonctions pour la sélection multiple
const toggleMultiSelectMode = () => {
    isMultiSelectMode.value = !isMultiSelectMode.value;
    if (!isMultiSelectMode.value) {
        selectedConversations.value.clear();
    }
};

const toggleConversationSelection = (conversationId) => {
    if (selectedConversations.value.has(conversationId)) {
        selectedConversations.value.delete(conversationId);
    } else {
        selectedConversations.value.add(conversationId);
    }
};

const selectAllConversations = () => {
    selectedConversations.value.clear();
    props.conversations.forEach(conversation => {
        selectedConversations.value.add(conversation.id);
    });
};

const deselectAllConversations = () => {
    selectedConversations.value.clear();
};

const deleteSelectedConversations = () => {
    const count = selectedConversations.value.size;
    if (count === 0) return;

    const message = count === 1
        ? 'Êtes-vous sûr de vouloir supprimer cette conversation ?'
        : `Êtes-vous sûr de vouloir supprimer ces ${count} conversations ?`;

    if (confirm(message)) {
        const conversationIds = Array.from(selectedConversations.value);

        router.delete(route('conversations.destroy-multiple'), {
            data: { conversation_ids: conversationIds },
            onSuccess: () => {
                selectedConversations.value.clear();
                isMultiSelectMode.value = false;

                // Si la conversation courante est supprimée, rediriger
                if (currentConversation.value && conversationIds.includes(currentConversation.value.id)) {
                    router.visit(route('conversations.create'));
                }
            }
        });
    }
};

const isAllSelected = computed(() => {
    return props.conversations.length > 0 && selectedConversations.value.size === props.conversations.length;
});

const hasSelectedConversations = computed(() => {
    return selectedConversations.value.size > 0;
});
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header v-if="mode !== 'create' && mode !== 'show'">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <!-- Interface nouvelle conversation (style Claude) -->
        <div v-if="mode === 'create' && !currentConversation" class="flex-1 flex flex-col items-center justify-center min-h-screen px-6">
            <!-- Message d'accueil centré -->
            <div class="text-center mb-12 -mt-16">
                <div class="mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-400 via-red-400 to-pink-400 rounded-2xl flex items-center justify-center mx-auto shadow-lg">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                </div>

                <h1 class="text-4xl font-light text-gray-900 dark:text-white mb-4">
                    Bonjour !
                </h1>

                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    Comment puis-je vous aider aujourd'hui ?
                </p>
            </div>

            <!-- Formulaire de message centré -->
            <div class="w-full max-w-3xl">
                <MessageForm
                    :processing-state="isLoading || isStreaming"
                    :initial-message="form.message"
                    :submit-label="'Envoyer'"
                    :processing-label="'Envoi...'"
                    :show-model-selector="true"
                    :models="props.models"
                    :selected-model="form.model || (props.models.length > 0 ? props.models[0].id : '')"
                    :should-reset="shouldResetForm"
                    @reset-complete="onResetComplete"
                    @submit="submitForm"
                    :center-layout="true"
                />
            </div>
        </div>

        <!-- Interface liste des conversations -->
        <div v-else-if="mode === 'list'" class="p-6">
            <div class="max-w-7xl mx-auto">
                <div v-if="conversations.length === 0" class="text-center py-16">
                    <div class="mb-6">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto">
                            <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune conversation</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Commencez votre première conversation avec Askai</p>
                    <Link
                        :href="route('conversations.create')"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-400 via-red-400 to-pink-400 text-white font-medium rounded-xl hover:from-orange-500 hover:via-red-500 hover:to-pink-500 transition-all duration-200 shadow-sm"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Nouvelle conversation
                    </Link>
                </div>

                <div v-else>
                    <!-- En-tête avec sélection multiple -->
                    <div class="mb-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <h2 class="text-2xl font-light text-gray-900 dark:text-white">Mes conversations</h2>

                            <div class="flex items-center space-x-3">
                                <!-- Boutons de tri -->
                                <div class="flex items-center space-x-2 bg-gray-100 dark:bg-gray-700 rounded-xl p-1">
                                    <button
                                        @click="sortBy = 'recent'"
                                        :class="[
                                            'px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200',
                                            sortBy === 'recent'
                                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                                : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'
                                        ]"
                                    >
                                        Récentes
                                    </button>
                                    <button
                                        @click="sortBy = 'favorites'"
                                        :class="[
                                            'px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200 flex items-center',
                                            sortBy === 'favorites'
                                                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                                : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'
                                        ]"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        Favoris
                                    </button>
                                </div>

                                <button
                                    @click="toggleMultiSelectMode"
                                    :class="[
                                        'inline-flex items-center px-4 py-2 border rounded-xl font-medium text-sm transition-all duration-200',
                                        isMultiSelectMode
                                            ? 'bg-orange-500 border-orange-500 text-white hover:bg-orange-600'
                                            : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ isMultiSelectMode ? 'Annuler' : 'Sélectionner' }}
                                </button>

                                <template v-if="isMultiSelectMode && hasSelectedConversations">
                                    <span class="text-sm text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded-lg">
                                        {{ selectedConversations.size }} sélectionnée(s)
                                    </span>
                                    <button
                                        @click="deleteSelectedConversations"
                                        class="inline-flex items-center px-4 py-2 bg-red-500 border border-red-500 text-white rounded-xl hover:bg-red-600 font-medium text-sm transition-all duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Supprimer
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Grille des conversations -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div
                            v-for="conversation in sortedConversations"
                            :key="conversation.id"
                            :class="[
                                'relative group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 transition-all duration-200 hover:shadow-md cursor-pointer',
                                isMultiSelectMode && selectedConversations.has(conversation.id)
                                    ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-600'
                                    : 'hover:border-gray-300 dark:hover:border-gray-600',
                                conversation.is_favorite ? 'border-yellow-200 dark:border-yellow-600/30' : ''
                            ]"
                            @click="isMultiSelectMode ? toggleConversationSelection(conversation.id) : $inertia.visit(route('conversations.show', conversation.id))"
                        >
                            <!-- Checkbox pour la sélection multiple -->
                            <div
                                v-if="isMultiSelectMode"
                                class="absolute top-3 left-3 z-10"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectedConversations.has(conversation.id)"
                                    @click.stop
                                    @change="toggleConversationSelection(conversation.id)"
                                    class="w-4 h-4 text-orange-600 bg-gray-100 border-gray-300 rounded focus:ring-orange-500 focus:ring-2"
                                />
                            </div>

                            <!-- Étoile de favori -->
                            <div
                                v-if="!isMultiSelectMode"
                                class="absolute top-3 right-3"
                            >
                                <button
                                    @click.stop="toggleFavorite(conversation, $event)"
                                    :class="[
                                        'transition-all duration-200',
                                        conversation.is_favorite
                                            ? 'text-yellow-400 hover:text-yellow-500'
                                            : 'text-gray-300 hover:text-yellow-400 dark:text-gray-600 dark:hover:text-yellow-400 opacity-0 group-hover:opacity-100'
                                    ]"
                                    :title="conversation.is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        :fill="conversation.is_favorite ? 'currentColor' : 'none'"
                                        :stroke="conversation.is_favorite ? 'none' : 'currentColor'"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            v-if="conversation.is_favorite"
                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                                        />
                                        <path
                                            v-else
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <!-- Bouton de suppression -->
                            <div
                                v-if="!isMultiSelectMode"
                                class="absolute top-3 right-10"
                            >
                                <button
                                    @click.stop="deleteConversation(conversation.id)"
                                    class="opacity-0 group-hover:opacity-100 text-gray-400 dark:text-gray-500 hover:text-red-500 transition-all duration-200 flex-shrink-0"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Contenu de la conversation -->
                            <div :class="isMultiSelectMode ? 'ml-6' : ''">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2 pr-12">
                                        {{ conversation.title }}
                                    </h3>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ conversation.model }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDistanceToNow(new Date(conversation.updated_at), { locale: fr, addSuffix: true }) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interface conversation active -->
        <div v-else-if="mode === 'show' && currentConversation" class="flex-1 flex flex-col h-full bg-gray-50/30 dark:bg-gray-900/30">
            <!-- Messages -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto px-6 py-8 scroll-smooth">
                <div class="max-w-4xl mx-auto">
                    <MessageList
                        v-if="messages.length > 0"
                        ref="messageListRef"
                        :messages="messages"
                    />
                </div>
            </div>

            <!-- Formulaire de message en bas -->
            <div class="px-6 pb-6 pt-4">
                <div class="max-w-4xl mx-auto">
                    <!-- Erreur -->
                    <div v-if="error || $page.props.flash?.error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                        {{ error || $page.props.flash.error }}
                    </div>

                    <!-- Sélecteur de modèle discret -->
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3 text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Modèle :</span>
                            <ModelSelector
                                :models="models"
                                :selected-model="form.model || (models.length > 0 ? models[0].id : '')"
                                @change="changeModel"
                                :compact="true"
                            />
                        </div>

                        <button
                            @click="deleteConversation(currentConversation.id)"
                            class="p-2 text-gray-400 dark:text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200"
                            title="Supprimer la conversation"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Formulaire de message -->
                    <MessageForm
                        :processing-state="isLoading || isStreaming"
                        :initial-message="form.message"
                        :submit-label="'Envoyer'"
                        :processing-label="'Envoi...'"
                        :show-model-selector="false"
                        :models="props.models"
                        :selected-model="form.model || (props.models.length > 0 ? props.models[0].id : '')"
                        :should-reset="shouldResetForm"
                        @reset-complete="onResetComplete"
                        @submit="submitForm"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

