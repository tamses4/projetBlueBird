// assets/js/search.js
document.addEventListener('DOMContentLoaded', function () {
    const resultats = document.getElementById('resultats');
    const villeDepart = document.getElementById('ville_depart');
    const villeArrivee = document.getElementById('ville_arrivee');
    const dateDepart = document.getElementById('date_depart');
    const prixMax = document.getElementById('prix_max');
    const btn = document.getElementById('btn-search');

    // Si on est pas sur la page de recherche → on sort direct (aucune erreur)
    if (!resultats || !btn) {
        return;
    }

    function rechercher() {
        const params = new URLSearchParams({
            depart: (villeDepart?.value || ''),
            arrivee: (villeArrivee?.value || ''),
            date: (dateDepart?.value || ''),
            prix: (prixMax?.value || '')
        });

        resultats.innerHTML = '<div class="loading">Recherche en cours...</div>';

        fetch(`traitement_recherche.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau');
                return response.text();
            })
            .then(html => {
                resultats.innerHTML = html;
            })
            .catch(err => {
                resultats.innerHTML = `<div class="error-alert">Erreur : ${err.message}</div>`;
            });
    }

    // Événements seulement si les éléments existent
    btn.addEventListener('click', rechercher);

    [villeDepart, villeArrivee, dateDepart, prixMax].forEach(el => {
        if (el) el.addEventListener('input', rechercher);
    });

    // Recherche automatique au chargement
    rechercher();
});