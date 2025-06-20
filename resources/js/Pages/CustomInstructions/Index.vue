<template>
    <AppLayout title="Instructions Personnalisées">
        <div class="flex-1 bg-gray-50/30 dark:bg-gray-900/30">
            <div class="max-w-5xl mx-auto px-4 py-8">
                <!-- En-tête moderne -->
                <div class="mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 via-red-400 to-pink-400 rounded-2xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 01.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-light text-gray-900 dark:text-white mb-2">
                                Instructions personnalisées
                            </h1>
                            <p class="text-gray-600 dark:text-gray-300 text-lg">
                                Personnalisez Askai pour qu'il vous comprenne mieux et réponde selon vos préférences
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="updateInstructions" class="space-y-8">
                    <!-- À propos de vous -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/60 dark:border-gray-700/60 overflow-hidden">
                        <div class="p-8">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-500 rounded-xl flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-1">
                                        À propos de vous
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Partagez des informations sur vous, votre profession, vos intérêts et vos objectifs
                                    </p>
                                </div>
                            </div>

                            <div class="relative">
                                <textarea
                                    v-model="form.about_you"
                                    rows="5"
                                    class="w-full px-6 py-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-2xl resize-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Exemple: Je suis un développeur web spécialisé en Laravel et Vue.js. Je travaille sur des applications e-commerce et j'aime apprendre de nouvelles technologies..."
                                ></textarea>
                                <div class="absolute bottom-4 right-4 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 px-2 py-1 rounded-lg">
                                    {{ form.about_you?.length || 0 }}/2000
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comportement de l'assistant -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/60 dark:border-gray-700/60 overflow-hidden">
                        <div class="p-8">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-500 rounded-xl flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-1">
                                        Comportement d'Askai
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Définissez comment vous souhaitez qu'Askai interagisse avec vous
                                    </p>
                                </div>
                            </div>

                            <div class="relative">
                                <textarea
                                    v-model="form.assistant_behavior"
                                    rows="5"
                                    class="w-full px-6 py-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-2xl resize-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                    placeholder="Exemple: Utilise un ton professionnel mais accessible. Fournis des exemples pratiques et du code quand c'est pertinent. Organise tes réponses avec des listes à puces..."
                                ></textarea>
                                <div class="absolute bottom-4 right-4 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 px-2 py-1 rounded-lg">
                                    {{ form.assistant_behavior?.length || 0 }}/2000
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Commandes personnalisées -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/60 dark:border-gray-700/60 overflow-hidden">
                        <div class="p-8">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-500 rounded-xl flex items-center justify-center mr-4">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-1">
                                        Commandes personnalisées
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Créez des raccourcis pour des actions fréquentes (ex: /météo, /citation)
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div v-for="(command, index) in form.custom_commands" :key="index" class="bg-gray-50 dark:bg-gray-700 p-6 rounded-xl border border-gray-200 dark:border-gray-600">
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom</label>
                                            <input
                                                v-model="command.name"
                                                type="text"
                                                class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                                placeholder="Météo"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Commande</label>
                                            <input
                                                v-model="command.command"
                                                type="text"
                                                class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                                placeholder="/météo"
                                            />
                                        </div>
                                        <div class="flex items-end">
                                            <button
                                                type="button"
                                                @click="removeCommand(index)"
                                                class="w-full lg:w-auto px-4 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500/50 transition-all duration-200 font-medium"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                        <textarea
                                            v-model="command.description"
                                            rows="2"
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                            placeholder="Affiche la météo actuelle et les prévisions"
                                        ></textarea>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="addCommand"
                                    class="w-full py-4 px-6 border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded-xl hover:border-orange-300 hover:text-orange-600 hover:bg-orange-50/50 dark:hover:bg-orange-900/20 focus:outline-none focus:ring-2 focus:ring-orange-500/20 transition-all duration-200 font-medium"
                                >
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Ajouter une commande
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200/60 dark:border-gray-700/60 p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <Link
                                :href="route('conversations.create')"
                                class="flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200 font-medium"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Retour au chat
                            </Link>

                            <div class="flex space-x-3">
                                <button
                                    type="button"
                                    @click="resetForm"
                                    class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-all duration-200 font-medium"
                                >
                                    Réinitialiser
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-8 py-3 bg-gradient-to-r from-orange-400 via-red-400 to-pink-400 text-white rounded-xl hover:from-orange-500 hover:via-red-500 hover:to-pink-500 focus:outline-none focus:ring-2 focus:ring-orange-500/50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:transform-none"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span v-if="form.processing">Enregistrement...</span>
                                    <span v-else>Enregistrer</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    customInstructions: Object
})

const form = useForm({
    about_you: props.customInstructions?.about_you || '',
    assistant_behavior: props.customInstructions?.assistant_behavior || '',
    custom_commands: props.customInstructions?.custom_commands || []
})

const addCommand = () => {
    form.custom_commands.push({
        name: '',
        command: '/',
        description: ''
    })
}

const removeCommand = (index) => {
    form.custom_commands.splice(index, 1)
}

const updateInstructions = () => {
    form.put(route('custom-instructions.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optionnel: afficher une notification de succès
        }
    })
}

const resetForm = () => {
    form.reset()
    form.custom_commands = []
}
</script>
