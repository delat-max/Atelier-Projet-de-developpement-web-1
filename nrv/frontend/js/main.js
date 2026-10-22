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

	if (Number.isNaN(date.getTime())) {
		return value;
	}

	return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(date);
}

function getImageUrl(value) {
	if (!value) {
		return null;
	}

	try {
		const url = new URL(value, window.location.origin);

		if (url.protocol === 'http:' || url.protocol === 'https:') {
			return url.href;
		}
	} catch {
		return null;
	}

	return null;
}

const CART_STORAGE_KEY = 'nrv-panier';

function lirePanier() {
	const contenu = window.localStorage.getItem(CART_STORAGE_KEY);
	const panier = contenu ? JSON.parse(contenu) : [];

	if (!Array.isArray(panier) || panier.some(function (article) {
		return !article || !Number.isInteger(article.id_soiree) || article.id_soiree < 1
			|| typeof article.nom !== 'string'
			|| typeof article.date !== 'string'
			|| typeof article.lieu !== 'string'
			|| !Number.isInteger(article.quantite) || article.quantite < 1
			|| !Number.isFinite(article.prix) || article.prix < 0
			|| !Number.isInteger(article.places_disponibles) || article.places_disponibles < 0;
	})) {
		throw new Error('Le contenu du panier est invalide.');
	}

	return panier;
}

function enregistrerPanier(panier) {
	window.localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(panier));
}

function formatPrix(prix) {
	return new Intl.NumberFormat('fr-FR', {
		style: 'currency',
		currency: 'EUR'
	}).format(prix);
}

function afficherCarte(spectacle) {
	const imageUrl = getImageUrl(spectacle.image);
	const artistes = spectacle.artistes.map(function (artist) {
		return escapeHtml(artist.nom);
	}).join(', ');

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
	const idSoiree = Number(soiree.id_soiree);
	const prix = Number(soiree.prix);
	const placesDisponibles = Number(soiree.places_disponibles);
	const shows = soiree.spectacles.length
		? soiree.spectacles.map(function (show) {
			return `
				<article class="row ligne-spectacle">
					<time class="two columns horaire-spectacle">${escapeHtml(show.horaire)}</time>
					<div class="ten columns description-spectacle">
						<h3>${escapeHtml(show.titre)}</h3>
						<p>${show.artistes.map(function (artist) {
							return escapeHtml(artist.nom);
						}).join(', ') || 'Artiste à annoncer'}</p>
						<p>${escapeHtml(show.genre)}${show.description ? ` · ${escapeHtml(show.description)}` : ''}</p>
						${show.video ? `<a href="${escapeHtml(getImageUrl(show.video) || '#')}" target="_blank" rel="noreferrer">Voir des vidéos</a>` : ''}
					</div>
				</article>`;
		}).join('')
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
			<div class="three columns"><p><strong>Tarif indiqué</strong><br>${escapeHtml(formatPrix(prix))}</p></div>
		</div>
		<div class="row"><div class="twelve columns"><h2 class="titre-spectacles">Au programme</h2></div></div>
		${shows}
		<div class="row banniere-action-soiree">
			<div class="twelve columns">
				<div class="contenu-banniere">
					<div>
						<strong>Prêt à vivre le concert ?</strong>
						<p class="texte-discret">${escapeHtml(placesDisponibles)} places restantes · ${escapeHtml(formatPrix(prix))} · ${escapeHtml(soiree.lieu)}</p>
					</div>
					<div class="actions-billetterie">
						<label for="quantite-billets">Billets</label>
						<input id="quantite-billets" type="number" min="1" max="${escapeHtml(placesDisponibles)}" value="1" ${placesDisponibles < 1 ? 'disabled' : ''}>
						<button class="button button-primary" type="button" data-ajouter-billet
							data-id-soiree="${escapeHtml(idSoiree)}"
							data-nom="${escapeHtml(soiree.nom)}"
							data-date="${escapeHtml(soiree.date)}"
							data-lieu="${escapeHtml(soiree.lieu)}"
							data-prix="${escapeHtml(prix)}"
							data-places="${escapeHtml(placesDisponibles)}"
							${placesDisponibles < 1 ? 'disabled' : ''}>Ajouter au panier</button>
						<a class="button button-light" href="/panier.html">Voir le panier</a>
					</div>
				</div>
				<p id="etat-ajout-panier" class="etat-ajout-panier" role="status" aria-live="polite"></p>
			</div>
		</div>`;
}

function afficherPanier() {
	const content = document.querySelector('#panier-content');
	const status = document.querySelector('#etat-panier');

	if (!content) {
		return;
	}

	try {
		const panier = lirePanier();
		const total = panier.reduce((montant, article) => montant + article.prix * article.quantite, 0);

		content.innerHTML = panier.length
			? panier.map(function (article) {
				return `
					<article class="article-panier">
						<div class="entete-article-panier">
							<strong>NRV<br>LIVE</strong>
							<span aria-hidden="true">&#9889;</span>
						</div>
						<div class="corps-article-panier">
							<div class="infos-article-panier">
								<h2>${escapeHtml(article.nom)}</h2>
								<p>${escapeHtml(formatDate(article.date))} · ${escapeHtml(article.lieu)}</p>
								<a href="/">Voir la programmation <span aria-hidden="true">↗</span></a>
								<button class="bouton-retirer-panier" type="button" data-retirer-panier="${escapeHtml(article.id_soiree)}">Retirer</button>
							</div>
							<div class="achat-article-panier">
								<div class="controle-quantite-panier">
									<span>Tickets</span>
									<button type="button" aria-label="Retirer un billet" data-quantite-step="${escapeHtml(article.id_soiree)}" data-delta="-1" ${article.quantite <= 1 ? 'disabled' : ''}>−</button>
									<input type="number" min="1" max="${escapeHtml(article.places_disponibles)}"
										value="${escapeHtml(article.quantite)}" aria-label="Quantité de billets pour ${escapeHtml(article.nom)}"
										data-quantite-panier="${escapeHtml(article.id_soiree)}">
									<button type="button" aria-label="Ajouter un billet" data-quantite-step="${escapeHtml(article.id_soiree)}" data-delta="1" ${article.quantite >= article.places_disponibles ? 'disabled' : ''}>+</button>
								</div>
								<strong class="prix-article-panier">${escapeHtml(formatPrix(article.prix * article.quantite))}</strong>
							</div>
						</div>
					</article>`;
			}).join('')
			: `<div class="panneau-vide">
					<div class="logo-panneau-vide"><span>NRV</span><strong>LIVE</strong><b>⚡</b></div>
					<h2>Votre panier est vide</h2>
					<p>Les billets ajoutés depuis la programmation apparaîtront ici.</p>
					<a class="button button-primary" href="/">Voir le programme</a>
				</div>`;

		document.querySelector('#total-panier').textContent = formatPrix(total);
		if (status) {
			status.textContent = '';
		}
	} catch (error) {
		if (status) {
			status.textContent = 'Impossible de lire le panier. Vérifiez les paramètres de stockage de votre navigateur.';
		}
		console.error(error);
	}
}

function remplirFiltres(spectacles) {
	const filtreDate = document.querySelector('#filtre-date');
	const filtreGenre = document.querySelector('#filtre-genre');

	if (!filtreDate || !filtreGenre) {
		return;
	}

	const dates = [...new Set(spectacles.map((spectacle) => spectacle.date).filter(Boolean))].sort();
	const genres = [...new Set(spectacles.map((spectacle) => spectacle.genre).filter(Boolean))]
		.sort((a, b) => a.localeCompare(b, 'fr'));

	dates.forEach((date) => {
		const option = document.createElement('option');
		option.value = date;
		option.textContent = formatDate(date);
		filtreDate.appendChild(option);
	});

	genres.forEach((genre) => {
		const option = document.createElement('option');
		option.value = genre;
		option.textContent = genre;
		filtreGenre.appendChild(option);
	});
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
	const contenuSoiree = document.querySelector('#soiree-content');

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
			if (status) {
				status.hidden = true;
			}
			spectacleList.innerHTML = spectacles.length
				? afficherListe(spectacles)
				: '<p class="message-vide">Aucun spectacle ne correspond à ces filtres.</p>';
		};

		const chargerSpectaclesFiltres = async function () {
			if (status) {
				status.hidden = false;
				status.textContent = 'Chargement…';
			}

			try {
				const result = await window.NRV_API.getSpectacles({
					date: filtreDate ? filtreDate.value : '',
					genre: filtreGenre ? filtreGenre.value : '',
					tri: triProgramme ? triProgramme.value : 'date-croissante'
				});
				afficherResultats(result.data);
			} catch {
				if (status) {
					status.hidden = false;
					status.textContent = 'Impossible de charger les spectacles filtrés.';
				}
			}
		};

		try {
			const result = await window.NRV_API.getSpectacles();
			remplirFiltres(result.data);
			afficherResultats(result.data);
		} catch {
			if (status) {
				status.textContent = 'La programmation est momentanément indisponible.';
			}
		}

		if (filtreDate) {
			filtreDate.addEventListener('change', chargerSpectaclesFiltres);
		}
		if (filtreGenre) {
			filtreGenre.addEventListener('change', chargerSpectaclesFiltres);
		}
		if (triProgramme) {
			triProgramme.addEventListener('change', chargerSpectaclesFiltres);
		}

		spectacleList.addEventListener('click', async function (event) {
			const button = event.target.closest('[data-soiree-id]');

			if (!button) {
				return;
			}

			button.disabled = true;

			try {
				const result = await window.NRV_API.getSoiree(button.dataset.soireeId);
				afficherDetail(result.data);
				if (programView) {
					programView.hidden = true;
				}
				if (soireeView) {
					soireeView.hidden = false;
				}
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
			if (soireeView) {
				soireeView.hidden = true;
			}
			if (programView) {
				programView.hidden = false;
			}
		});
	}

	if (contenuSoiree) {
		contenuSoiree.addEventListener('click', function (event) {
			const button = event.target.closest('[data-ajouter-billet]');

			if (!button) {
				return;
			}

			const statusAjout = document.querySelector('#etat-ajout-panier');
			const input = document.querySelector('#quantite-billets');
			const quantite = Number(input.value);
			const placesDisponibles = Number(button.dataset.places);

			if (!Number.isInteger(quantite) || quantite < 1) {
				statusAjout.textContent = 'Choisissez au moins un billet.';
				return;
			}

			try {
				const panier = lirePanier();
				const idSoiree = Number(button.dataset.idSoiree);
				const article = panier.find((item) => item.id_soiree === idSoiree);
				const dejaDansPanier = article ? article.quantite : 0;

				if (quantite + dejaDansPanier > placesDisponibles) {
					statusAjout.textContent = `Il ne reste que ${Math.max(0, placesDisponibles - dejaDansPanier)} place(s) disponible(s) pour cette soirée.`;
					return;
				}

				if (article) {
					article.quantite += quantite;
				} else {
					panier.push({
						id_soiree: idSoiree,
						nom: button.dataset.nom,
						date: button.dataset.date,
						lieu: button.dataset.lieu,
						prix: Number(button.dataset.prix),
						places_disponibles: placesDisponibles,
						quantite: quantite
					});
				}

				enregistrerPanier(panier);
				statusAjout.textContent = `${quantite} billet(s) ajouté(s) au panier.`;
			} catch (error) {
				statusAjout.textContent = 'Impossible d’enregistrer le panier dans ce navigateur.';
				console.error(error);
			}
		});
	}

	const contenuPanier = document.querySelector('#panier-content');

	if (contenuPanier) {
		afficherPanier();
		contenuPanier.addEventListener('click', function (event) {
			const stepButton = event.target.closest('[data-quantite-step]');

			if (stepButton) {
				try {
					const panier = lirePanier();
					const idSoiree = Number(stepButton.dataset.quantiteStep);
					const article = panier.find((item) => item.id_soiree === idSoiree);

					if (article) {
						const quantite = article.quantite + Number(stepButton.dataset.delta);
						if (quantite >= 1 && quantite <= article.places_disponibles) {
							article.quantite = quantite;
							enregistrerPanier(panier);
							afficherPanier();
						}
					}
				} catch (error) {
					document.querySelector('#etat-panier').textContent = 'Impossible de modifier le panier.';
					console.error(error);
				}
				return;
			}

			const button = event.target.closest('[data-retirer-panier]');

			if (!button) {
				return;
			}

			try {
				const idSoiree = Number(button.dataset.retirerPanier);
				enregistrerPanier(lirePanier().filter((article) => article.id_soiree !== idSoiree));
				afficherPanier();
			} catch (error) {
				document.querySelector('#etat-panier').textContent = 'Impossible de modifier le panier.';
				console.error(error);
			}
		});
		contenuPanier.addEventListener('change', function (event) {
			const input = event.target.closest('[data-quantite-panier]');

			if (!input) {
				return;
			}

			const quantite = Number(input.value);
			const placesDisponibles = Number(input.max);

			if (!Number.isInteger(quantite) || quantite < 1 || quantite > placesDisponibles) {
				document.querySelector('#etat-panier').textContent = `La quantité doit être comprise entre 1 et ${placesDisponibles}.`;
				return;
			}

			try {
				const idSoiree = Number(input.dataset.quantitePanier);
				const panier = lirePanier();
				const article = panier.find((item) => item.id_soiree === idSoiree);

				if (article) {
					article.quantite = quantite;
					enregistrerPanier(panier);
					afficherPanier();
				}
			} catch (error) {
				document.querySelector('#etat-panier').textContent = 'Impossible de modifier le panier.';
				console.error(error);
			}
		});
	}
});
