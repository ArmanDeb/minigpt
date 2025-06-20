<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    processingState: {
        type: Boolean,
        default: false
    },
    initialMessage: {
        type: String,
        default: ''
    },
    submitLabel: {
        type: String,
        default: 'Envoyer'
    },
    processingLabel: {
        type: String,
        default: 'Envoi...'
    },
    showModelSelector: {
        type: Boolean,
        default: false
    },
    models: {
        type: Array,
        default: () => []
    },
    selectedModel: {
        type: String,
        default: ''
    },
    shouldReset: {
        type: Boolean,
        default: false
    },
    centerLayout: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['submit', 'resetComplete']);

// État pour gérer le focus du textarea
const isFocused = ref(false);

// Utiliser le modèle sélectionné s'il existe, sinon prendre le premier de la liste
const defaultModel = computed(() => {
    if (props.selectedModel) {
        return props.selectedModel;
    }
    return props.models.length > 0 ? props.models[0].id : '';
});

const form = useForm({
    message: props.initialMessage,
    model: defaultModel.value
});

// Observer la prop shouldReset pour réinitialiser le formulaire
watch(() => props.shouldReset, (newValue) => {
    if (newValue) {
        form.message = '';
        emit('resetComplete');
    }
});

const submitForm = () => {
    emit('submit', form);
};

// Auto-resize du textarea
const textareaRef = ref(null);
const adjustTextareaHeight = () => {
    if (textareaRef.value) {
        textareaRef.value.style.height = 'auto';
        textareaRef.value.style.height = textareaRef.value.scrollHeight + 'px';
    }
};

watch(() => form.message, () => {
    adjustTextareaHeight();
});

// Gestion des touches clavier
const handleKeydown = (event) => {
    if (event.key === 'Enter') {
        if (event.shiftKey) {
            // Shift + Enter = saut de ligne (comportement par défaut)
            return;
        } else {
            // Enter seul = envoyer le message
            event.preventDefault();
            if (form.message.trim() && !props.processingState) {
                submitForm();
            }
        }
    }
};

// Gestion du focus/blur
const handleFocus = () => {
    isFocused.value = true;
};

const handleBlur = () => {
    isFocused.value = false;
};

// Placeholder dynamique
const placeholderText = computed(() => {
    if (isFocused.value || form.message) {
        return '';
    }
    return props.centerLayout ? 'Posez votre question...' : 'Tapez votre message...';
});
</script>

<template>
    <div class="w-full">
        <!-- Sélecteur de modèle moderne (si affiché) -->
        <div v-if="showModelSelector && models.length > 0" class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Modèle d'IA
            </label>
            <div class="relative">
                <select
                    v-model="form.model"
                    class="w-full px-4 py-3 pr-12 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-2xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 appearance-none cursor-pointer shadow-sm hover:border-gray-300 dark:hover:border-gray-500"
                    style="-webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important; background-image: none !important;"
                >
                    <option v-for="model in models" :key="model.id" :value="model.id">
                        {{ model.name }} (Contexte: {{ model.context_length }})
                    </option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Formulaire principal -->
        <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Container du message avec design moderne -->
            <div class="relative">
                <label v-if="!centerLayout" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Votre message
                </label>

                <div class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-2xl shadow-sm hover:border-gray-300 dark:hover:border-gray-500 focus-within:border-orange-500 focus-within:ring-2 focus-within:ring-orange-500/20 transition-all duration-200">
                    <textarea
                        ref="textareaRef"
                        v-model="form.message"
                        :placeholder="placeholderText"
                        class="w-full px-6 py-4 bg-transparent border-0 rounded-2xl resize-none focus:outline-none focus:ring-0 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 min-h-[120px] max-h-[300px]"
                        :class="centerLayout ? 'text-center' : ''"
                        required
                        @input="adjustTextareaHeight"
                        @keydown="handleKeydown"
                        @focus="handleFocus"
                        @blur="handleBlur"
                    ></textarea>

                    <!-- Bouton d'envoi intégré -->
                    <div class="absolute bottom-4 right-4">
                        <button
                            type="submit"
                            :disabled="processingState || !form.message.trim()"
                            class="group relative inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-orange-400 via-red-400 to-pink-400 text-white font-medium rounded-xl hover:from-orange-500 hover:via-red-500 hover:to-pink-500 focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:transform-none disabled:hover:shadow-lg"
                        >
                            <!-- Icône de chargement -->
                            <svg
                                v-if="processingState"
                                class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <!-- Icône d'envoi -->
                            <svg
                                v-else
                                class="w-5 h-5 mr-2 transition-transform duration-200 group-hover:translate-x-0.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>

                            <span class="font-medium">
                                {{ processingState ? processingLabel : submitLabel }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
/* Styles pour le textarea auto-resize */
textarea {
    field-sizing: content;
}

/* Supprimer complètement l'apparence native du select */
select {
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    background-image: none !important;
}

/* Pour Internet Explorer */
select::-ms-expand {
    display: none;
}

/* Animation pour le bouton */
@keyframes pulse-gradient {
    0%, 100% {
        background-size: 100% 100%;
    }
    50% {
        background-size: 110% 110%;
    }
}

.group:hover button {
    animation: pulse-gradient 2s ease-in-out infinite;
}

/* Styles pour les kbd */
kbd {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 0.75rem;
    font-weight: 600;
    color: #374151;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 0.125rem 0.375rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
</style>
