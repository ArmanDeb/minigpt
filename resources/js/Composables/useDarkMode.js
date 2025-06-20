import { ref, watch } from 'vue'

const isDark = ref(false)
let isInitialized = false

// Initialiser le mode sombre immédiatement
const initDarkMode = () => {
    if (isInitialized) return

    // Vérifier d'abord localStorage
    const stored = localStorage.getItem('darkMode')
    if (stored !== null) {
        isDark.value = JSON.parse(stored)
    } else {
        // Sinon, utiliser la préférence système
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }

    // Appliquer la classe au document
    updateDarkClass()

    // Écouter les changements de préférence système
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    const handleSystemChange = (e) => {
        // Ne changer que si l'utilisateur n'a pas de préférence sauvegardée
        if (localStorage.getItem('darkMode') === null) {
            isDark.value = e.matches
        }
    }
    mediaQuery.addEventListener('change', handleSystemChange)

    isInitialized = true
}

// Mettre à jour la classe 'dark' sur le document
const updateDarkClass = () => {
    if (isDark.value) {
        document.documentElement.classList.add('dark')
    } else {
        document.documentElement.classList.remove('dark')
    }
}

// Basculer le mode sombre
const toggleDarkMode = () => {
    isDark.value = !isDark.value
}

// Sauvegarder dans localStorage quand le mode change
watch(isDark, (newValue) => {
    localStorage.setItem('darkMode', JSON.stringify(newValue))
    updateDarkClass()
})

// Initialiser automatiquement dès que le DOM est prêt
if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDarkMode)
    } else {
        initDarkMode()
    }
}

export function useDarkMode() {
    // S'assurer que l'initialisation est faite
    initDarkMode()

    return {
        isDark,
        toggleDarkMode,
        initDarkMode
    }
}
