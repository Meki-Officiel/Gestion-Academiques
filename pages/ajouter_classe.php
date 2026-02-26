<?php
include_once __DIR__ . '/../traitements/db.php';
$success_msg = '';
$error_msg = '';

// =====================
// Traitement du formulaire
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_classe = trim($_POST['nom_classe']);
    $id_niveau = intval($_POST['id_niveau']);
    $annee_academique = trim($_POST['annee_academique']);
    $effectif_max = !empty($_POST['effectif_max']) ? intval($_POST['effectif_max']) : null;

    if (empty($nom_classe) || empty($id_niveau) || empty($annee_academique)) {
        $error_msg = "Tous les champs sauf effectif max sont obligatoires.";
    } else {
        try {
            // Vérifier doublon : même niveau + année + nom classe
            $check = $conn->prepare("SELECT * FROM classes WHERE nom_classe = ? AND id_niveau = ? AND annee_academique = ?");
            $check->execute([$nom_classe, $id_niveau, $annee_academique]);

            if ($check->rowCount() > 0) {
                $error_msg = "Cette classe existe déjà pour ce niveau et cette année.";
            } else {
                // Ajouter la classe
                $stmt = $conn->prepare("
                    INSERT INTO classes (nom_classe, id_niveau, annee_academique, effectif_max)
                    VALUES (:nom, :id_niveau, :annee, :effectif)
                ");
                $stmt->execute([
                    ':nom' => $nom_classe,
                    ':id_niveau' => $id_niveau,
                    ':annee' => $annee_academique,
                    ':effectif' => $effectif_max
                ]);
                $success_msg = "Classe '$nom_classe' ajoutée avec succès !";
            }
        } catch(PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}

// =====================
// Récupérer tous les niveaux
// =====================
$niveaux = $conn->query("SELECT * FROM niveaux ORDER BY ordre_niveau ASC")->fetchAll(PDO::FETCH_ASSOC);

// =====================
// Récupérer toutes les classes avec leur niveau
// =====================
$classes = $conn->query("
    SELECT c.*, n.nom_niveau 
    FROM classes c
    INNER JOIN niveaux n ON c.id_niveau = n.id_niveau
    ORDER BY n.ordre_niveau, c.nom_classe
")->fetchAll(PDO::FETCH_ASSOC);

// Regrouper les classes par niveau pour affichage
$classes_par_niveau = [];
foreach ($classes as $classe) {
    $classes_par_niveau[$classe['nom_niveau']][] = $classe;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une classe - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include_once "../includes/navbar.php"; ?>
<div class="container mt-4">
    <h3>Ajouter une classe</h3>

    <?php if($success_msg): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <!-- Formulaire d'ajout -->
    <form method="POST" class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nom de la classe</label>
                <input type="text" name="nom_classe" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Niveau</label>
                <select name="id_niveau" class="form-select" required>
                    <option value="">-- Sélectionner un niveau --</option>
                    <?php foreach($niveaux as $niveau): ?>
                        <option value="<?= $niveau['id_niveau'] ?>"><?= htmlspecialchars($niveau['nom_niveau']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Année académique</label>
                <input type="text" name="annee_academique" class="form-control" placeholder="2025-2026" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Effectif max</label>
                <input type="number" name="effectif_max" class="form-control" value="40">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Ajouter la classe</button>
        </div>
    </form>

    <!-- =========================
         Liste des classes existantes par niveau
         ========================= -->
    <h4>Liste des classes existantes</h4>
    <?php if(empty($classes)): ?>
        <div class="alert alert-info">Aucune classe ajoutée pour le moment.</div>
    <?php else: ?>
        <?php foreach($classes_par_niveau as $niveau => $liste_classes): ?>
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <?= $niveau ?> <?= empty($liste_classes) ? "(aucune classe)" : "" ?>
                </div>
                <div class="card-body">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nom de la classe</th>
                                <th>Année académique</th>
                                <th>Effectif max</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($liste_classes as $classe): ?>
                                <tr>
                                    <td><?= htmlspecialchars($classe['nom_classe']) ?></td>
                                    <td><?= htmlspecialchars($classe['annee_academique']) ?></td>
                                    <td><?= htmlspecialchars($classe['effectif_max']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>
