<?php
include_once __DIR__ . '/../traitements/db.php';

try {
    $stmt = $conn->query("SELECT * FROM niveaux ORDER BY ordre_niveau ASC");
    $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Niveaux - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Liste des Niveaux</h2>
        <a href="ajouter_niveau.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Ajouter un niveau
        </a>
    </div>

    <?php if (count($niveaux) === 0): ?>
        <div class="alert alert-info">Aucun niveau enregistré.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Nom du niveau</th>
                            <th>Ordre</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($niveaux as $niveau): ?>
                            <tr>
                                <td><?= htmlspecialchars($niveau['id_niveau']) ?></td>
                                <td><?= htmlspecialchars($niveau['nom_niveau']) ?></td>
                                <td><?= htmlspecialchars($niveau['ordre_niveau']) ?></td>
                                <td>
                                    <a href="modifier_niveau.php?id=<?= $niveau['id_niveau'] ?>"
                                       class="btn btn-sm btn-warning">
                                       <i class="bi bi-pencil"></i> Modifier
                                    </a>
                                    <a href="supprimer_niveau.php?id=<?= $niveau['id_niveau'] ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Voulez-vous vraiment supprimer ce niveau ?');">
                                       <i class="bi bi-trash"></i> Supprimer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>
