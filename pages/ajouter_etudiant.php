<?php
include_once __DIR__ . '/../traitements/db.php';

$message = "";

/* =========================
   TRAITEMENT DU FORMULAIRE
========================= */
if (isset($_POST['ajouter'])) {

    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $date_naissance = $_POST['date_naissance'];
    $lieu_naissance = trim($_POST['lieu_naissance']);
    $sexe = $_POST['sexe'];
    $email = trim($_POST['email']);
    $id_classe = intval($_POST['id_classe']);

    try {

        // 1️⃣ Insertion sans matricule
        $insert = $conn->prepare("
            INSERT INTO etudiants
            (nom, prenom, date_naissance, lieu_naissance, sexe, email, id_classe)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $nom,
            $prenom,
            $date_naissance,
            $lieu_naissance,
            $sexe,
            $email,
            $id_classe
        ]);

        // 2️⃣ Récupérer ID généré
        $id_etudiant = $conn->lastInsertId();

        // 3️⃣ Générer matricule automatique
        // Récupérer info de la classe et du niveau
        $stmtNiveau = $conn->prepare("
            SELECT n.nom_niveau, c.nom_classe
            FROM classes c
            INNER JOIN niveaux n ON c.id_niveau = n.id_niveau
            WHERE c.id_classe = ?
        ");
        $stmtNiveau->execute([$id_classe]);
        $infoClasse = $stmtNiveau->fetch(PDO::FETCH_ASSOC);

        // Générer codes pour matricule
        $codeNiveau = strtoupper(substr($infoClasse['nom_niveau'], 0, 2)); // ex: L2
        $codeClasse = strtoupper(preg_replace('/[^A-Z]/i', '', substr($infoClasse['nom_classe'], 0, 3))); // ex: IAG
        $initiales = strtoupper(substr($prenom,0,1) . substr($nom,0,2)); // ex: Emilie Dupont → EMD
        $annee = date("y"); // ex: 2021 → 21

        $matricule = $codeNiveau . $codeClasse . $initiales . $annee;

        // 4️⃣ Mettre à jour le matricule
        $update = $conn->prepare("
            UPDATE etudiants 
            SET matricule = ?
            WHERE id_etudiant = ?
        ");
        $update->execute([$matricule, $id_etudiant]);

        $message = "Étudiant ajouté avec succès ! Matricule : " . $matricule;

    } catch(PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

/* =========================
   RÉCUPÉRATION DES CLASSES
========================= */
try {
    $classes = $conn->query("
        SELECT c.id_classe, c.nom_classe, n.nom_niveau
        FROM classes c
        INNER JOIN niveaux n ON c.id_niveau = n.id_niveau
        ORDER BY n.ordre_niveau, c.nom_classe
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include_once "../includes/navbar.php"; ?>

<div class="container mt-4">
    <h3>Ajouter un étudiant</h3>

    <?php if($message): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" name="nom" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Prénom</label>
            <input type="text" name="prenom" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Date de naissance</label>
            <input type="date" name="date_naissance" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Lieu de naissance</label>
            <input type="text" name="lieu_naissance" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Sexe</label>
            <select name="sexe" class="form-select" required>
                <option value="">-- Choisir --</option>
                <option value="M">Masculin</option>
                <option value="F">Féminin</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Classe</label>
            <select name="id_classe" class="form-select" required>
                <option value="">-- Sélectionner une classe --</option>
                <?php foreach($classes as $c): ?>
                    <option value="<?= $c['id_classe'] ?>">
                        <?= htmlspecialchars($c['nom_classe']." - ".$c['nom_niveau']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="ajouter" class="btn btn-success">
            Ajouter
        </button>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include_once "../includes/footer.php"; ?>
