<?php
include_once __DIR__ . '/../traitements/db.php';
$id_niveau = isset($_GET['id_niveau']) ? intval($_GET['id_niveau']) : (isset($_GET['id']) ? intval($_GET['id']) : null);

if (!$id_niveau) {
    header("Location: niveau.php");
    exit;
}

// Récupérer données actuelles
try {
    $stmt = $conn->prepare("SELECT * FROM niveaux WHERE id_niveau = ?");
    $stmt->execute([$id_niveau]);
    $niveau = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$niveau) {
        header("Location: niveau.php");
        exit;
    }
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

$message = '';

// Traitement formulaire
if (isset($_POST['modifier'])) {
    $nom_niveau = trim($_POST['nom_niveau']);
    $ordre_niveau = intval($_POST['ordre_niveau']);

    try {
        $update = $conn->prepare("UPDATE niveaux SET nom_niveau = ?, ordre_niveau = ? WHERE id_niveau = ?");
        $update->execute([$nom_niveau, $ordre_niveau, $id_niveau]);
        header("Location: niveau.php?msg=modifie");
        exit;
    } catch(PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Niveau</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>
<div class="container py-4">
    <h2>Modifier Niveau</h2>

    <?php if($message): ?>
        <div class="alert alert-danger"><?= $message ?></div>
    <?php endif; ?>

    <div class="card p-4">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nom du niveau</label>
                <input type="text" class="form-control" name="nom_niveau" required value="<?= htmlspecialchars($niveau['nom_niveau']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Ordre du niveau</label>
                <input type="number" class="form-control" name="ordre_niveau" required value="<?= htmlspecialchars($niveau['ordre_niveau']) ?>">
            </div>
            <button type="submit" name="modifier" class="btn btn-primary">Modifier</button>
            <a href="niveau.php" class="btn btn-secondary">Retour</a>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>
