<?php
include_once "../traitements/db.php";
// Récupérer toutes les classes
$classes = $conn->query("
    SELECT c.id_classe, c.nom_classe, n.id_niveau, n.nom_niveau
    FROM classes c
    JOIN niveaux n ON c.id_niveau = n.id_niveau
    ORDER BY n.ordre_niveau, c.nom_classe
")->fetchAll(PDO::FETCH_ASSOC);

$id_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : null;

$admis = $ajournes = $exclus = $non_evals = [];
$nom_classe_sel = '';

if ($id_classe) {
    // Nom de la classe
    foreach ($classes as $c) {
        if ($c['id_classe'] == $id_classe) $nom_classe_sel = $c['nom_classe'] . ' ('.$c['nom_niveau'].')';
    }

    // Calculer la moyenne de chaque étudiant de la classe
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
    ");
    $stmt->execute([$id_classe, $id_classe]);
    $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($etudiants as $e) {
        if ($e['moyenne'] === null) {
            $non_evals[] = $e;
        } elseif ($e['moyenne'] >= 10) {
            $admis[] = $e;
        } elseif ($e['moyenne'] >= 5) {
            $ajournes[] = $e;
        } else {
            $exclus[] = $e;
        }
    }
}

// Stats globales par niveau
$stats_niveaux = $conn->query("
    SELECT n.nom_niveau, 
           COUNT(DISTINCT c.id_classe) AS nb_classes,
           COUNT(DISTINCT e.id_etudiant) AS nb_etudiants
    FROM niveaux n
    LEFT JOIN classes c ON n.id_niveau = c.id_niveau
    LEFT JOIN etudiants e ON c.id_classe = e.id_classe
    GROUP BY n.id_niveau, n.nom_niveau
    ORDER BY n.ordre_niveau
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>
<div class="container mt-4">
    <h2>Statistiques Académiques</h2>

    <!-- Stats globales par niveau -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Répartition par niveau</div>
        <div class="card-body table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr><th>Niveau</th><th class="text-center">Classes</th><th class="text-center">Étudiants</th></tr>
                </thead>
                <tbody>
                    <?php foreach($stats_niveaux as $sn): ?>
                    <tr>
                        <td><?= htmlspecialchars($sn['nom_niveau']) ?></td>
                        <td class="text-center"><?= $sn['nb_classes'] ?></td>
                        <td class="text-center"><?= $sn['nb_etudiants'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sélection Classe -->
    <form method="GET" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Choisir une classe pour le tableau de bord détaillé :</label>
                <select name="id_classe" class="form-select" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id_classe'] ?>" <?= $id_classe == $c['id_classe'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom_classe'].' ('.$c['nom_niveau'].')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Afficher</button>
            </div>
        </div>
    </form>

<?php if ($id_classe): ?>

    <h4>Tableau de bord — Classe <?= htmlspecialchars($nom_classe_sel) ?></h4>

    <!-- Résumé chiffré -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success text-center p-3 bg-success bg-opacity-10">
                <h3 class="text-success"><?= count($admis) ?></h3>
                <p class="mb-0 fw-bold">Admis</p>
                <small>moy ≥ 10</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning text-center p-3 bg-warning bg-opacity-10">
                <h3 class="text-warning"><?= count($ajournes) ?></h3>
                <p class="mb-0 fw-bold">Ajournés</p>
                <small>5 ≤ moy < 10</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger text-center p-3 bg-danger bg-opacity-10">
                <h3 class="text-danger"><?= count($exclus) ?></h3>
                <p class="mb-0 fw-bold">Exclus</p>
                <small>moy < 5</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary text-center p-3">
                <h3 class="text-secondary"><?= count($non_evals) ?></h3>
                <p class="mb-0 fw-bold">Non évalués</p>
            </div>
        </div>
    </div>

    <?php
    function afficherTableauEtudiants($liste, $titre, $classe_css) {
        if (empty($liste)) return;
        echo '<div class="card mb-4">';
        echo '<div class="card-header '.$classe_css.' text-white">'.$titre.'</div>';
        echo '<div class="card-body table-responsive">';
        echo '<table class="table table-striped mb-0">';
        echo '<thead><tr><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Moyenne</th><th>Bulletin</th></tr></thead>';
        echo '<tbody>';
        foreach ($liste as $e) {
            echo '<tr>';
            echo '<td>'.htmlspecialchars($e['matricule']).'</td>';
            echo '<td>'.htmlspecialchars($e['nom']).'</td>';
            echo '<td>'.htmlspecialchars($e['prenom']).'</td>';
            $moy = $e['moyenne'] !== null ? $e['moyenne'].'/20' : 'N/A';
            echo '<td><strong>'.$moy.'</strong></td>';
            echo '<td><a href="bulltin.php?id='.$e['id_etudiant'].'" class="btn btn-sm btn-success" target="_blank">Bulletin</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div></div>';
    }

    afficherTableauEtudiants($admis, '✅ Admis ('.count($admis).')', 'bg-success');
    afficherTableauEtudiants($ajournes, '⚠️ Ajournés ('.count($ajournes).')', 'bg-warning');
    afficherTableauEtudiants($exclus, '❌ Exclus ('.count($exclus).')', 'bg-danger');

    if (!empty($non_evals)):
    ?>
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Non évalués (<?= count($non_evals) ?>)</div>
        <div class="card-body table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Matricule</th><th>Nom</th><th>Prénom</th></tr></thead>
                <tbody>
                    <?php foreach($non_evals as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['matricule']) ?></td>
                        <td><?= htmlspecialchars($e['nom']) ?></td>
                        <td><?= htmlspecialchars($e['prenom']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include_once "../includes/footer.php"; ?>
