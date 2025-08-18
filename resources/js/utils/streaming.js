// Fonction simplifiée pour les requêtes de streaming
// Utilise maintenant la configuration Axios XSRF automatique

export const makeStreamingRequest = async (url, options = {}) => {
    try {
        // Utiliser Axios avec la configuration XSRF automatique
        const response = await window.axios({
            url: url,
            method: options.method || 'POST',
            data: options.body ? JSON.parse(options.body) : undefined,
            headers: {
                'Accept': 'application/json',
                ...options.headers
            },
            responseType: 'stream' // Pour le streaming
        });

        return response;
    } catch (error) {
        // Si erreur 419, c'est que la session a expiré
        if (error.response?.status === 419) {
            console.log('Session expirée, redirection vers login...');
            window.location.href = '/login';
            return;
        }
        throw error;
    }
};

// Pour les requêtes fetch directes (si nécessaire)
export const makeStreamingFetch = async (url, options = {}) => {
    // Récupérer le cookie XSRF-TOKEN si disponible
    const xsrfToken = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    const headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers
    };

    // Ajouter le token XSRF si disponible
    if (xsrfToken) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken);
    }

    const response = await fetch(url, {
        ...options,
        headers
    });

    // Si erreur 419, c'est que la session a expiré
    if (response.status === 419) {
        console.log('Session expirée, redirection vers login...');
        window.location.href = '/login';
        return;
    }

    return response;
};

// Exemple d'utilisation pour le streaming :
/*
const response = await makeStreamingFetch('/conversations/123/stream', {
    method: 'POST',
    body: JSON.stringify({ message: 'Hello' }),
    headers: {
        'Content-Type': 'application/json'
    }
});

const reader = response.body.getReader();
// ... traitement du streaming
*/
