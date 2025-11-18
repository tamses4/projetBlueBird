<?php
session_start();
require_once '../../config/db.php';
require_once '../../functions/update_voyage_status.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['client_id'];

// Récupérer les infos du client
$stmt = $pdo->prepare("SELECT nom_client, telephone, email FROM Client WHERE id_client = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Mettre à jour tous les statuts de voyage
foreach ($pdo->query("SELECT id_voyage FROM Voyage")->fetchAll(PDO::FETCH_COLUMN) as $id) {
    updateVoyageStatus($pdo, $id);
}

// Récupérer toutes les réservations du client
$stmt = $pdo->prepare("
    SELECT 
        r.id_reservation,
        r.date_reservation,
        r.statut_reservation,
        t.ville_depart,
        t.ville_arrivee,
        t.prix_base,
        v.date_depart,
        COALESCE(s.numero_siege, 'Non assigné') AS numero_siege,
        COALESCE(p.statut, 'en_attente') AS statut_paiement
    FROM Reservation r
    JOIN Voyage v ON r.id_voyage = v.id_voyage
    JOIN Trajet t ON v.id_trajet = t.id_trajet
    LEFT JOIN Billet b ON r.id_reservation = b.id_reservation
    LEFT JOIN Siege s ON b.id_siege = s.id_siege
    LEFT JOIN Paiement p ON r.id_reservation = p.id_reservation
    WHERE r.id_client = ?
    ORDER BY r.date_reservation DESC
");
$stmt->execute([$client_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Voyages - BusExpress</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary: #9c27b0;
            --success: #4CAF50;
            --warning: #FF9800;
            --danger: #f44336;
        }
        body { background: #f5f5f5; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .dashboard { max-width: 1200px; margin: 40px auto; padding: 20px; }

        .profile-card {
            background: linear-gradient(135deg, var(--primary), #e91e63);
            color: white; padding: 40px; border-radius: 20px; text-align: center;
            box-shadow: 0 15px 40px rgba(156,39,176,0.3); margin-bottom: 40px;
        }
        .profile-card h2 { margin: 0; font-size: 34px; }
        .edit-btn {
            background: rgba(255,255,255,0.25); padding: 14px 35px; border-radius: 50px;
            text-decoration: none; display: inline-block; margin-top: 15px; font-weight: bold;
            transition: 0.3s;
        }
        .edit-btn:hover { background: rgba(255,255,255,0.4); transform: translateY(-3px); }

        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 25px; margin: 40px 0; 
        }
        .stat-card { 
            background: white; padding: 35px; border-radius: 18px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; 
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-8px); }
        .stat-card h3 { margin: 0; font-size: 42px; color: var(--primary); font-weight: bold; }

        table { 
            width: 100%; background: white; border-radius: 18px; overflow: hidden;
            box-shadow: 0 12px 35px rgba(0,0,0,0.12); margin-top: 20px; 
        }
        th { background: #2c3e50; color: white; padding: 20px; font-size: 16px; }
        td { padding: 20px; text-align: center; border-bottom: 1px solid #eee; }
        tr:hover { background: #f8f9fa; }
        .badge { padding: 10px 18px; border-radius: 30px; font-size: 14px; font-weight: bold; color: white; }
        .paye { background: var(--success); }
        .attente { background: var(--warning); }
        .annule { background: var(--danger); }
        .pdf-btn { 
            background: var(--success); color: white; padding: 12px 24px; border-radius: 50px; 
            text-decoration: none; font-weight: bold; font-size: 14px; box-shadow: 0 5px 15px rgba(76,175,80,0.3);
            transition: 0.3s;
        }
        .pdf-btn:hover { transform: translateY(-3px); }

        .no-reservation { 
            text-align: center; padding: 100px 20px; background: white;
            border-radius: 25px; box-shadow: 0 15px 40px rgba(0,0,0,0.1); margin: 50px 0;
        }
        .big-btn {
            background: var(--primary); color: white; padding: 22px 60px; border-radius: 50px;
            text-decoration: none; font-size: 24px; font-weight: bold; display: inline-block;
            margin: 30px 0; box-shadow: 0 15px 40px rgba(156,39,176,0.5);
            transition: all 0.4s;
        }
        .big-btn:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 25px 60px rgba(156,39,176,0.6); 
        }

        /* NOUVEAU BOUTON EN HAUT À DROITE */
        .new-reservation-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #27ae60;
            color: white;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            font-size: 32px;
            text-align: center;
            line-height: 70px;
            box-shadow: 0 10px 30px rgba(39,174,96,0.5);
            z-index: 1000;
            transition: all 0.3s;
            text-decoration: none;
        }
        .new-reservation-btn:hover {
            transform: scale(1.15);
            background: #219653;
            box-shadow: 0 15px 40px rgba(39,174,96,0.7);
        }
    </style>
</head>
<body>

<!-- BOUTON FLOTTANT "NOUVELLE RÉSERVATION" -->
<a href="recherche_voyages.php" class="new-reservation-btn" title="Réserver un nouveau voyage">
    +
</a>

<div class="dashboard">

    <div class="profile-card">
        <h2>Bienvenue, <?= htmlspecialchars($client['nom_client'] ?? 'Client') ?> !</h2>
        <p><?= htmlspecialchars($client['telephone'] ?? '') ?> • <?= htmlspecialchars($client['email'] ?? '') ?></p>
        <a href="modifier_profil.php" class="edit-btn">Modifier mes informations</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <h3><?= count($reservations) ?></h3>
            <p>Total réservations</p>
        </div>
        <div class="stat-card">
            <h3><?= count(array_filter($reservations, fn($r) => $r['statut_paiement'] === 'paye')) ?></h3>
            <p>Voyages payés</p>
        </div>
        <div class="stat-card">
            <h3><?= count(array_filter($reservations, fn($r) => strtotime($r['date_depart']) < time())) ?></h3>
            <p>Voyages effectués</p>
        </div>
    </div>

    <h2 style="color:var(--primary); text-align:center; margin:50px 0 30px; font-size:32px;">
        Mes réservations
    </h2>

    <?php if (empty($reservations)): ?>
        <div class="no-reservation">
            <h3>Vous n'avez pas encore réservé de voyage</h3>
            <p>Parcourez nos trajets et réservez votre prochain déplacement en quelques clics !</p>
            <a href="recherche_voyages.php" class="big-btn">Réserver un voyage maintenant</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date réservation</th>
                    <th>Trajet</th>
                    <th>Départ</th>
                    <th>Siège</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Billet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $r): ?>
                    <tr <?= strtotime($r['date_depart']) < time() ? 'style="opacity:0.65;"' : '' ?>>
                        <td><?= date('d/m/Y H:i', strtotime($r['date_reservation'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($r['ville_depart']) ?> → <?= htmlspecialchars($r['ville_arrivee']) ?></strong>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($r['date_depart'])) ?><br>
                            
                        </td>
                        <td style="font-size:22px; font-weight:bold; color:var(--success);">
                            <?= htmlspecialchars($r['numero_siege']) ?>
                        </td>
                        <td><strong><?= number_format($r['prix_base'], 0, ',', ' ') ?> FCFA</strong></td>
                        <td>
                            <?php if ($r['statut_paiement'] === 'paye'): ?>
                                <span class="badge paye">Payé</span>
                            <?php elseif ($r['statut_reservation'] === 'annulée'): ?>
                                <span class="badge annule">Annulée</span>
                            <?php else: ?>
                                <span class="badge attente">En attente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['statut_paiement'] === 'paye'): ?>
                                <a href="imprimer_billet.php?id=<?= $r['id_reservation'] ?>" target="_blank" class="pdf-btn">
                                    Voir le billet
                                </a>
                            <?php else: ?>
                                <em style="color:#aaa;">—</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- BOUTON "RÉSERVER UN AUTRE VOYAGE" EN BAS AUSSI -->
        <div style="text-align:center; margin:60px 0;">
            <a href="recherche_voyages.php" class="big-btn">
                Réserver un autre voyage
            </a>
        </div>
    <?php endif; ?>

    <div style="text-align:center; margin:80px 0 20px;">
        <a href="logout.php" style="color:var(--danger); font-size:18px; font-weight:bold; text-decoration:none;">
            Déconnexion
        </a>
    </div>
</div>

</body>
</html>