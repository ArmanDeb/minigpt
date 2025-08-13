import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Fonction pour obtenir le token CSRF depuis le meta tag
function getCsrfToken() {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    return token ? token.content : null;
}

// Fonction pour rafraîchir le token CSRF après login/logout
async function refreshCsrfToken() {
    try {
        const response = await fetch('/fresh-csrf-token', {
            method: 'GET',
            credentials: 'same-origin'
        });

        if (response.ok) {
            const data = await response.json();
            const newToken = data.csrf_token;

            // Mettre à jour le meta tag
            const metaTag = document.head.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                metaTag.setAttribute('content', newToken);
            }

            // Mettre à jour axios
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
            window.csrfToken = newToken;

            return newToken;
        }
    } catch (error) {
        console.error('Erreur lors du rafraîchissement du token CSRF:', error);
    }
    return null;
}

// Exposer la fonction globalement
window.refreshCsrfToken = refreshCsrfToken;

// Configuration initiale du token CSRF
const token = getCsrfToken();
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    window.csrfToken = token;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Intercepteur axios simplifié - plus besoin de gérer le refresh du token
window.axios.interceptors.response.use(
    response => response,
    error => {
        // Si on a une erreur 419 (token expiré), rediriger vers la page de connexion
        if (error.response?.status === 419) {
            console.log('Session expired, redirecting to login...');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

// Wrapper fetch global avec token CSRF
const originalFetch = window.fetch;
window.fetch = function(url, options = {}) {
    const currentToken = getCsrfToken();

    if (currentToken) {
        options.headers = options.headers || {};
        if (!options.headers['X-CSRF-TOKEN']) {
            options.headers['X-CSRF-TOKEN'] = currentToken;
        }
    }

    return originalFetch(url, options);
};
