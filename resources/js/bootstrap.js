import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configuration Axios pour utiliser les cookies XSRF automatiquement
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Intercepteur pour gérer les erreurs de session expirée
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
