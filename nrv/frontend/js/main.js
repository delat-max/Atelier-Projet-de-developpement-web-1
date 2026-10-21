const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
})[character]);

const formatDate = (value) => {
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(date);
};

const safeImageUrl = (value) => {
    if (!value) return null;

    try {
        const url = new URL(value, window.location.origin);
        return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
    } catch {
        return null;
    }
};

const renderSpectacle = (spectacle) => {
    const imageUrl = safeImageUrl(spectacle.image);
    const artists = spectacle.artistes.map((artist) => escapeHtml(artist.nom)).join(', ');

    return `
        <a class="spectacle-card" href="#soiree-view" data-soiree-id="${spectacle.id_soiree}">
            <article class="row">
                <div class="three columns">
                    ${imageUrl
                        ? `<img class="u-max-full-width" width="320" height="180" src="${escapeHtml(imageUrl)}" alt="Affiche de ${escapeHtml(spectacle.titre)}" loading="lazy">`
                        : '<p>Image non renseignée.</p>'}
                </div>
                <div class="nine columns">
                    <p>${escapeHtml(spectacle.genre)}</p>
                    <h2>${escapeHtml(spectacle.titre)}</h2>
                    <p>${artists || 'Artiste à annoncer'}</p>
                    <p>${escapeHtml(formatDate(spectacle.date))} · ${escapeHtml(spectacle.horaire)}</p>
                    <span class="button button-primary">Voir la soirée</span>
                </div>
            </article>
        </a>`;
};

const renderSoiree = (soiree) => {
    const content = document.querySelector('#soiree-content');
    const shows = soiree.spectacles.length
        ? soiree.spectacles.map((show) => `
            <article class="row">
                <time class="two columns">${escapeHtml(show.horaire)}</time>
                <div class="ten columns">
                    <h3>${escapeHtml(show.titre)}</h3>
                    <p>${show.artistes.map((artist) => escapeHtml(artist.nom)).join(', ') || 'Artiste à annoncer'}</p>
                    <p>${escapeHtml(show.genre)}${show.description ? ` · ${escapeHtml(show.description)}` : ''}</p>
                    ${show.video ? `<a href="${escapeHtml(safeImageUrl(show.video) || '#')}" target="_blank" rel="noreferrer">Voir des vidéos</a>` : ''}
                </div>
            </article>`).join('')
        : '<p class="empty-state">Aucun spectacle n’est encore renseigné pour cette soirée.</p>';

    content.innerHTML = `
        <div class="row">
            <div class="twelve columns">
                <p>Détail de la soirée</p>
                <h1>${escapeHtml(soiree.nom)}</h1>
                <p>${escapeHtml(soiree.thematique || 'Programmation du festival')}</p>
            </div>
        </div>
        <div class="row">
            <div class="three columns"><p><strong>Date et début</strong><br>${escapeHtml(formatDate(soiree.date))} · ${escapeHtml(soiree.horaire_debut)}</p></div>
            <div class="three columns"><p><strong>Lieu</strong><br>${escapeHtml(soiree.lieu)}</p></div>
            <div class="three columns"><p><strong>Places restantes</strong><br>${escapeHtml(soiree.places_disponibles)}</p></div>
            <div class="three columns"><p><strong>Tarif indiqué</strong><br>${escapeHtml(Number(soiree.prix).toFixed(2))} €</p></div>
        </div>
        <div class="row"><div class="twelve columns"><h2>Au programme</h2></div></div>
        ${shows}`;
};

document.addEventListener('DOMContentLoaded', async () => {
    const status = document.querySelector('#api-status');
    const programView = document.querySelector('#program-view');
    const soireeView = document.querySelector('#soiree-view');
    const spectacleList = document.querySelector('#spectacle-list');
    const filterDate = document.querySelector('#filter-date');
    const filterGenre = document.querySelector('#filter-genre');

    const fetchFilteredSpectacles = async () => {
        status.hidden = false;
        status.textContent = 'Chargement…';

        const dateVal = filterDate ? filterDate.value.trim() : '';
        const genreVal = filterGenre ? filterGenre.value.trim() : '';

        try {
            const filters = {};
            if (dateVal !== '') filters.date = dateVal;
            if (genreVal !== '') filters.genre = genreVal;

            const { data } = await window.NRV_API.getSpectacles(filters);

            status.hidden = true;
            spectacleList.innerHTML = data.length
                ? data.map(renderSpectacle).join('')
                : '<p class="empty-state">Aucun spectacle ne correspond à ces critères.</p>';
        } catch (err) {
            console.error('Erreur API :', err);
            status.hidden = false;
            status.textContent = 'Erreur lors du chargement des spectacles.';
        }
    };

    const populateFilterOptions = (shows) => {
        if (!filterDate || !filterGenre) return;

        const dates = [...new Set(shows.map((s) => s.date).filter(Boolean))].sort();
        const genres = [...new Set(shows.map((s) => s.genre).filter(Boolean))].sort();

        // Réinitialiser les listes en conservant la première option
        filterDate.innerHTML = '<option value="">Toutes les dates</option>';
        filterGenre.innerHTML = '<option value="">Tous les styles</option>';

        dates.forEach((d) => {
            const opt = document.createElement('option');
            opt.value = d;
            opt.textContent = formatDate(d);
            filterDate.appendChild(opt);
        });

        genres.forEach((g) => {
            const opt = document.createElement('option');
            opt.value = g;
            opt.textContent = g;
            filterGenre.appendChild(opt);
        });
    };

    try {
        const { data } = await window.NRV_API.getSpectacles();
        status.hidden = true;
        populateFilterOptions(data);
        spectacleList.innerHTML = data.length
            ? data.map(renderSpectacle).join('')
            : '<p class="empty-state">Aucun spectacle n’est encore programmé.</p>';
    } catch (err) {
        console.error('Erreur au chargement initial :', err);
        status.hidden = false;
        status.textContent = 'La programmation est momentanément indisponible.';
    }

    if (filterDate) filterDate.addEventListener('change', fetchFilteredSpectacles);
    if (filterGenre) filterGenre.addEventListener('change', fetchFilteredSpectacles);

    spectacleList.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-soiree-id]');
        if (!button) return;

        button.disabled = true;
        try {
            const { data } = await window.NRV_API.getSoiree(button.dataset.soireeId);
            renderSoiree(data);
            programView.hidden = true;
            soireeView.hidden = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) {
            console.error('Erreur soirée :', err);
            status.hidden = false;
            status.textContent = 'Impossible de charger le détail de cette soirée.';
        } finally {
            button.disabled = false;
        }
    });

    document.querySelector('#back-to-program')?.addEventListener('click', () => {
        soireeView.hidden = true;
        programView.hidden = false;
    });
});
