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
                <button class="button button-primary" type="button" data-soiree-id="${spectacle.id_soiree}">
                    Voir la soirée
                </button>
            </div>
        </article>`;
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

    try {
        const { data } = await window.NRV_API.getSpectacles();
        status.hidden = true;
        spectacleList.innerHTML = data.length
            ? data.map(renderSpectacle).join('')
            : '<p class="empty-state">Aucun spectacle n’est encore programmé.</p>';
    } catch {
        status.textContent = 'La programmation est momentanément indisponible.';
    }

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
        } catch {
            status.hidden = false;
            status.textContent = 'Impossible de charger le détail de cette soirée.';
        } finally {
            button.disabled = false;
        }
    });

    document.querySelector('#back-to-program').addEventListener('click', () => {
        soireeView.hidden = true;
        programView.hidden = false;
    });
});
