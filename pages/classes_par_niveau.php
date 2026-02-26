<?php
include_once __DIR__ . '/../traitements/db.php';
// Messages
$success_msg = '';
$error_msg = '';

// Ajouter une classe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_classe = trim($_POST['nom_classe']);
    $id_niveau = trim($_POST['id_niveau']);
    $annee_academique = trim($_POST['annee_academique']);
    $effectif_max = trim($_POST['effectif_max']);

    if (empty($nom_classe) || empty($id_niveau) || empty($annee_academique)) {
        $error_msg = "Tous les champs sauf effectif sont obligatoires.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO classes (nom_classe, id_niveau, annee_academique, effectif_max) 
                                    VALUES (:nom, :id_niveau, :annee, :effectif)");
            $stmt->bindParam(':nom', $nom_classe);
            $stmt->bindParam(':id_niveau', $id_niveau);
            $stmt->bindParam(':annee', $annee_academique);
            $stmt->bindParam(':effectif', $effectif_max);
            $stmt->execute();
            $success_msg = "Classe '$nom_classe' ajoutée avec succès !";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_msg = "Cette classe existe déjà pour ce niveau et cette année.";
            } else {
                $error_msg = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// Récupérer tous les niveaux
$niveau_stmt = $conn->query("SELECT * FROM niveaux ORDER BY ordre_niveau ASC");
$niveaux = $niveau_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer toutes les classes avec niveau
$classes_stmt = $conn->query("
    SELECT c.*, n.nom_niveau 
    FROM classes c
    INNER JOIN niveaux n ON c.id_niveau = n.id_niveau
    ORDER BY n.ordre_niveau, c.nom_classe
");
$classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Regrouper les classes par niveau
$classes_par_niveau = [];
foreach ($classes as $classe) {
    $classes_par_niveau[$classe['nom_niveau']][] = $classe;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes par niveau - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>
<div class="container py-5">
    <h2>Classes par Niveau</h2>
    <p class="text-muted">Visualisez toutes les classes regroupées par niveau et ajoutez-en de nouvelles.</p>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- Formulaire d'ajout de classe -->
    <div class="card p-4 mb-4">
        <h5>Ajouter une nouvelle classe</h5>
        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nom_classe" class="form-label">Nom de la classe</label>
                    <input type="text" name="nom_classe" id="nom_classe" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="id_niveau" class="form-label">Niveau</label>
                    <select name="id_niveau" id="id_niveau" class="form-select" required>
                        <option value="">-- Sélectionner le niveau --</option>
                        <?php foreach ($niveaux as $niveau): ?>
                            <option value="<?php echo $niveau['id_niveau']; ?>">
                                <?php echo $niveau['nom_niveau']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="annee_academique" class="form-label">Année académique</label>
                    <input type="text" name="annee_academique" id="annee_academique" class="form-control" placeholder="2025-2026" required>
                </div>
                <div class="col-md-2">
                    <label for="effectif_max" class="form-label">Effectif max</label>
                    <input type="number" name="effectif_max" id="effectif_max" class="form-control" value="40">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Ajouter la classe</button>
            </div>
        </form>
    </div>

    <!-- Affichage des classes par niveau — avec message si niveau vide -->
    <?php
    foreach ($niveaux as $niveau):
        $niveau_name = $niveau['nom_niveau'];
        $liste = $classes_par_niveau[$niveau_name] ?? [];
    ?>
        <div class="card mb-3">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <span><?php echo htmlspecialchars($niveau_name); ?></span>
                <?php if (empty($liste)): ?>
                    <span class="badge bg-warning text-dark">Aucune classe</span>
                <?php else: ?>
                    <span class="badge bg-light text-dark"><?= count($liste) ?> classe(s)</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($liste)): ?>
                    <p class="text-muted fst-italic mb-0">Ce niveau ne contient aucune classe.</p>
                <?php else: ?>
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Nom de la classe</th>
                            <th>Année académique</th>
                            <th>Effectif max</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($liste as $classe): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($classe['nom_classe']); ?></td>
                                <td><?php echo htmlspecialchars($classe['annee_academique']); ?></td>
                                <td><?php echo htmlspecialchars($classe['effectif_max']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
include_once "../includes/footer.php";
?>
