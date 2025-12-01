<?php
require_once '../../tcpdf/tcpdf.php';
include '../../config/db.php';

$id_reservation = $_GET['id'] ?? null;
if (!$id_reservation || !is_numeric($id_reservation)) {
    die("ID de réservation invalide.");
}

// Récupérer les données + vérifier le statut paiement
$stmt = $pdo->prepare("
    SELECT 
        r.id_reservation, r.statut_reservation,
        c.nom_complet, c.email, c.telephone,
        t.ville_depart, t.ville_arrivee,
        v.date_depart, v.date_arrivee,
        b.immatriculation,
        s.numero_siege,
        bi.montant,
        p.statut as statut_paiement
    FROM reservation r
    JOIN client c ON r.id_client = c.id_client
    JOIN voyage v ON r.id_voyage = v.id_voyage
    JOIN trajet t ON v.id_trajet = t.id_trajet
    JOIN billet bi ON r.id_reservation = bi.id_reservation
    JOIN paiement p ON r.id_reservation = p.id_reservation
    JOIN siege s ON bi.id_siege = s.id_siege
    JOIN bus b ON s.id_bus = b.id_bus
    WHERE r.id_reservation = ? 
      AND r.statut_reservation = 'confirmée'
      AND p.statut = 'paye'
");
$stmt->execute([$id_reservation]);
$billet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$billet) {
    die("Billet non disponible : paiement non effectué ou réservation invalide.");
}

// Code unique
$code_billet = "BUS" . str_pad($id_reservation, 6, '0', STR_PAD_LEFT);

ob_clean();

// === PDF ===
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('BusExpress');
$pdf->SetTitle('Billet - ' . $code_billet);
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 26);
$pdf->SetTextColor(156, 39, 176);
$pdf->Cell(0, 15, 'BILLET DE VOYAGE', 0, 1, 'C');
$pdf->Ln(8);

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetFillColor(240, 240, 255);
$pdf->Cell(0, 12, "Code : $code_billet", 0, 1, 'C', true);
$pdf->Ln(10);

$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, $billet['ville_depart'] . ' → ' . $billet['ville_arrivee'], 0, 1, 'C');
$pdf->Ln(15);

$html = '
<style>
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { background-color: #f0f0f0; padding: 12px; text-align: left; }
    td { padding: 12px; border-bottom: 1px solid #ddd; }
    .label { font-weight: bold; color: #2c3e50; }
    .value { color: #34495e; }
    .siege { font-size: 28px; font-weight: bold; color: #27ae60; text-align: center; }
</style>

<table>
    <tr>
        <th width="50%"><span class="label">Passager</span></th>
        <th width="50%"><span class="label">Contact</span></th>
    </tr>
    <tr>
        <td>' . htmlspecialchars($billet['nom_complet']) . '</td>
        <td>' . $billet['telephone'] . '<br>' . $billet['email'] . '</td>
    </tr>

    <tr><th><span class="label">Départ</span></th><th><span class="label">Arrivée prévue</span></th></tr>
    <tr>
        <td>' . date('d/m/Y à H:i', strtotime($billet['date_depart'])) . '</td>
        <td>' . date('d/m/Y à H:i', strtotime($billet['date_arrivee'])) . '</td>
    </tr>

    <tr><th><span class="label">Bus</span></th><th><span class="label">Siège</span></th></tr>
    <tr>
        <td>' . $billet['immatriculation'] . '</td>
        <td class="siege">' . $billet['numero_siege'] . '</td>
    </tr>

    <tr>
        <th colspan="2"><span class="label">Prix payé</span></th>
    </tr>
    <tr>
        <td colspan="2" style="text-align:center; font-size:20px; color:#27ae60;">
            <strong>' . number_format($billet['montant'], 0, ',', ' ') . ' FCFA</strong>
        </td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(20);

$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 10, 'BusExpress Cameroun - Merci pour votre confiance', 0, 1, 'C');
$pdf->Cell(0, 10, 'Présentez ce billet + pièce d\'identité à l\'embarquement', 0, 0, 'C');

$pdf->Output('billet_' . $code_billet . '.pdf', 'I');
exit;
?>