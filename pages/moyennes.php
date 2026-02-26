<?php
include_once __DIR__ . '/../traitements/db.php';
// Récupérer toutes les classes
$classes = $conn->query("
    SELECT c.id_classe, c.nom_classe, n.nom_niveau, n.id_niveau
    FROM classes c
    JOIN niveaux n ON c.id_niveau = n.id_niveau
    ORDER BY n.ordre_niveau, c.nom_classe
")->fetchAll(PDO::FETCH_ASSOC);

$id_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : null;

$etudiants_moyennes = [];
$moyenne_generale_classe = null;
$meilleur_classe = null;
$meilleur_niveau = null;
$superieurs = [];
$nom_classe_sel = '';
$id_niveau_sel = null;

if ($id_classe) {
    // Infos classe sélectionnée
    $info_classe = $conn->prepare("
        SELECT c.nom_classe, c.id_niveau, n.nom_niveau 
        FROM classes c JOIN niveaux n ON c.id_niveau = n.id_niveau 
        WHERE c.id_classe = ?
    ");
    $info_classe->execute([$id_classe]);
    $info_classe = $info_classe->fetch(PDO::FETCH_ASSOC);
    $nom_classe_sel = $info_classe['nom_classe'] ?? '';
    $id_niveau_sel = $info_classe['id_niveau'] ?? null;

    // Moyenne de chaque étudiant de la classe (hors TP, uniquement devoirs et examens)
    $stmt = $conn->prepare("
        SELECT e.id_etudiant, e.matricule, e.nom, e.prenom,
            ROUND(
                SUM(CASE WHEN te.inclus_dans_moyenne = 1 THEN (ev.note / ev.note_sur * 20) * te.poids * m.coefficient ELSE 0 END)
                / NULLIF(SUM(CASE WHEN te.inclus_dans_moyenne = 1 THEN te.poids * m.coefficient ELSE 0 END), 0),
            2) AS moyenne
        FROM etudiants e
        LEFT JOIN evaluations ev ON e.id_etudiant = ev.id_etudiant AND ev.id_classe = ?
        LEFT JOIN types_evaluation te ON ev.id_type_evaluation = te.id_type_evaluation
        LEFT JOIN modules m ON ev.id_module = m.id_module
        WHERE e.id_classe = ?
        GROUP BY e.id_etudiant, e.matricule, e.nom, e.prenom
        ORDER BY moyenne DESC
    ");
    $stmt->execute([$id_classe, $id_classe]);
    $etudiants_moyennes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Moyenne générale de la classe
    $avg_stmt = $conn->prepare("
        SELECT ROUND(AVG(moy.moyenne), 2) AS moy_classe FROM (
            SELECT
                ROUND(
                    SUM(CASE WHEN te.inclus_dans_moyenne = 1 THEN (ev.note / ev.note_sur * 20) * te.poids * m.coefficient ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN te.inclus_dans_moyenne = 1 THEN te.poids * m.coefficient ELSE 0 END), 0),
                2) AS moyenne
            FROM etudiants e
            LEFT JOIN evaluations ev ON e.id_etudiant = ev.id_etudiant AND ev.id_classe = ?
            LEFT JOIN types_evaluation te ON ev.id_type_evaluation = te.id_type_evaluation
            LEFT JOIN modules m ON ev.id_module = m.id_module
            WHERE e.id_classe = ?
            GROUP BY e.id_etudiant
            HAVING moyenne IS NOT NULL
        ) AS moy
    ");
    $avg_stmt->execute([$id_classe, $id_classe]);
    $moyenne_generale_classe = $avg_stmt->fetchColumn();

    // Meilleur étudiant de la classe
    $meilleur_classe = !empty($etudiants_moyennes) ? $etudiants_moyennes[0] : null;

    // Meilleur étudiant du niveau
    if ($id_niveau_sel) {
        $stmt_best_niveau = $conn->prepare("
            SELECT e.nom, e.prenom, e.matricule, c.nom_classe,
                ROUND(
                    SUM(CASE WHEN te.inclus_dans_moyenne = 1 THEN (ev.note / ev.note_sur * 20) * te.poids * m.coefficient ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN te.inclus_dans_moyenne = 1 THEN te.poids * m.coefficient ELSE 0 END), 0),
                2) AS moyenne
            FROM etudiants e
            JOIN classes c ON e.id_classe = c.id_classe
            LEFT JOIN evaluations ev ON e.id_etudiant = ev.id_etudiant AND ev.id_classe = e.id_classe
            LEFT JOIN types_evaluation te ON ev.id_type_evaluation = te.id_type_evaluation
            LEFT JOIN modules m ON ev.id_module = m.id_module
            WHERE c.id_niveau = ?
            GROUP BY e.id_etudiant, e.nom, e.prenom, e.matricule, c.nom_classe
            HAVING moyenne IS NOT NULL
            ORDER BY moyenne DESC
            LIMIT 1
        ");
        $stmt_best_niveau->execute([$id_niveau_sel]);
        $meilleur_niveau = $stmt_best_niveau->fetch(PDO::FETCH_ASSOC);
    }

    // Étudiants au-dessus de la moyenne de la classe
    if ($moyenne_generale_classe !== null) {
        foreach ($etudiants_moyennes as $etu) {
            if ($etu['moyenne'] !== null && $etu['moyenne'] > $moyenne_generale_classe) {
                $superieurs[] = $etu;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Moyennes - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>
<div class="container py-4">
    <h2>Calcul des Moyennes</h2>
    <p class="text-muted">Les moyennes sont calculées uniquement à partir des Devoirs et Examens (les TP sont exclus).</p>

    <!-- Sélection classe -->
    <form method="GET" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Choisir une classe</label>
                <select name="id_classe" class="form-select" required>
                    <option value="">-- Sélectionner une classe --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id_classe'] ?>" <?= $id_classe == $c['id_classe'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom_classe'].' ('.$c['nom_niveau'].')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Afficher</button>
            </div>
        </div>
    </form>

    <?php if ($id_classe && !empty($etudiants_moyennes)): ?>

    <!-- Résumé -->
    <div class="row g-3 mb-4">
        <?php if ($meilleur_classe && $meilleur_classe['moyenne'] !== null): ?>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center bg-success bg-opacity-10">
                    <i class="bi bi-trophy-fill text-warning fs-2"></i>
                    <h6 class="mt-2"> Meilleur étudiant (classe)</h6>
                    <strong><?= htmlspecialchars($meilleur_classe['nom'].' '.$meilleur_classe['prenom']) ?></strong><br>
                    <span class="badge bg-success fs-6"><?= $meilleur_classe['moyenne'] ?>/20</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($meilleur_niveau): ?>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center bg-info bg-opacity-10">
                    <i class="bi bi-award-fill text-info fs-2"></i>
                    <h6 class="mt-2"> Meilleur étudiant (niveau)</h6>
                    <strong><?= htmlspecialchars($meilleur_niveau['nom'].' '.$meilleur_niveau['prenom']) ?></strong><br>
                    <small><?= htmlspecialchars($meilleur_niveau['nom_classe']) ?></small><br>
                    <span class="badge bg-info fs-6"><?= $meilleur_niveau['moyenne'] ?>/20</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body text-center bg-primary bg-opacity-10">
                    <i class="bi bi-calculator-fill text-primary fs-2"></i>
                    <h6 class="mt-2">Moyenne générale de la classe</h6>
                    <span class="badge bg-primary fs-5">
                        <?= $moyenne_generale_classe !== null ? $moyenne_generale_classe.'/20' : 'N/A' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des étudiants avec moyennes -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Moyennes des étudiants — Classe <?= htmlspecialchars($nom_classe_sel) ?>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Rang</th>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Moyenne /20</th>
                        <th>Décision</th>
                        <th>Bulletin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rang = 1; foreach($etudiants_moyennes as $etu): ?>
                        <?php
                        $moy = $etu['moyenne'];
                        if ($moy === null) {
                            $badge = '<span class="badge bg-secondary">Non évalué</span>';
                        } elseif ($moy >= 10) {
                            $badge = '<span class="badge bg-success">Admis</span>';
                        } elseif ($moy >= 5) {
                            $badge = '<span class="badge bg-warning text-dark">Ajourné</span>';
                        } else {
                            $badge = '<span class="badge bg-danger">Exclu</span>';
                        }
                        ?>
                        <tr>
                            <td><?= $rang++ ?></td>
                            <td><?= htmlspecialchars($etu['matricule']) ?></td>
                            <td><?= htmlspecialchars($etu['nom']) ?></td>
                            <td><?= htmlspecialchars($etu['prenom']) ?></td>
                            <td class="fw-bold"><?= $moy !== null ? $moy : 'N/A' ?></td>
                            <td><?= $badge ?></td>
                            <td>
                                <a href="bulltin.php?id=<?= $etu['id_etudiant'] ?>" class="btn btn-sm btn-success" target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i> Bulletin
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Étudiants au-dessus de la moyenne de la classe -->
    <?php if (!empty($superieurs)): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            Étudiants au-dessus de la moyenne de la classe (<?= $moyenne_generale_classe ?>/20)
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Moyenne</th></tr>
                </thead>
                <tbody>
                    <?php foreach($superieurs as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['matricule']) ?></td>
                        <td><?= htmlspecialchars($s['nom']) ?></td>
                        <td><?= htmlspecialchars($s['prenom']) ?></td>
                        <td class="fw-bold text-success"><?= $s['moyenne'] ?>/20</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($id_classe): ?>
        <div class="alert alert-info">Aucun étudiant trouvé dans cette classe ou aucune évaluation enregistrée.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>
