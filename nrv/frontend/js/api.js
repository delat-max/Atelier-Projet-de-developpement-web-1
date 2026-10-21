window.NRV_API = {
    async request(path) {
        const response = await fetch(path, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            const error = new Error(`Erreur API : ${response.status}`);
            error.status = response.status;
            throw error;
        }

        return response.json();
    },

    getSpectacles(filters = {}) {
        const params = new URLSearchParams();
        if (filters.date) params.set('date', filters.date);
        if (filters.genre) params.set('genre', filters.genre);
        if (filters.tri) params.set('tri', filters.tri);

        const query = params.toString();
        return this.request(query ? `/api/spectacles?${query}` : '/api/spectacles');
    },

    getSoiree(id) {
        return this.request(`/api/soirees/${encodeURIComponent(id)}`);
    },
};
