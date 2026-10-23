'use strict';

document.addEventListener('DOMContentLoaded', chargerBillet);

async function chargerBillet() {
    const message = document.querySelector('#billet-message');
    const contenu = document.querySelector('#billet-contenu');

    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');

    if (!token || !/^[a-f0-9]{64}$/.test(token)) {
        message.textContent = 'Lien de billet invalide.';
        return;
    }

    try {
        const reponse = await fetch(
            `/api/billets/${encodeURIComponent(token)}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                cache: 'no-store',
            }
        );

        const resultat = await reponse.json();

        if (!reponse.ok) {
            throw new Error(resultat.error ?? 'Billet introuvable.');
        }

        const billet = resultat.data;

        if (!billet) {
            throw new Error('Billet introuvable.');
        }

        afficherBillet(billet, token);

        message.textContent = '';
        contenu.hidden = false;
    } catch (erreur) {
        console.error('Erreur de chargement du billet :', erreur);
        message.textContent =
            erreur.message || 'Impossible de charger ce billet.';
    }
}

function afficherBillet(billet, token) {
    document.querySelector('#billet-nom').textContent =
        billet.nom_soiree ?? 'Billet NRV';

    document.querySelector('#billet-reference').textContent =
        billet.reference ?? '';

    document.querySelector('#billet-acheteur').textContent =
        `${billet.prenom_acheteur ?? ''} ${billet.nom_acheteur ?? ''}`.trim();

    document.querySelector('#billet-tarif').textContent =
        libelleTarif(billet.categorie_tarif);

    if (billet.date_soiree) {
        document.querySelector('#billet-date').textContent =
            formaterDate(billet.date_soiree);

        document.querySelector('#billet-date-conteneur').hidden = false;
    }

    if (billet.horaire_debut) {
        document.querySelector('#billet-horaire').textContent =
            String(billet.horaire_debut).slice(0, 5);

        document.querySelector('#billet-horaire-conteneur').hidden = false;
    }

    if (billet.lieu) {
        document.querySelector('#billet-lieu').textContent = billet.lieu;
        document.querySelector('#billet-lieu-conteneur').hidden = false;
    }

    const qr = document.querySelector('#billet-qr');
    qr.replaceChildren();

    const urlBillet = new URL('/billet.html', window.location.origin);
    urlBillet.searchParams.set('token', token);

    if (typeof QRCode !== 'undefined') {
        new QRCode(qr, {
            text: urlBillet.href,
            width: 180,
            height: 180,
            correctLevel: QRCode.CorrectLevel.M,
        });
    } else {
        qr.textContent =
            'QR code indisponible. Recharge la page pour réessayer.';
    }

    document.querySelector('#billet-imprimer').addEventListener(
        'click',
        () => window.print()
    );
}

function libelleTarif(typeTarif) {
    const libelles = {
        normal: 'Normal',
        reduit: 'Réduit',
        pass_3_jours: 'Pass 3 jours',
        pass_3_jours_reduit: 'Pass 3 jours réduit',
    };

    return libelles[typeTarif] ?? 'Non précisé';
}

function formaterDate(valeur) {
    const date = new Date(`${valeur}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return valeur;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'long',
    }).format(date);
}