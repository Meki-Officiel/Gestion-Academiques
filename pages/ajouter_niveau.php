<?php
include_once __DIR__ . '/../traitements/db.php';
// Initialisation des messages
$success_msg = '';
$error_msg = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_niveau = trim($_POST['nom_niveau']);
    $ordre_niveau = trim($_POST['ordre_niveau']);

    if (empty($nom_niveau) || empty($ordre_niveau)) {
        $error_msg = "Tous les champs sont obligatoires.";
    } elseif (!is_numeric($ordre_niveau)) {
        $error_msg = "L'ordre du niveau doit être un nombre.";
    } else {
        try {
            // Préparer l'insertion
            $stmt = $conn->prepare("INSERT INTO niveaux (nom_niveau, ordre_niveau) VALUES (:nom, :ordre)");
            $stmt->bindParam(':nom', $nom_niveau);
            $stmt->bindParam(':ordre', $ordre_niveau);
            $stmt->execute();
            $success_msg = "Niveau '$nom_niveau' ajouté avec succès !";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error_msg = "Ce niveau existe déjà.";
            } else {
                $error_msg = "Erreur : " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un niveau - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>
<div class="container py-5">
    <h2>Ajouter un Niveau</h2>
    <p class="text-muted">Créer un nouveau niveau de formation (Licence, Master…)</p>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="card p-4">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="nom_niveau" class="form-label">Nom du niveau</label>
                <input type="text" class="form-control" id="nom_niveau" name="nom_niveau" required>
            </div>
            <div class="mb-3">
                <label for="ordre_niveau" class="form-label">Ordre du niveau</label>
                <input type="number" class="form-control" id="ordre_niveau" name="ordre_niveau" required>
                <small class="text-muted">Exemple : 1 pour Licence 1, 2 pour Licence 2, etc.</small>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter le niveau</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
        
<?php
include_once "../includes/footer.php";
?>