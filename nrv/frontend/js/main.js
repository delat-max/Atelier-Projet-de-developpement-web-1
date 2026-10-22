function escapeHtml(value) {
	return String(value ?? '').replace(/[&<>"']/g, function (character) {
		return {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		}[character];
	});
}

function formatDate(value) {
	const date = new Date(value + 'T00:00:00');
	if (Number.isNaN(date.getTime())) return value;
	return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(date);
}

function getImageUrl(value) {
	if (!value) return null;
	try {
		const url = new URL(value, window.location.origin);
		if (url.protocol === 'http:' || url.protocol === 'https:') return url.href;
	} catch {
		return null;
	}
	return null;
}

function afficherCarte(spectacle) {
	const imageUrl = getImageUrl(spectacle.image);
	const artistes = spectacle.artistes.map(artist => escapeHtml(artist.nom)).join(', ');

	return `
		<article class="four columns carte-spectacle">
			<div class="marque-carte-spectacle">
				<span>NRV<br>LIVE</span>
				<span class="eclair-logo" aria-hidden="true">&#9889;</span>
			</div>
			<div class="image-carte-spectacle">
				${imageUrl
					? `<img class="u-max-full-width image-spectacle" width="320" height="180" src="${escapeHtml(imageUrl)}" alt="Affiche de ${escapeHtml(spectacle.titre)}" loading="lazy">`
					: '<div class="image-spectacle-absente" aria-hidden="true">NRV</div>'}
			</div>
			<div class="contenu-carte-spectacle">
				<p class="surtitre">${escapeHtml(spectacle.genre)}</p>
				<h2 class="titre-spectacle">${escapeHtml(spectacle.titre)}</h2>
				<p class="artistes-spectacle">${artistes || 'Artiste à annoncer'}</p>
				<p class="date-spectacle">${escapeHtml(formatDate(spectacle.date))} · ${escapeHtml(spectacle.horaire)}</p>
				<button class="button button-primary" type="button" data-soiree-id="${spectacle.id_soiree}">
					Voir la soirée
				</button>
			</div>
		</article>`;
}

function afficherListe(spectacles) {
	let html = '';
	for (let i = 0; i < spectacles.length; i += 3) {
		html += '<div class="row">';
		html += spectacles.slice(i, i + 3).map(afficherCarte).join('');
		html += '</div>';
	}
	return html;
}

function afficherDetail(soiree) {
	const content = document.querySelector('#soiree-content');
	const shows = soiree.spectacles.length
		? soiree.spectacles.map(show => `
				<article class="row ligne-spectacle">
					<time class="two columns horaire-spectacle">${escapeHtml(show.horaire)}</time>
					<div class="ten columns description-spectacle">
						<h3>${escapeHtml(show.titre)}</h3>
						<p>${show.artistes.map(a => escapeHtml(a.nom)).join(', ') || 'Artiste à annoncer'}</p>
						<p>${escapeHtml(show.genre)}${show.description ? ` · ${escapeHtml(show.description)}` : ''}</p>
						${show.video ? `<a href="${escapeHtml(getImageUrl(show.video) || '#')}" target="_blank" rel="noreferrer">Voir des vidéos</a>` : ''}
					</div>
				</article>`).join('')
		: '<p class="message-vide">Aucun spectacle n’est encore renseigné pour cette soirée.</p>';

	content.innerHTML = `
		<div class="row entete-soiree">
			<div class="twelve columns">
				<p class="surtitre">Détail de la soirée</p>
				<h1>${escapeHtml(soiree.nom)}</h1>
				<p>${escapeHtml(soiree.thematique || 'Programmation du festival')}</p>
			</div>
		</div>
		<div class="row infos-soiree">
			<div class="three columns"><p><strong>Date et début</strong><br>${escapeHtml(formatDate(soiree.date))} · ${escapeHtml(soiree.horaire_debut)}</p></div>
			<div class="three columns"><p><strong>Lieu</strong><br>${escapeHtml(soiree.lieu)}</p></div>
			<div class="three columns"><p><strong>Places restantes</strong><br>${escapeHtml(soiree.places_disponibles)}</p></div>
			<div class="three columns"><p><strong>Tarif indiqué</strong><br>${escapeHtml(Number(soiree.prix).toFixed(2))} €</p></div>
		</div>
		<div class="row"><div class="twelve columns"><h2 class="titre-spectacles">Au programme</h2></div></div>
		${shows}
		<div class="row banniere-action-soiree">
			<div class="twelve columns">
				<div class="contenu-banniere">
					<div>
						<strong>Prêt à vivre le concert ?</strong>
						<p class="texte-discret">${escapeHtml(soiree.places_disponibles)} places restantes · ${escapeHtml(Number(soiree.prix).toFixed(2))} € · ${escapeHtml(soiree.lieu)}</p>
					</div>
					<button class="button button-primary" type="button">Billetterie</button>
				</div>
			</div>
		</div>`;
}

// Remplissage asynchrone des filtres depuis les nouvelles routes
async function initFiltres() {
    try {
        const [datesRes, genresRes] = await Promise.all([
            window.NRV_API.getDates(),
            window.NRV_API.getGenres()
        ]);

        const filtreDate = document.querySelector('#filtre-date');
        const filtreGenre = document.querySelector('#filtre-genre');

        if (filtreDate && datesRes.data) {
            datesRes.data.forEach(date => {
                const option = document.createElement('option');
                option.value = date;
                option.textContent = formatDate(date);
                filtreDate.appendChild(option);
            });
        }

        if (filtreGenre && genresRes.data) {
            genresRes.data.forEach(genre => {
                const option = document.createElement('option');
                option.value = genre;
                option.textContent = genre;
                filtreGenre.appendChild(option);
            });
        }
    } catch (e) {
        console.error('Erreur lors du chargement des filtres.');
    }
}

document.addEventListener('DOMContentLoaded', async function () {
	const menuToggle = document.querySelector('.bouton-menu-mobile');
	const mobileDrawer = document.querySelector('.menu-mobile');

	if (menuToggle && mobileDrawer) {
		menuToggle.addEventListener('click', function () {
			const ouvert = menuToggle.getAttribute('aria-expanded') === 'true';
			menuToggle.setAttribute('aria-expanded', String(!ouvert));
			mobileDrawer.hidden = ouvert;
		});
	}

	let status = document.querySelector('#etat-api');
	const programView = document.querySelector('#vue-programme');
	const soireeView = document.querySelector('#vue-soiree');
	const spectacleList = document.querySelector('#spectacle-list');
	const filtreDate = document.querySelector('#filtre-date');
	const filtreGenre = document.querySelector('#filtre-genre');
	const triProgramme = document.querySelector('#tri-programme');
    
    let paginationContainer = document.querySelector('#pagination-spectacles');
    if (spectacleList && !paginationContainer) {
        paginationContainer = document.createElement('div');
        paginationContainer.id = 'pagination-spectacles';
        paginationContainer.className = 'row pagination-container';
        paginationContainer.style.textAlign = 'center';
        paginationContainer.style.marginTop = '2rem';
        spectacleList.after(paginationContainer);
    }

	if (spectacleList && !status) {
		status = document.createElement('p');
		status.id = 'etat-api';
		status.className = 'etat-api';
		status.setAttribute('role', 'status');
		status.setAttribute('aria-live', 'polite');
		spectacleList.before(status);
	}

	if (spectacleList) {
		let spectaclesActuels = [];

		const afficherResultats = function (spectacles) {
			spectaclesActuels = spectacles;
			if (status) status.hidden = true;
			spectacleList.innerHTML = spectacles.length
				? afficherListe(spectacles)
				: '<p class="message-vide">Aucun spectacle ne correspond à ces filtres.</p>';
		};

        const afficherPagination = function (meta) {
            if (!meta || meta.total_pages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            let html = '<div class="twelve columns">';
            for (let i = 1; i <= meta.total_pages; i++) {
                const isCurrent = i === meta.current_page;
                html += `<button class="button ${isCurrent ? 'button-primary' : ''} btn-page" data-page="${i}" style="margin: 0 4px;">${i}</button>`;
            }
            html += '</div>';
            paginationContainer.innerHTML = html;

            paginationContainer.querySelectorAll('.btn-page').forEach(btn => {
                btn.addEventListener('click', function() {
                    const pageChoisie = parseInt(this.getAttribute('data-page'), 10);
                    chargerSpectaclesFiltres(pageChoisie);
                    window.scrollTo({ top: spectacleList.offsetTop - 20, behavior: 'smooth' });
                });
            });
        };

		const chargerSpectaclesFiltres = async function (page = 1) {
			if (status) {
				status.hidden = false;
				status.textContent = 'Chargement…';
			}

			try {
				const result = await window.NRV_API.getSpectacles({
					date: filtreDate ? filtreDate.value : '',
					genre: filtreGenre ? filtreGenre.value : '',
					tri: triProgramme ? triProgramme.value : 'date-croissante',
                    page: page
				});
				afficherResultats(result.data);
                if (result.meta) afficherPagination(result.meta);
			} catch {
				if (status) {
					status.hidden = false;
					status.textContent = 'Impossible de charger les spectacles filtrés.';
				}
			}
		};

        // Initialisation indépendante des filtres puis de la liste
        await initFiltres();
		try {
			const result = await window.NRV_API.getSpectacles();
			afficherResultats(result.data);
            if (result.meta) afficherPagination(result.meta);
		} catch {
			if (status) {
				status.textContent = 'La programmation est momentanément indisponible.';
			}
		}

		if (filtreDate) filtreDate.addEventListener('change', () => chargerSpectaclesFiltres(1));
		if (filtreGenre) filtreGenre.addEventListener('change', () => chargerSpectaclesFiltres(1));
		if (triProgramme) triProgramme.addEventListener('change', () => chargerSpectaclesFiltres(1));

		spectacleList.addEventListener('click', async function (event) {
			const button = event.target.closest('[data-soiree-id]');
			if (!button) return;

			button.disabled = true;

			try {
				const result = await window.NRV_API.getSoiree(button.dataset.soireeId);
				afficherDetail(result.data);
				if (programView) programView.hidden = true;
				if (soireeView) soireeView.hidden = false;
				window.scrollTo({ top: 0, behavior: 'smooth' });
			} catch {
				if (status) {
					status.hidden = false;
					status.textContent = 'Impossible de charger le détail de cette soirée.';
				}
			} finally {
				button.disabled = false;
			}
		});
	}

	const backButton = document.querySelector('#back-to-program');
	if (backButton) {
		backButton.addEventListener('click', function () {
			if (soireeView) soireeView.hidden = true;
			if (programView) programView.hidden = false;
		});
	}
});