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
        if (filters.date) params.append('date', filters.date);
        if (filters.genre) params.append('genre', filters.genre);

        const queryString = params.toString();
        const url = queryString ? `/api/spectacles?${queryString}` : '/api/spectacles';

        return this.request(url);
    },

    getSoiree(id) {
        return this.request(`/api/soirees/${encodeURIComponent(id)}`);
    },
};
