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

    getSpectacles() {
        return this.request('/api/spectacles');
    },

    getSoiree(id) {
        return this.request(`/api/soirees/${encodeURIComponent(id)}`);
    },
};
