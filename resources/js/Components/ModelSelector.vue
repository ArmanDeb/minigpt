<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    models: {
        type: Array,
        required: true
    },
    selectedModel: {
        type: String,
        default: ''
    },
    compact: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['change']);
const isModelSelectorOpen = ref(false);

const toggleModelSelector = () => {
    isModelSelectorOpen.value = !isModelSelectorOpen.value;
};

const changeModel = (modelId) => {
    emit('change', modelId);
    isModelSelectorOpen.value = false;
};

// Calculer le modèle sélectionné avec une valeur par défaut
const currentModel = computed(() => {
    // Si le modèle sélectionné est valide, l'utiliser
    if (props.selectedModel) {
        return props.selectedModel;
    }

    // Sinon, utiliser le premier modèle de la liste
    return props.models.length > 0 ? props.models[0].id : '';
});

// Obtenir le nom du modèle sélectionné
const selectedModelName = computed(() => {
    if (!props.selectedModel && props.models.length > 0) {
        return props.models[0].name;
    }

    const model = props.models.find(m => m.id === props.selectedModel);
    return model ? model.name : props.selectedModel;
});
</script>

<template>
    <div class="relative">
        <button
            @click="toggleModelSelector"
            :class="[
                'flex items-center space-x-2 transition-colors duration-200 rounded-lg px-3 py-1.5',
                compact
                    ? 'hover:bg-gray-100 dark:hover:bg-gray-700 text-sm'
                    : 'hover:text-indigo-600'
            ]"
        >
            <span v-if="!compact">Modèle: </span>
            <span class="font-medium">{{ selectedModelName }}</span>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                :class="[
                    'transition-transform duration-200',
                    compact ? 'h-3 w-3' : 'h-4 w-4',
                    isModelSelectorOpen ? 'rotate-180' : ''
                ]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Model Selector Dropdown -->
        <div
            v-if="isModelSelectorOpen"
            :class="[
                'absolute top-full mt-1 bg-white dark:bg-gray-800 shadow-lg rounded-xl overflow-hidden z-50 border border-gray-200 dark:border-gray-600',
                compact ? 'left-0 w-72' : 'left-0 w-64'
            ]"
        >
            <div class="max-h-60 overflow-y-auto">
                <button
                    v-for="model in models"
                    :key="model.id"
                    @click="changeModel(model.id)"
                    :class="[
                        'w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 text-sm',
                        model.id === currentModel ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300' : 'text-gray-700 dark:text-gray-300'
                    ]"
                >
                    <div class="font-medium">{{ model.name }}</div>
                    <div v-if="compact" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Contexte: {{ model.context_length }}
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
