(function () {
    async function request(path, options = {}) {
        const headers = {
            Accept: 'application/json',
            ...(options.headers || {})
        };

        if (options.body !== undefined) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(path, {
            credentials: 'same-origin',
            ...options,
            headers
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(
                payload.error || 'Une erreur est survenue.'
            );

            error.status = response.status;

            throw error;
        }

        return payload;
    }

    function afficherMessage(message, erreur = false) {
        const element = document.querySelector('#auth-message');

        if (!element) {
            return;
        }

        element.textContent = message;
        element.classList.toggle('erreur', erreur);
        element.classList.toggle('succes', !erreur);
    }

    function initialiserConnexion() {
        const form = document.querySelector('#login-form');

        if (!form) {
            return;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            try {
                await request('/api/auth/connexion', {
                    method: 'POST',
                    body: JSON.stringify({
                        email: formData.get('email'),
                        password: formData.get('password')
                    })
                });

                window.location.href = '/profil-connecte.html';
            } catch (error) {
                afficherMessage(error.message, true);
            }
        });

        verifierConnexion();
    }

    async function verifierConnexion() {
        try {
            await request('/api/utilisateurs/me');
            window.location.href = '/profil-connecte.html';
        } catch (error) {
            if (error.status !== 401) {
                afficherMessage(error.message, true);
            }
        }
    }

    function initialiserInscription() {
        const form = document.querySelector('#register-form');

        if (!form) {
            return;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            try {
                await request('/api/utilisateurs', {
                    method: 'POST',
                    body: JSON.stringify({
                        nom: formData.get('nom'),
                        prenom: formData.get('prenom'),
                        email: formData.get('email'),
                        password: formData.get('password')
                    })
                });

                afficherMessage(
                    'Inscription réussie ! Vous pouvez maintenant vous connecter.'
                );

                form.reset();
            } catch (error) {
                afficherMessage(error.message, true);
            }
        });
    }

    async function initialiserProfil() {
        const profilContent = document.querySelector('#profile-content');

        if (!profilContent) {
            return;
        }

        try {
            const result = await request('/api/utilisateurs/me');
            const utilisateur = result.data;

            const titre = document.querySelector('#profile-title');
            const emailDisplay = document.querySelector('#profile-email-display');
            const avatar = document.querySelector('#profile-avatar');
            const nomInput = document.querySelector('#edit-nom');
            const prenomInput = document.querySelector('#edit-prenom');
            const emailInput = document.querySelector('#edit-email');

            if (titre) {
                titre.textContent = `${utilisateur.prenom} ${utilisateur.nom}`;
            }

            if (emailDisplay) {
                emailDisplay.textContent = utilisateur.email;
            }

            if (avatar) {
                avatar.textContent = (
                    `${utilisateur.prenom?.charAt(0) || ''}` +
                    `${utilisateur.nom?.charAt(0) || ''}`
                ).toUpperCase();
            }

            if (nomInput) {
                nomInput.value = utilisateur.nom;
            }

            if (prenomInput) {
                prenomInput.value = utilisateur.prenom;
            }

            if (emailInput) {
                emailInput.value = utilisateur.email;
            }

            profilContent.hidden = false;
        } catch (error) {
            if (error.status === 401) {
                window.location.href = '/profil.html';
                return;
            }

            afficherMessage(error.message, true);
        }

        let estAdmin = false;

        try {
            await request('/api/admin/me');
            estAdmin = true;
        } catch (errorAdmin) {
            if (![401, 403].includes(errorAdmin.status)) {
                console.warn(
                    'Vérification du rôle administrateur impossible.',
                    errorAdmin
                );
            }
        }

        const accesAdmin = document.querySelector('#admin-access');
        const sectionBillets = document.querySelector('#mes-billets-section');

        if (estAdmin) {
            if (accesAdmin) {
                accesAdmin.hidden = false;
            }

            if (sectionBillets) {
                sectionBillets.hidden = true;
            }
        } else {
            chargerMesBillets();
        }

        initialiserModificationProfil();
        initialiserModificationMotDePasse();
        initialiserDeconnexion();
        chargerMesBillets();
    }

    function initialiserModificationProfil() {
        const form = document.querySelector('#profile-form');

        if (!form) {
            return;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            try {
                const result = await request('/api/utilisateurs/me', {
                    method: 'PATCH',
                    body: JSON.stringify({
                        nom: formData.get('nom'),
                        prenom: formData.get('prenom'),
                        email: formData.get('email')
                    })
                });

                const utilisateur = result.data;

                const titre = document.querySelector('#profile-title');
                const emailDisplay = document.querySelector('#profile-email-display');
                const avatar = document.querySelector('#profile-avatar');

                if (titre) {
                    titre.textContent = `${utilisateur.prenom} ${utilisateur.nom}`;
                }

                if (emailDisplay) {
                    emailDisplay.textContent = utilisateur.email;
                }

                if (avatar) {
                    avatar.textContent = (
                        `${utilisateur.prenom?.charAt(0) || ''}` +
                        `${utilisateur.nom?.charAt(0) || ''}`
                    ).toUpperCase();
                }

                afficherMessage('Profil mis à jour avec succès.');
            } catch (error) {
                afficherMessage(error.message, true);
            }
        });
    }

    function initialiserModificationMotDePasse() {
        const form = document.querySelector('#password-form');

        if (!form) {
            return;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            try {
                await request('/api/utilisateurs/me/mot-de-passe', {
                    method: 'PATCH',
                    body: JSON.stringify({
                        current_password: formData.get('current_password'),
                        new_password: formData.get('new_password')
                    })
                });

                afficherMessage('Mot de passe modifié avec succès.');
                form.reset();
            } catch (error) {
                afficherMessage(error.message, true);
            }
        });
    }

    function initialiserDeconnexion() {
        const button = document.querySelector('#logout-button');

        if (!button) {
            return;
        }

        button.addEventListener('click', async function () {
            try {
                await request('/api/auth/deconnexion', {
                    method: 'POST'
                });

                window.location.href = '/profil.html';
            } catch (error) {
                afficherMessage(error.message, true);
            }
        });
    }

    async function chargerMesBillets() {
        const liste = document.querySelector('#mes-billets-liste');
        const message = document.querySelector('#mes-billets-message');

        if (!liste || !message) {
            return;
        }

        try {
            const resultat = await request(
                '/api/utilisateurs/me/billets'
            );

            const billets = Array.isArray(resultat.data)
                ? resultat.data
                : [];

            liste.replaceChildren();

            if (billets.length === 0) {
                message.textContent =
                    'Tu n’as pas encore de billet acheté.';
                return;
            }

            message.textContent =
                `${billets.length} billet(s) acheté(s).`;

            for (const billet of billets) {
                const carte = document.createElement('article');
                carte.className = 'billet-profil';

                const titre = document.createElement('h3');
                titre.textContent = billet.nom_soiree ?? 'Billet NRV';
                carte.appendChild(titre);

                const reference = document.createElement('p');
                reference.textContent =
                    `Référence : ${billet.reference}`;
                carte.appendChild(reference);

                const tarif = document.createElement('p');
                tarif.textContent =
                    `Tarif : ${libelleTarifProfil(billet.categorie_tarif)}`;
                carte.appendChild(tarif);

                if (billet.date_soiree) {
                    const date = document.createElement('p');
                    date.textContent =
                        `Date : ${formaterDateProfil(billet.date_soiree)}`;
                    carte.appendChild(date);
                }

                if (billet.horaire_debut) {
                    const horaire = document.createElement('p');
                    horaire.textContent =
                        `Début : ${String(billet.horaire_debut).slice(0, 5)}`;
                    carte.appendChild(horaire);
                }

                const lien = document.createElement('a');
                const url = new URL('/billet.html', window.location.origin);
                url.searchParams.set('token', billet.token);
                lien.href = url.href;
                lien.className = 'button button-primary';
                lien.textContent = 'Voir et imprimer le billet';
                carte.appendChild(lien);

                liste.appendChild(carte);
            }
        } catch (error) {
            message.textContent =
                error.message || 'Impossible de charger les billets.';
        }
    }

    function libelleTarifProfil(typeTarif) {
        const libelles = {
            normal: 'Normal',
            reduit: 'Réduit',
            pass_3_jours: 'Pass 3 jours',
            pass_3_jours_reduit: 'Pass 3 jours réduit',
        };

        return libelles[typeTarif] ?? 'Non précisé';
    }

    function formaterDateProfil(valeur) {
        const date = new Date(`${valeur}T00:00:00`);

        if (Number.isNaN(date.getTime())) {
            return valeur;
        }

        return new Intl.DateTimeFormat('fr-FR', {
            dateStyle: 'long',
        }).format(date);
    }

    initialiserConnexion();
    initialiserInscription();
    initialiserProfil();
})();