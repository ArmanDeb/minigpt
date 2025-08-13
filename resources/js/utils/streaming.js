// Fonction ultra-simplifiée pour les requêtes de streaming
// Plus besoin de retry ou de refresh du token !

export const makeStreamingRequest = async (url, options = {}) => {
    // Obtenir le token CSRF unique
    const token = document.head.querySelector('meta[name="csrf-token"]')?.content;

    if (!token) {
        throw new Error('Token CSRF non trouvé');
    }

    // Configurer les headers avec le token unique
    const headers = {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
        ...options.headers
    };

    // Faire la requête (sans retry, le token est persistant !)
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
const response = await makeStreamingRequest('/conversations/123/stream', {
    method: 'POST',
    body: JSON.stringify({ message: 'Hello' }),
    headers: {
        'Content-Type': 'application/json'
    }
});

const reader = response.body.getReader();
// ... traitement du streaming
*/
