<?php
include_once __DIR__ . '/../traitements/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once __DIR__ . '/navbar.php'; ?>

<?php
// Statistiques globales
$sql = "
SELECT 
    (SELECT COUNT(*) FROM etudiants) AS total_etudiants,
    (SELECT COUNT(*) FROM classes) AS total_classes,
    (SELECT COUNT(*) FROM modules) AS total_modules,
    (SELECT COUNT(*) FROM niveaux) AS total_niveaux,
    
    (
        SELECT COUNT(*) 
        FROM (
            SELECT ev.id_etudiant
            FROM evaluations ev
            JOIN types_evaluation te ON ev.id_type_evaluation = te.id_type_evaluation
            WHERE te.inclus_dans_moyenne = 1
            GROUP BY ev.id_etudiant
            HAVING AVG(ev.note / ev.note_sur * 20) >= 10
        ) AS admis
    ) AS total_admis,
    
    (
        SELECT COUNT(*) 
        FROM (
            SELECT ev.id_etudiant
            FROM evaluations ev
            JOIN types_evaluation te ON ev.id_type_evaluation = te.id_type_evaluation
            WHERE te.inclus_dans_moyenne = 1
            GROUP BY ev.id_etudiant
            HAVING AVG(ev.note / ev.note_sur * 20) >= 5 AND AVG(ev.note / ev.note_sur * 20) < 10
        ) AS ajournes
    ) AS total_ajournes,
    
    (
        SELECT COUNT(*) 
        FROM (
            SELECT ev.id_etudiant
            FROM evaluations ev
            JOIN types_evaluation te ON ev.id_type_evaluation = te.id_type_evaluation
            WHERE te.inclus_dans_moyenne = 1
            GROUP BY ev.id_etudiant
            HAVING AVG(ev.note / ev.note_sur * 20) < 5
        ) AS exclus
    ) AS total_exclus
";

$result = $conn->query($sql);
$stats = $result->fetch(PDO::FETCH_ASSOC);
$stats['taux_reussite'] = 0;
if ($stats['total_etudiants'] > 0) {
    $stats['taux_reussite'] = ($stats['total_admis'] / $stats['total_etudiants']) * 100;
}

$stats_niveaux = $conn->query("
    SELECT n.nom_niveau, COUNT(DISTINCT c.id_classe) AS nb_classes,
           COUNT(DISTINCT e.id_etudiant) AS nb_etudiants
    FROM niveaux n
    LEFT JOIN classes c ON n.id_niveau = c.id_niveau
    LEFT JOIN etudiants e ON c.id_classe = e.id_classe
    GROUP BY n.id_niveau, n.nom_niveau
    ORDER BY n.ordre_niveau
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Tableau de bord</h2>
            <p class="text-muted">Vue d'ensemble - Statistiques académiques</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill text-primary fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_etudiants'] ?></h3>
                    <p class="text-muted mb-0">Étudiants</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="bi bi-door-open-fill text-info fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_classes'] ?></h3>
                    <p class="text-muted mb-0">Classes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="bi bi-book-fill text-warning fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_modules'] ?></h3>
                    <p class="text-muted mb-0">Modules</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <i class="bi bi-layers-fill text-secondary fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_niveaux'] ?></h3>
                    <p class="text-muted mb-0">Niveaux</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center bg-success bg-opacity-10">
                    <i class="bi bi-check-circle-fill text-success fs-2"></i>
                    <h4 class="text-success mt-2"><?= $stats['total_admis'] ?></h4>
                    <p class="mb-0 fw-bold">Admis (moy ≥ 10)</p>
                    <small class="text-muted"><?= number_format($stats['taux_reussite'], 1) ?>% des étudiants</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center bg-warning bg-opacity-10">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-2"></i>
                    <h4 class="text-warning mt-2"><?= $stats['total_ajournes'] ?></h4>
                    <p class="mb-0 fw-bold">Ajournés (5 ≤ moy < 10)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center bg-danger bg-opacity-10">
                    <i class="bi bi-x-circle-fill text-danger fs-2"></i>
                    <h4 class="text-danger mt-2"><?= $stats['total_exclus'] ?></h4>
                    <p class="mb-0 fw-bold">Exclus (moy < 5)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">Statistiques par niveau</div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Niveau</th>
                                <th class="text-center">Nombre de classes</th>
                                <th class="text-center">Nombre d'étudiants</th>
                            </tr>
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
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12"><h5>Actions rapides</h5></div>
        <div class="col-md-2 mb-2"><a href="/Gestion_Academique/pages/ajouter_niveau.php" class="btn btn-outline-primary w-100"><i class="bi bi-plus"></i> Niveau</a></div>
        <div class="col-md-2 mb-2"><a href="/Gestion_Academique/pages/ajouter_classe.php" class="btn btn-outline-info w-100"><i class="bi bi-plus"></i> Classe</a></div>
        <div class="col-md-2 mb-2"><a href="/Gestion_Academique/pages/ajouter_etudiant.php" class="btn btn-outline-success w-100"><i class="bi bi-person-plus"></i> Étudiant</a></div>
        <div class="col-md-2 mb-2"><a href="/Gestion_Academique/pages/evaluations.php" class="btn btn-outline-warning w-100"><i class="bi bi-plus"></i> Évaluation</a></div>
        <div class="col-md-2 mb-2"><a href="/Gestion_Academique/pages/moyennes.php" class="btn btn-outline-secondary w-100"><i class="bi bi-calculator"></i> Moyennes</a></div>
        <div class="col-md-2 mb-2"><a href="/Gestion_Academique/pages/statistiques.php" class="btn btn-outline-dark w-100"><i class="bi bi-pie-chart"></i> Stats</a></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include_once __DIR__ . '/footer.php'; ?>
