<?php
require_once __DIR__ . '/../libs/fpdf182/fpdf.php';
include_once __DIR__ . '/../traitements/db.php';

if (!isset($_GET['id'])) die("ID etudiant manquant.");
$id_etudiant = intval($_GET['id']);

// Récupérer infos étudiant
$stmt = $conn->prepare("
    SELECT e.*, c.nom_classe, n.nom_niveau, c.annee_academique
    FROM etudiants e
    JOIN classes c ON e.id_classe = c.id_classe
    JOIN niveaux n ON c.id_niveau = n.id_niveau
    WHERE e.id_etudiant = ?
");
$stmt->execute([$id_etudiant]);
$etu = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$etu) die("Etudiant non trouve.");

// Récupérer TOUTES les evaluations de l'etudiant dans sa classe
$stmtEvals = $conn->prepare("
    SELECT m.nom_module, m.code_module, m.coefficient,
           t.nom_type,
           ev.note, ev.note_sur,
           ROUND(ev.note / ev.note_sur * 20, 2) AS note_sur_20,
           t.poids,
           t.inclus_dans_moyenne
    FROM evaluations ev
    JOIN modules m ON ev.id_module = m.id_module
    JOIN types_evaluation t ON ev.id_type_evaluation = t.id_type_evaluation
    WHERE ev.id_etudiant = ? AND ev.id_classe = ?
    ORDER BY m.nom_module, t.nom_type
");
$stmtEvals->execute([$id_etudiant, $etu['id_classe']]);
$evaluations = $stmtEvals->fetchAll(PDO::FETCH_ASSOC);

// Calcul moyenne generale (hors TP uniquement, devoirs et examens)
$total_pondere = 0;
$total_coeff   = 0;
foreach ($evaluations as $ev) {
    if ($ev['inclus_dans_moyenne']) {
        $note20 = $ev['note'] / $ev['note_sur'] * 20;
        $total_pondere += $note20 * $ev['poids'] * $ev['coefficient'];
        $total_coeff   += $ev['poids'] * $ev['coefficient'];
    }
}
$moyenne = $total_coeff > 0 ? round($total_pondere / $total_coeff, 2) : null;

// Decision
if ($moyenne === null) {
    $decision = "Non evalue";
} elseif ($moyenne >= 10) {
    $decision = "ADMIS";
} elseif ($moyenne >= 5) {
    $decision = "AJOURNE";
} else {
    $decision = "EXCLU";
}

// Helper: convert UTF-8 to Latin1 for FPDF
function u($str) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $str);
}

// ======= GENERATION PDF =======
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// ---- En-tête bleu ----
$pdf->SetFillColor(13, 110, 253);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 14, u('BULLETIN DE NOTES'), 0, 1, 'C', true);
$pdf->Ln(2);

// ---- Sous-titre ----
$pdf->SetFont('Arial', 'I', 11);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 7, u('Institut Groupe ISI - Annee Academique ' . $etu['annee_academique']), 0, 1, 'C');
$pdf->Ln(3);

// ---- Cadre info etudiant ----
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, u('Informations de l\'etudiant'), 0, 1, 'C', true);

$pdf->SetFillColor(236, 240, 241);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);

$pdf->SetFillColor(236, 240, 241);
$pdf->Cell(45, 7, u('Matricule :'), 'B', 0, 'L', true);
$pdf->Cell(55, 7, u($etu['matricule']), 'B', 0, 'L');
$pdf->Cell(40, 7, u('Classe :'), 'B', 0, 'L', true);
$pdf->Cell(0, 7, u($etu['nom_classe'] . ' - ' . $etu['nom_niveau']), 'B', 1, 'L');

$pdf->Cell(45, 7, u('Nom complet :'), 0, 0, 'L', true);
$pdf->Cell(55, 7, u($etu['nom'] . ' ' . $etu['prenom']), 0, 0, 'L');
$pdf->Cell(40, 7, u('Date naissance :'), 0, 0, 'L', true);
$pdf->Cell(0, 7, u(date('d/m/Y', strtotime($etu['date_naissance']))), 0, 1, 'L');

$pdf->Cell(45, 7, u('Sexe :'), 0, 0, 'L', true);
$pdf->Cell(55, 7, u($etu['sexe'] == 'M' ? 'Masculin' : 'Feminin'), 0, 0, 'L');
$pdf->Cell(40, 7, u('Lieu naissance :'), 0, 0, 'L', true);
$pdf->Cell(0, 7, u($etu['lieu_naissance'] ?? '-'), 0, 1, 'L');

$pdf->Ln(5);

// ---- En-tête tableau des notes ----
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(58, 9, u('Module'), 1, 0, 'C', true);
$pdf->Cell(16, 9, u('Code'), 1, 0, 'C', true);
$pdf->Cell(26, 9, u('Type eval.'), 1, 0, 'C', true);
$pdf->Cell(20, 9, u('Note'), 1, 0, 'C', true);
$pdf->Cell(16, 9, u('Sur'), 1, 0, 'C', true);
$pdf->Cell(20, 9, u('/20'), 1, 0, 'C', true);
$pdf->Cell(12, 9, u('Coef'), 1, 0, 'C', true);
$pdf->Cell(12, 9, u('Moy.'), 1, 1, 'C', true);

// ---- Lignes du tableau ----
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 9);
$fill = false;

if (empty($evaluations)) {
    $pdf->SetFillColor(255, 248, 220);
    $pdf->Cell(180, 8, u('Aucune evaluation enregistree pour cet etudiant.'), 1, 1, 'C', true);
} else {
    // Regrouper par module pour afficher une ligne de synthese par module
    $modules_data = [];
    foreach ($evaluations as $ev) {
        $key = $ev['code_module'];
        if (!isset($modules_data[$key])) {
            $modules_data[$key] = [
                'nom_module'  => $ev['nom_module'],
                'code_module' => $ev['code_module'],
                'coefficient' => $ev['coefficient'],
                'notes'       => []
            ];
        }
        $modules_data[$key]['notes'][] = $ev;
    }

    foreach ($modules_data as $mod) {
        $bg = $fill ? [235, 245, 255] : [255, 255, 255];
        $rowCount = count($mod['notes']);

        // Calculer moyenne du module (hors TP)
        $tp = 0; $tc = 0;
        foreach ($mod['notes'] as $n) {
            if ($n['inclus_dans_moyenne']) {
                $tp += ($n['note'] / $n['note_sur'] * 20) * $n['poids'];
                $tc += $n['poids'];
            }
        }
        $moy_module = $tc > 0 ? round($tp / $tc, 2) : null;

        foreach ($mod['notes'] as $i => $n) {
            $pdf->SetFillColor($bg[0], $bg[1], $bg[2]);

            // Colonne module seulement sur première ligne
            if ($i === 0) {
                $pdf->Cell(58, 7, u($mod['nom_module']), 'LR', 0, 'L', true);
            } else {
                $pdf->Cell(58, 7, '', 'LR', 0, 'L', true);
            }

            $pdf->Cell(16, 7, u($mod['code_module']), 'LR', 0, 'C', true);

            // Colorier TP en gris
            if (!$n['inclus_dans_moyenne']) {
                $pdf->SetFillColor(220, 220, 220);
            }
            $pdf->Cell(26, 7, u($n['nom_type']), 'LR', 0, 'C', $n['inclus_dans_moyenne'] ? true : true);
            $pdf->Cell(20, 7, number_format($n['note'], 2), 'LR', 0, 'C', $n['inclus_dans_moyenne'] ? true : true);
            $pdf->Cell(16, 7, number_format($n['note_sur'], 2), 'LR', 0, 'C', $n['inclus_dans_moyenne'] ? true : true);
            $pdf->Cell(20, 7, number_format($n['note_sur_20'], 2), 'LR', 0, 'C', $n['inclus_dans_moyenne'] ? true : true);
            $pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
            $pdf->Cell(12, 7, $mod['coefficient'], 'LR', 0, 'C', true);

            // Moyenne module seulement sur première ligne
            if ($i === 0) {
                if ($moy_module !== null) {
                    if ($moy_module >= 10) $pdf->SetFillColor(200, 240, 200);
                    elseif ($moy_module >= 5) $pdf->SetFillColor(255, 240, 180);
                    else $pdf->SetFillColor(255, 200, 200);
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->Cell(12, 7, number_format($moy_module, 2), 'LR', 1, 'C', true);
                    $pdf->SetFont('Arial', '', 9);
                } else {
                    $pdf->Cell(12, 7, '-', 'LR', 1, 'C', true);
                }
            } else {
                $pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
                $pdf->Cell(12, 7, '', 'LR', 1, 'C', true);
            }
        }

        // Ligne de séparation entre modules
        $pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
        $pdf->Cell(180, 0.3, '', 'T', 1, 'C', false);
        $fill = !$fill;
    }
}

$pdf->Ln(4);

// ---- Ligne moyenne generale ----
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(130, 11, u('MOYENNE GENERALE (Devoirs + Examens) :'), 1, 0, 'R', true);
$pdf->SetFillColor(26, 188, 156);
$pdf->Cell(50, 11, ($moyenne !== null ? number_format($moyenne, 2) . ' / 20' : 'N/A'), 1, 1, 'C', true);

// ---- Decision ----
$pdf->Ln(3);
if ($decision === 'ADMIS') {
    $pdf->SetFillColor(40, 167, 69);
} elseif ($decision === 'AJOURNE') {
    $pdf->SetFillColor(255, 152, 0);
} else {
    $pdf->SetFillColor(220, 53, 69);
}
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 13, u('DECISION : ' . $decision), 0, 1, 'C', true);

// ---- Note de bas ----
$pdf->SetTextColor(100, 100, 100);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Ln(4);
$pdf->Cell(0, 5, u('Note : Les TP ne sont pas inclus dans le calcul de la moyenne generale.'), 0, 1, 'C');
$pdf->Cell(0, 5, u('Les colonnes colorees en vert indiquent une note >= 10, orange >= 5, rouge < 5.'), 0, 1, 'C');
$pdf->Cell(0, 5, u('Document genere automatiquement le ' . date('d/m/Y a H:i')), 0, 1, 'C');

$pdf->Output('I', $etu['matricule'] . '_bulletin.pdf');
