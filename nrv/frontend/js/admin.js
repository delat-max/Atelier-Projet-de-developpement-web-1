(function () {
    'use strict';

    const message = document.querySelector('#admin-message');
    const formSoiree = document.querySelector('#form-soiree');
    const formSpectacle = document.querySelector('#form-spectacle');
    const selectSoiree = document.querySelector('#spectacle-soiree');
    const selectArtiste = document.querySelector('#spectacle-artiste');

    function afficherMessage(texte) {
        message.textContent = texte;
    }

    async function api(path, options = {}) {
        const response = await fetch(path, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body
                    ? { 'Content-Type': 'application/json' }
                    : {}),
                ...(options.headers || {})
            }
        });

        const resultat = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(
                resultat.error || 'Une erreur est survenue.'
            );
            error.status = response.status;
            throw error;
        }

        return resultat;
    }

    function echapper(value) {
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

    function euros(value) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(Number(value || 0));
    }

    async function verifierAdmin() {
        try {
            await api('/api/admin/me');
            return true;
        } catch (error) {
            if (error.status === 401) {
                afficherMessage(
                    'Connecte-toi avec le compte administrateur.'
                );
                window.location.href = '/profil.html';
            } else if (error.status === 403) {
                afficherMessage(
                    'Ce compte ne possède pas les droits administrateur.'
                );
                formSoiree.hidden = true;
                formSpectacle.hidden = true;
            } else {
                afficherMessage(
                    'Impossible de vérifier les droits administrateur.'
                );
            }

            return false;
        }
    }

    async function chargerStatistiques() {
        const resultat = await api('/api/admin/tableau-de-bord');
        const stats = resultat.data;

        document.querySelector('#stat-soirees').textContent =
            stats.nombre_soirees;
        document.querySelector('#stat-spectacles').textContent =
            stats.nombre_spectacles;
        document.querySelector('#stat-billets').textContent =
            stats.billets_vendus;
        document.querySelector('#stat-commandes').textContent =
            stats.commandes_payees;
        document.querySelector('#stat-revenu').textContent =
            euros(stats.revenu_total);
    }

    async function chargerArtistes() {
        const resultat = await api('/api/admin/artistes');

        selectArtiste.innerHTML =
            '<option value="">Choisir un artiste</option>';

        resultat.data.forEach(function (artiste) {
            const option = document.createElement('option');
            option.value = artiste.id_artiste;
            option.textContent =
                `${artiste.nom} — ${artiste.genre || 'Genre non précisé'}`;
            selectArtiste.appendChild(option);
        });
    }

    
    let pageSoireesActuelle = 1;
    const SOIREES_PAR_PAGE = 2;

    async function chargerSoirees() {
        const resultat = await api('/api/admin/soirees');
        const soirees = resultat.data;

        selectSoiree.innerHTML =
            '<option value="">Choisir une soirée</option>';

        soirees.forEach(function (soiree) {
            const option = document.createElement('option');
            option.value = soiree.id_soiree;
            option.textContent =
                `${soiree.nom} — ${soiree.nombre_spectacles}/3 spectacles`;

            option.disabled = Number(soiree.nombre_spectacles) >= 3;
            selectSoiree.appendChild(option);
        });

        const conteneur = document.querySelector('#liste-soirees');

        if (soirees.length === 0) {
            pageSoireesActuelle = 1;
            conteneur.textContent = 'Aucune soirée enregistrée.';
            return;
        }

        const nombrePages = Math.ceil(
            soirees.length / SOIREES_PAR_PAGE
        );

        pageSoireesActuelle = Math.min(
            pageSoireesActuelle,
            nombrePages
        );

        const debut = (pageSoireesActuelle - 1) * SOIREES_PAR_PAGE;

        const soireesPage = soirees.slice(
            debut,
            debut + SOIREES_PAR_PAGE
        );

        conteneur.innerHTML = `
            <div class="row">
                ${soireesPage.map(function (soiree) {
                    return `
                        <div class="six columns">
                            <article class="panneau-commande">
                                <p class="surtitre">Soirée du festival</p>

                                <h2>${echapper(soiree.nom)}</h2>

                                <p>
                                    <strong>Date :</strong>
                                    ${echapper(soiree.date)}
                                </p>

                                <p>
                                    <strong>Horaire :</strong>
                                    ${echapper(soiree.horaire_debut)}
                                </p>

                                <p>
                                    <strong>Lieu :</strong>
                                    ${echapper(soiree.lieu)}
                                </p>

                                <hr>

                                <h3>Capacité et programmation</h3>

                                <p>
                                    <strong>Places restantes :</strong>
                                    ${echapper(soiree.places_disponibles)}
                                </p>

                                <p>
                                    <strong>Spectacles :</strong>
                                    ${echapper(soiree.nombre_spectacles)}/3
                                </p>

                                <hr>

                                <h3>Ventes et recettes</h3>

                                <p>
                                    <strong>Billets vendus :</strong>
                                    ${echapper(soiree.billets_vendus)}
                                </p>

                                <p>
                                    <strong>Pass vendus :</strong>
                                    ${echapper(soiree.pass_vendus)}
                                </p>

                                <p>
                                    <strong>Recettes des billets individuels :</strong>
                                </p>

                                <h3>${euros(soiree.revenu_individuel)}</h3>
                            </article>
                        </div>
                    `;
                }).join('')}
            </div>

            ${nombrePages > 1 ? `
                <div class="row">
                    <div class="twelve columns" style="text-align: center;">
                        <button
                            type="button"
                            class="button-light"
                            id="soirees-precedentes"
                            ${pageSoireesActuelle === 1 ? 'disabled' : ''}
                        >
                            Précédent
                        </button>

                        <span>
                            Page ${pageSoireesActuelle} / ${nombrePages}
                        </span>

                        <button
                            type="button"
                            class="button-primary"
                            id="soirees-suivantes"
                            ${pageSoireesActuelle === nombrePages ? 'disabled' : ''}
                        >
                            Suivant
                        </button>
                    </div>
                </div>
            ` : ''}
        `;

        document
            .querySelector('#soirees-precedentes')
            ?.addEventListener('click', function () {
                pageSoireesActuelle--;
                chargerSoirees();
            });

        document
            .querySelector('#soirees-suivantes')
            ?.addEventListener('click', function () {
                pageSoireesActuelle++;
                chargerSoirees();
            });
    }


    formSoiree.addEventListener('submit', async function (event) {
        event.preventDefault();

        const donnees = Object.fromEntries(
            new FormData(formSoiree).entries()
        );

        donnees.places_disponibles = Number(donnees.places_disponibles);
        donnees.prix = Number(donnees.prix);

        try {
            await api('/api/admin/soirees', {
                method: 'POST',
                body: JSON.stringify(donnees)
            });

            formSoiree.reset();
            afficherMessage('Soirée créée. Ajoute maintenant ses spectacles.');

            await Promise.all([
                chargerStatistiques(),
                chargerSoirees()
            ]);
        } catch (error) {
            afficherMessage(error.message);
        }
    });

    formSpectacle.addEventListener('submit', async function (event) {
        event.preventDefault();

        const donnees = Object.fromEntries(
            new FormData(formSpectacle).entries()
        );

        donnees.id_soiree = Number(donnees.id_soiree);
        donnees.id_artiste = Number(donnees.id_artiste);

        try {
            await api('/api/admin/spectacles', {
                method: 'POST',
                body: JSON.stringify(donnees)
            });

            formSpectacle.reset();
            afficherMessage('Spectacle ajouté à la programmation.');

            await Promise.all([
                chargerStatistiques(),
                chargerSoirees()
            ]);
        } catch (error) {
            afficherMessage(error.message);
        }
    });

    async function initialiser() {
        if (!await verifierAdmin()) {
            return;
        }

        try {
            await Promise.all([
                chargerStatistiques(),
                chargerSoirees(),
                chargerArtistes()
            ]);
        } catch (error) {
            afficherMessage(error.message);
        }
    }

    initialiser();
})();