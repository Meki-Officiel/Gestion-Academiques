<?php
include_once __DIR__ . '/../traitements/db.php';
// Vérifier l'ID de l'étudiant
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de l'étudiant manquant.");
}
$id_etudiant = intval($_GET['id']);

// Messages
$success_msg = '';
$error_msg = '';

// Récupérer l'étudiant
try {
    $stmt = $conn->prepare("SELECT * FROM etudiants WHERE id_etudiant = ?");
    $stmt->execute([$id_etudiant]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$etudiant) {
        die("Étudiant introuvable.");
    }

    // Récupérer toutes les classes pour le select
    $classes = $conn->query("
        SELECT c.id_classe, c.nom_classe, n.nom_niveau
        FROM classes c
        INNER JOIN niveaux n ON c.id_niveau = n.id_niveau
        ORDER BY n.ordre_niveau, c.nom_classe
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $date_naissance = $_POST['date_naissance'];
    $lieu_naissance = trim($_POST['lieu_naissance']);
    $sexe = $_POST['sexe'];
    $email = trim($_POST['email']);
    $id_classe = intval($_POST['id_classe']);

    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($lieu_naissance) || empty($sexe) || empty($email) || empty($id_classe)) {
        $error_msg = "Tous les champs sont obligatoires.";
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE etudiants SET
                    nom = :nom,
                    prenom = :prenom,
                    date_naissance = :date_naissance,
                    lieu_naissance = :lieu_naissance,
                    sexe = :sexe,
                    email = :email,
                    id_classe = :id_classe
                WHERE id_etudiant = :id
            ");

            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':date_naissance' => $date_naissance,
                ':lieu_naissance' => $lieu_naissance,
                ':sexe' => $sexe,
                ':email' => $email,
                ':id_classe' => $id_classe,
                ':id' => $id_etudiant
            ]);

            $success_msg = "Étudiant modifié avec succès !";

        } catch(PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include_once "../includes/navbar.php"; ?>
<div class="container mt-4">
    <h3>Modifier l'étudiant : <?= htmlspecialchars($etudiant['nom']." ".$etudiant['prenom']) ?></h3>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($etudiant['nom']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Prénom</label>
            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($etudiant['prenom']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Date de naissance</label>
            <input type="date" name="date_naissance" class="form-control" value="<?= $etudiant['date_naissance'] ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Lieu de naissance</label>
            <input type="text" name="lieu_naissance" class="form-control" value="<?= htmlspecialchars($etudiant['lieu_naissance']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Sexe</label>
            <select name="sexe" class="form-select" required>
                <option value="">-- Choisir --</option>
                <option value="M" <?= $etudiant['sexe'] === 'M' ? 'selected' : '' ?>>Masculin</option>
                <option value="F" <?= $etudiant['sexe'] === 'F' ? 'selected' : '' ?>>Féminin</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($etudiant['email']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Classe</label>
            <select name="id_classe" class="form-select" required>
                <option value="">-- Sélectionner une classe --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id_classe'] ?>" <?= $c['id_classe'] == $etudiant['id_classe'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nom_classe']." - ".$c['nom_niveau']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Modifier</button>
        <a href="liste_etudiant.php?" class="btn btn-secondary">Annuler</a>
    </form>
</div>
</body>
</html>
