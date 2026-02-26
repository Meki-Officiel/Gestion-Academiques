<?php
include_once __DIR__ . '/../traitements/db.php';
$message = "";

// Récupérer les étudiants
try {
    $stmt = $conn->query("SELECT id_etudiant, matricule, CONCAT(nom, ' ', prenom) AS nom_complet FROM etudiants ORDER BY nom, prenom");
    $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les classes
    $stmt = $conn->query("
        SELECT c.id_classe, c.nom_classe, n.nom_niveau, c.annee_academique
        FROM classes c
        INNER JOIN niveaux n ON c.id_niveau = n.id_niveau
        ORDER BY n.ordre_niveau, c.nom_classe
    ");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les inscriptions existantes
    $stmt = $conn->query("
        SELECT i.id_inscription, e.matricule, CONCAT(e.nom,' ',e.prenom) AS etudiant,
               c.nom_classe, n.nom_niveau, i.annee_academique, i.statut
        FROM inscriptions i
        INNER JOIN etudiants e ON i.id_etudiant = e.id_etudiant
        INNER JOIN classes c ON i.id_classe = c.id_classe
        INNER JOIN niveaux n ON c.id_niveau = n.id_niveau
        ORDER BY n.ordre_niveau, c.nom_classe, e.nom
    ");
    $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Traitement du formulaire d'inscription
if (isset($_POST['inscrire'])) {
    $id_etudiant = intval($_POST['id_etudiant']);
    $id_classe = intval($_POST['id_classe']);
    $annee = trim($_POST['annee_academique']);
    $statut = 'actif';

    try {
        // Vérifier si l'inscription existe déjà
        $stmt = $conn->prepare("SELECT * FROM inscriptions WHERE id_etudiant = ? AND id_classe = ? AND annee_academique = ?");
        $stmt->execute([$id_etudiant, $id_classe, $annee]);
        if ($stmt->rowCount() > 0) {
            $message = "Cet étudiant est déjà inscrit dans cette classe pour cette année.";
        } else {
            $stmt = $conn->prepare("INSERT INTO inscriptions (id_etudiant, id_classe, annee_academique, statut) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_etudiant, $id_classe, $annee, $statut]);
            $message = "Inscription réussie !";
            header("Location: inscrire_etudiant.php"); // pour éviter la resoumission du formulaire
            exit;
        }
    } catch(PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Étudiant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>
<div class="container py-4">
    <h2>Inscrire un étudiant</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="id_etudiant" class="form-label">Étudiant</label>
            <select name="id_etudiant" id="id_etudiant" class="form-select" required>
                <option value="">-- Sélectionnez un étudiant --</option>
                <?php foreach ($etudiants as $e): ?>
                    <option value="<?= $e['id_etudiant'] ?>"><?= htmlspecialchars($e['nom_complet'] . " (" . $e['matricule'] . ")") ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="id_classe" class="form-label">Classe</label>
            <select name="id_classe" id="id_classe" class="form-select" required>
                <option value="">-- Sélectionnez une classe --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id_classe'] ?>"><?= htmlspecialchars($c['nom_classe'] . " - " . $c['nom_niveau'] . " (" . $c['annee_academique'] . ")") ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="annee_academique" class="form-label">Année académique</label>
            <input type="text" name="annee_academique" id="annee_academique" class="form-control" placeholder="Ex: 2025-2026" required>
        </div>

        <button type="submit" name="inscrire" class="btn btn-primary">Inscrire</button>
    </form>

    <h3>Liste des inscriptions</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Étudiant</th>
                <th>Classe</th>
                <th>Niveau</th>
                <th>Année académique</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inscriptions as $inscription): ?>
                <tr>
                    <td><?= htmlspecialchars($inscription['etudiant'] . " (" . $inscription['matricule'] . ")") ?></td>
                    <td><?= htmlspecialchars($inscription['nom_classe']) ?></td>
                    <td><?= htmlspecialchars($inscription['nom_niveau']) ?></td>
                    <td><?= htmlspecialchars($inscription['annee_academique']) ?></td>
                    <td><?= htmlspecialchars($inscription['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include_once "../includes/footer.php"; ?>
