<?php
include_once __DIR__ . '/../traitements/db.php';

// Récupérer tous les niveaux
try {
    $stmt = $conn->query("SELECT * FROM niveaux ORDER BY ordre_niveau ASC");
    $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'modifie') $msg = "Niveau modifié avec succès !";
    if ($_GET['msg'] == 'supprime') $msg = "Niveau supprimé avec succès !";
    if ($_GET['msg'] == 'ajoute') $msg = "Niveau ajouté avec succès !";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Niveaux - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>

<div class="container py-4">
    <h2>Liste des Niveaux</h2>

    <?php if($msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>

    <a href="ajouter_niveau.php" class="btn btn-primary mb-3">
        <i class="bi bi-plus-circle"></i> Ajouter un niveau
    </a>

    <?php if (empty($niveaux)): ?>
        <div class="alert alert-info">Aucun niveau enregistré.</div>
    <?php else: ?>
    <table class="table table-striped table-bordered">
        <thead class="table-primary">
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Ordre</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($niveaux as $niveau): ?>
                <tr>
                    <td><?= $niveau['id_niveau'] ?></td>
                    <td><?= htmlspecialchars($niveau['nom_niveau']) ?></td>
                    <td><?= $niveau['ordre_niveau'] ?></td>
                    <td>
                        <a href="modifier_niveau.php?id=<?= $niveau['id_niveau'] ?>" class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil"></i> Modifier
                        </a>
                        <a href="supprimer_niveau.php?id=<?= $niveau['id_niveau'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Voulez-vous vraiment supprimer ce niveau ?');">
                           <i class="bi bi-trash"></i> Supprimer
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>
