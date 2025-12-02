<?php
session_start();
include '../../config/db.php';
include '../../includes/header_public1.php';
include '../../functions/update_voyage_status.php';

$id_voyage = $_GET['voyage'] ?? null;
$client_connecte = isset($_SESSION['client_id']);
$client_id = $client_connecte ? $_SESSION['client_id'] : null;

if (!$id_voyage || !is_numeric($id_voyage)) {
    die("<div class='error-alert'>Voyage invalide.</div>");
}

// Mettre à jour le statut du voyage
updateVoyageStatus($pdo, $id_voyage);

// Récupérer les infos du voyage
$stmt = $pdo->prepare("
    SELECT v.*, t.ville_depart, t.ville_arrivee, t.prix_base, 
           b.immatriculation, b.nombre_place, a.nom_agence
    FROM voyage v
    JOIN trajet t ON v.id_trajet = t.id_trajet
    JOIN bus b ON v.id_bus = b.id_bus
    JOIN agence a ON v.id_agence = a.id_agence
    WHERE v.id_voyage = ? AND v.statut_voyage = 'Disponible'
");
$stmt->execute([$id_voyage]);
$voyage = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voyage) {
    die("<div class='error-alert'>Voyage indisponible ou complet.</div>");
}

// CORRECTION ICI : Compter proprement les places réservées
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM billet bi
    JOIN reservation r ON bi.id_reservation = r.id_reservation
    WHERE r.id_voyage = ? AND r.statut_reservation != 'annulée'
");
$stmt->execute([$id_voyage]);
$places_reservees = (int)$stmt->fetchColumn();

$places_libres = $voyage['nombre_place'] - $places_reservees;

if ($places_libres <= 0) {
    die("<div class='error-alert'>Désolé, ce voyage est complet !</div>");
}

// Récupérer les sièges
$stmt = $pdo->prepare("
    SELECT s.id_siege, s.numero_siege,
           CASE WHEN bi.id_billet IS NOT NULL THEN 1 ELSE 0 END as reserve
    FROM siege s
    LEFT JOIN billet bi ON s.id_siege = bi.id_siege
    LEFT JOIN reservation r ON bi.id_reservation = r.id_reservation 
           AND r.id_voyage = ? AND r.statut_reservation != 'annulée'
    WHERE s.id_bus = ?
    ORDER BY s.numero_siege
");
$stmt->execute([$id_voyage, $voyage['id_bus']]);
$sieges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les infos du client connecté (si connecté)
$client_info = null;
if ($client_connecte) {
    $stmt = $pdo->prepare("SELECT nom_client, telephone, email FROM client WHERE id_client = ?");
    $stmt->execute([$client_id]);
    $client_info = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="form-container">
    <h2>Réserver un Siège</h2>

    <!-- RÉSUMÉ DU VOYAGE -->
    <div class="voyage-summary">
        <h3><?= htmlspecialchars($voyage['ville_depart']) ?> → <?= htmlspecialchars($voyage['ville_arrivee']) ?></h3>
        <p><strong>Départ :</strong> <?= date('d/m/Y à H:i', strtotime($voyage['date_depart'])) ?></p>
        <p><strong>Bus :</strong> <?= htmlspecialchars($voyage['immatriculation']) ?> (<?= $voyage['nombre_place'] ?> places)</p>
        <p><strong>Places libres :</strong> <span class="places-count"><?= $places_libres ?></span></p>
        <p><strong>Prix :</strong> <span class="price"><?= number_format($voyage['prix_base'], 0, ',', ' ') ?> FCFA</span></p>
    </div>

    <!-- PLAN DE SIÈGES -->
    <h3 class="section-title">Choisissez votre siège</h3>
    <div id="plan-sieges">
        <?php foreach ($sieges as $s): ?>
            <div class="siege <?= $s['reserve'] ? 'occupe' : 'libre' ?>"
                 data-siege="<?= $s['id_siege'] ?>"
                 data-numero="<?= $s['numero_siege'] ?>">
                <?= $s['numero_siege'] ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- FORMULAIRE AUTO-REMPLI -->
    <form id="form-reservation" class="reservation-form" style="display:none;">
        <input type="hidden" name="id_voyage" value="<?= $id_voyage ?>">
        <input type="hidden" name="id_siege" id="id_siege">
        <?php if ($client_connecte): ?>
            <input type="hidden" name="client_connecte" value="1">
            <input type="hidden" name="id_client" value="<?= $client_id ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Nom complet</label>
            <input type="text" name="nom_client" class="form-control" 
                   value="<?= htmlspecialchars($client_info['nom_client'] ?? '') ?>" 
                   <?= $client_connecte ? 'readonly style="background:#f0f0f0; border:1px solid #ccc;"' : 'required' ?>>
        </div>

        <div class="form-group">
            <label>Téléphone</label>
            <input type="tel" name="telephone" class="form-control" 
                   value="<?= htmlspecialchars($client_info['telephone'] ?? '') ?>" 
                   <?= $client_connecte ? 'readonly style="background:#f0f0f0; border:1px solid #ccc;"' : 'required' ?>>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email_client" class="form-control" 
                   value="<?= htmlspecialchars($client_info['email'] ?? '') ?>" 
                   <?= $client_connecte ? 'readonly style="background:#f0f0f0; border:1px solid #ccc;"' : 'required' ?>>
        </div>

        <?php if ($client_connecte): ?>
            <div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin:20px 0; font-weight:bold;">
                Vos informations sont pré-remplies car vous êtes connecté
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Confirmer la réservation</button>
            <button type="button" id="annuler" class="btn-secondary">Annuler sélection</button>
        </div>
    </form>

    <div id="message"></div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sieges = document.querySelectorAll('.siege.libre');
    const form = document.getElementById('form-reservation');
    const idSiegeInput = document.getElementById('id_siege');
    const message = document.getElementById('message');

    sieges.forEach(siege => {
        siege.addEventListener('click', function() {
            document.querySelectorAll('.siege').forEach(s => s.classList.remove('selectionne'));
            this.classList.add('selectionne');
            idSiegeInput.value = this.dataset.siege;
            form.style.display = 'block';
            message.innerHTML = `<div class="success-alert">Siège <strong>${this.dataset.numero}</strong> sélectionné !</div>`;
        });
    });

    document.getElementById('annuler').addEventListener('click', () => {
        form.style.display = 'none';
        document.querySelectorAll('.siege').forEach(s => s.classList.remove('selectionne'));
        message.innerHTML = '';
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('traitement_reservation.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(data => {
            message.innerHTML = data;
            if (data.includes('confirmée') || data.includes('succès')) {
                setTimeout(() => location.href = 'dashboard.php', 3000);
            }
        });
    });
});
</script>