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

        initialiserModificationProfil();
        initialiserModificationMotDePasse();
        initialiserDeconnexion();
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

    initialiserConnexion();
    initialiserInscription();
    initialiserProfil();
})();