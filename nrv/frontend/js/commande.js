'use strict';

let idCommande = null;

document.addEventListener('DOMContentLoaded', initialiserCommande);

async function initialiserCommande() {
    const resume = document.querySelector('#commande-resume');
    const formulaire = document.querySelector('#formulaire-paiement');
    const message = document.querySelector('#paiement-message');
    const bouton = formulaire?.querySelector('[type="submit"]');

    if (!resume || !formulaire || !message || !bouton) {
        console.error('Éléments manquants dans commande.html.');
        return;
    }

    formulaire.addEventListener('submit', confirmerCommande);

    try {
        const profilResultat = await requete('/api/utilisateurs/me');
        const utilisateur = profilResultat.data;

        const nomAcheteur =
            `${utilisateur.prenom ?? ''} ${utilisateur.nom ?? ''}`.trim();

        if (!nomAcheteur) {
            throw new Error('Le nom du compte est indisponible.');
        }

        document.querySelector('#acheteur-nom').value = nomAcheteur;
        document.querySelector('#carte-nom').value = nomAcheteur;

        const panierResultat = await requete('/api/panier');
        const commande = panierResultat.data;

        if (
            !commande
            || !commande.id_commande
            || !Array.isArray(commande.articles)
            || commande.articles.length === 0
        ) {
            resume.textContent = 'Ton panier est vide.';
            formulaire.hidden = true;
            return;
        }

        idCommande = Number(commande.id_commande);

        if (!Number.isInteger(idCommande) || idCommande < 1) {
            throw new Error('Identifiant de commande invalide.');
        }

        afficherCommande(commande);
    } catch (erreur) {
        console.error('Erreur de chargement de la commande :', erreur);
        resume.textContent =
            erreur.message || 'Impossible de charger la commande.';
        formulaire.hidden = true;
    }
}

function afficherCommande(commande) {
    const resume = document.querySelector('#commande-resume');
    const identifiant = document.querySelector('#commande-identifiant');

    identifiant.textContent = `Commande n° ${commande.id_commande}`;
    resume.replaceChildren();

    const liste = document.createElement('ul');

    for (const article of commande.articles) {
        const ligne = document.createElement('li');
        const nom = article.nom ?? 'Billet';
        const quantite = Number(article.quantite ?? 1);

        ligne.textContent =
            `${nom} — ${quantite} billet(s) — ` +
            `${libelleTarif(article.type_tarif)} — ` +
            `${formatPrix(Number(article.prix) * quantite)}`;

        liste.appendChild(ligne);
    }

    resume.appendChild(liste);

    const total = document.createElement('p');
    total.className = 'total-commande';
    total.textContent =
        `Total à régler (simulation) : ${formatPrix(commande.montant_total)}`;

    resume.appendChild(total);
}

async function confirmerCommande(event) {
    event.preventDefault();

    const formulaire = event.currentTarget;
    const message = document.querySelector('#paiement-message');
    const bouton = formulaire.querySelector('[type="submit"]');

    if (!idCommande) {
        message.textContent = 'Aucune commande valide à confirmer.';
        return;
    }

    if (!formulaire.reportValidity()) {
        return;
    }

    bouton.disabled = true;
    message.textContent = 'Confirmation de la commande…';

    const donneesSimulation = {
        nom_carte: document.querySelector('#carte-nom').value.trim(),
        numero_carte: document.querySelector('#carte-numero').value.replace(/\s/g, ''),
        expiration: document.querySelector('#carte-expiration').value,
        code: document.querySelector('#carte-code').value,
    };

    try {
        const resultat = await requete(
            `/api/commandes/${idCommande}/paiement`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(donneesSimulation),
            }
        );

        const commande = resultat.data ?? resultat;
        localStorage.removeItem('nrv-panier');
        const billets = Array.isArray(commande.billets)
            ? commande.billets
            : [];

        message.replaceChildren();

        const titre = document.createElement('h2');
        titre.textContent = 'Commande confirmée';
        message.appendChild(titre);

        const details = document.createElement('p');
        details.textContent =
            `Commande n° ${commande.id_commande} — ` +
            `${formatPrix(commande.montant_total)}.`;
        message.appendChild(details);

        const avertissement = document.createElement('p');
        avertissement.textContent =
            'Paiement simulé : aucune transaction bancaire réelle n’a été effectuée.';
        message.appendChild(avertissement);

        if (billets.length === 0) {
            const sansBillet = document.createElement('p');
            sansBillet.textContent =
                'La commande a été confirmée, mais aucun billet n’a été renvoyé par le serveur.';
            message.appendChild(sansBillet);
        } else {
            const titreBillets = document.createElement('h3');
            titreBillets.textContent = 'Tes billets';
            message.appendChild(titreBillets);

            for (const billet of billets) {
                message.appendChild(creerElementBillet(billet));
            }

            const lienProfil = document.createElement('a');
            lienProfil.className = 'button button-primary';
            lienProfil.href = '/profil-connecte.html';
            lienProfil.textContent = 'Retrouver mes billets dans mon profil';
            message.appendChild(lienProfil);
        }

        formulaire.hidden = true;
    } catch (erreur) {
        console.error('Erreur de confirmation :', erreur);
        message.textContent =
            erreur.message || 'La commande n’a pas pu être confirmée.';
        bouton.disabled = false;
    } finally {
        document.querySelector('#carte-code').value = '';
    }
}

function creerElementBillet(billet) {
    const carte = document.createElement('article');
    carte.className = 'carte-billet';

    const titre = document.createElement('h4');
    titre.textContent = billet.nom_soiree ?? 'Billet NRV';
    carte.appendChild(titre);

    const reference = document.createElement('p');
    reference.textContent = `Référence : ${billet.reference}`;
    carte.appendChild(reference);

    const tarif = document.createElement('p');
    tarif.textContent = `Tarif : ${libelleTarif(billet.categorie_tarif)}`;
    carte.appendChild(tarif);

    if (!billet.token) {
        const erreur = document.createElement('p');
        erreur.textContent = 'Le lien sécurisé du billet est indisponible.';
        carte.appendChild(erreur);
        return carte;
    }

    const urlBillet = new URL('/billet.html', window.location.origin);
    urlBillet.searchParams.set('token', billet.token);

    const lien = document.createElement('a');
    lien.href = urlBillet.href;
    lien.className = 'button button-primary';
    lien.textContent = 'Ouvrir et imprimer le billet';
    lien.target = '_blank';
    lien.rel = 'noopener noreferrer';
    carte.appendChild(lien);

    const qr = document.createElement('div');
    qr.className = 'billet-qr';
    qr.setAttribute('aria-label', `QR code du billet ${billet.reference}`);
    carte.appendChild(qr);

    if (typeof QRCode === 'undefined') {
        const indisponible = document.createElement('p');
        indisponible.textContent =
            'Le QR code est indisponible. Utilise le lien du billet.';
        carte.appendChild(indisponible);
    } else {
        new QRCode(qr, {
            text: urlBillet.href,
            width: 160,
            height: 160,
            correctLevel: QRCode.CorrectLevel.M,
        });
    }

    return carte;
}

async function requete(chemin, options = {}) {
    const reponse = await fetch(chemin, {
        ...options,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(options.headers ?? {}),
        },
    });

    const texte = await reponse.text();
    let resultat = {};

    if (texte) {
        try {
            resultat = JSON.parse(texte);
        } catch {
            throw new Error('Le serveur a renvoyé une réponse invalide.');
        }
    }

    if (!reponse.ok) {
        throw new Error(
            resultat.error
            ?? resultat.message
            ?? `Erreur HTTP ${reponse.status}`
        );
    }

    return resultat;
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

function formatPrix(montant) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(Number(montant) || 0);
}