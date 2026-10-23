
(function () {
    'use strict';

    async function appliquerInterfaceAdmin() {
        let response;

        try {
            response = await fetch('/api/admin/me', {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json'
                }
            });
        } catch (error) {
            return;
        }

        if (!response.ok) {
            return;
        }

        const chemin = window.location.pathname;

        if (
            chemin === '/panier.html' ||
            chemin === '/commande.html'
        ) {
            window.location.replace('/admin.html');
            return;
        }

        function appliquerRestrictionsAdmin() {
            document.querySelectorAll(
                'a[href="/panier.html"]'
            ).forEach(function (lien) {
                lien.href = '/admin.html';

                lien.textContent = lien.closest('.menu-mobile')
                    ? 'Back-office'
                    : 'Back-office ↗';

                lien.setAttribute(
                    'aria-label',
                    'Ouvrir le back-office'
                );
            });

            document.querySelectorAll(
                '.actions-billetterie, .banniere-action-soiree, .carte-pass'
            ).forEach(function (element) {
                element.hidden = true;
            });

            document.querySelectorAll(
                '[data-ajouter-billet]'
            ).forEach(function (bouton) {
                bouton.disabled = true;
            });
        }

        appliquerRestrictionsAdmin();

        const observateur = new MutationObserver(
            appliquerRestrictionsAdmin
        );

        observateur.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            appliquerInterfaceAdmin,
            { once: true }
        );
    } else {
        appliquerInterfaceAdmin();
    }
})();
