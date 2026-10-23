window.NRV_API = {
    async request(path, options = {}) {
        const headers = { Accept: 'application/json' };
        if (options.body !== undefined) headers['Content-Type'] = 'application/json';

        const response = await fetch(path, {
            method: options.method || 'GET',
            headers,
            credentials: 'same-origin',
            body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            const error = new Error(payload.error || `Erreur API : ${response.status}`);
            error.status = response.status;
            throw error;
        }

        return response.json();
    },

    getDates() {
        return this.request('/api/dates');
    },

    getGenres() {
        return this.request('/api/genres');
    },

    getSpectacles(filters = {}) {
        const params = new URLSearchParams();
        if (filters.date) params.set('date', filters.date);
        if (filters.genre) params.set('genre', filters.genre);
        if (filters.tri) params.set('tri', filters.tri);
        if (filters.page) params.set('page', filters.page); 

        const query = params.toString();
        return this.request(query ? `/api/spectacles?${query}` : '/api/spectacles');
    },

    getSoiree(id) {
        return this.request(`/api/soirees/${encodeURIComponent(id)}`);
    },

    // --- Authentification / admin ---
    login(email, password) {
        return this.request('/api/login', { method: 'POST', body: { email, password } });
    },

    register(data) {
        return this.request('/api/register', { method: 'POST', body: data });
    },

    logout() {
        return this.request('/api/logout', { method: 'POST' });
    },

    getMe() {
        return this.request('/api/me');
    },

    // Les futures routes du backoffice passent par ce helper (cookie de session envoyé automatiquement).
    admin(path, options = {}) {
        return this.request(`/api/admin${path}`, options);
    },
};