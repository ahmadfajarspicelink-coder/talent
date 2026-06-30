import './bootstrap';

// Livewire v4 bundles Alpine — no separate import needed.
// Alpine is started automatically by @livewireScripts.

// Handle expired session: redirect to login when Livewire returns 401.
document.addEventListener('livewire:morph-failed', () => {
    window.location.href = '/login';
});

// Livewire v3/4 fires this event when a request fails with 401.
document.addEventListener('status', (e) => {
    if (e.detail?.statusCode === 401) {
        window.location.href = '/login';
    }
});

// Catch-all: any fetch/XHR that returns 401 with JSON triggers redirect.
const origFetch = window.fetch;
window.fetch = async function (...args) {
    const resp = await origFetch.apply(this, args);
    if (resp.status === 401 && window.location.pathname !== '/login') {
        window.location.href = '/login';
    }
    return resp;
};
