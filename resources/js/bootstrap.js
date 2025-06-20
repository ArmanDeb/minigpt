import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Function to get fresh CSRF token
function getCsrfToken() {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    return token ? token.content : null;
}

// Function to refresh CSRF token
async function refreshCsrfToken() {
    try {
        // First try to get a fresh CSRF token from our endpoint
        const response = await fetch('/csrf-token', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            const newToken = data.csrf_token;

            if (newToken) {
                // Update the meta tag
                const metaTag = document.head.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', newToken);
                }

                // Update axios defaults and global token
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
                window.csrfToken = newToken;
                return newToken;
            }
        }

        // Fallback: try sanctum cookie method
        const sanctumResponse = await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin'
        });

        if (sanctumResponse.ok) {
            // Wait a bit for the cookie to be set
            await new Promise(resolve => setTimeout(resolve, 100));

            // Update the meta tag
            const newToken = getCsrfToken();
            if (newToken) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
                window.csrfToken = newToken;
                return newToken;
            }
        }
    } catch (error) {
        console.error('Failed to refresh CSRF token:', error);
    }
    return null;
}

// Initialize CSRF token
const token = getCsrfToken();
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    window.csrfToken = token;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Add axios interceptor to handle 419 errors
window.axios.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;

        // If it's a 419 error and we haven't already retried
        if (error.response?.status === 419 && !originalRequest._retry) {
            originalRequest._retry = true;

            console.log('CSRF token expired, refreshing...');

            // Try to refresh the CSRF token
            const newToken = await refreshCsrfToken();

            if (newToken) {
                // Update the original request with the new token
                originalRequest.headers['X-CSRF-TOKEN'] = newToken;

                // Retry the original request
                return window.axios(originalRequest);
            } else {
                // If we can't refresh the token, redirect to login
                console.error('Unable to refresh CSRF token, redirecting to login');
                window.location.href = '/login';
                return Promise.reject(error);
            }
        }

        return Promise.reject(error);
    }
);

// Add a global fetch wrapper that includes CSRF token
const originalFetch = window.fetch;
window.fetch = function(url, options = {}) {
    // Get fresh CSRF token for each request
    const currentToken = getCsrfToken();

    if (currentToken) {
        options.headers = options.headers || {};
        if (!options.headers['X-CSRF-TOKEN']) {
            options.headers['X-CSRF-TOKEN'] = currentToken;
        }
    }

    return originalFetch(url, options);
};

// Refresh CSRF token when the page becomes visible (helps with browser back/forward)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Update token when page becomes visible
        const currentToken = getCsrfToken();
        if (currentToken && currentToken !== window.csrfToken) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = currentToken;
            window.csrfToken = currentToken;
        }
    }
});

// Refresh token on window focus
window.addEventListener('focus', function() {
    const currentToken = getCsrfToken();
    if (currentToken && currentToken !== window.csrfToken) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = currentToken;
        window.csrfToken = currentToken;
    }
});
