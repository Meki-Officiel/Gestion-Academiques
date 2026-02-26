<?php
// Chemin correct vers la connexion
include_once __DIR__ . '/../traitements/db.php';
$message = "";

// Récupérer la liste des étudiants, classes, modules et types d'évaluation pour les selects
try {
    $etudiants = $conn->query("SELECT id_etudiant, matricule, CONCAT(nom,' ',prenom) AS nom_complet FROM etudiants ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    $classes = $conn->query("SELECT id_classe, nom_classe FROM classes ORDER BY nom_classe")->fetchAll(PDO::FETCH_ASSOC);
    $modules = $conn->query("SELECT id_module, nom_module FROM modules ORDER BY nom_module")->fetchAll(PDO::FETCH_ASSOC);
    $types = $conn->query("SELECT id_type_evaluation, nom_type FROM types_evaluation ORDER BY nom_type")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur récupération données : " . $e->getMessage());
}

// Traitement du formulaire
if (isset($_POST['ajouter'])) {
    $id_etudiant = intval($_POST['id_etudiant']);
    $id_module = intval($_POST['id_module']);
    $id_classe = intval($_POST['id_classe']);
    $id_type_evaluation = intval($_POST['id_type_evaluation']);
    $note = floatval($_POST['note']);
    $note_sur = floatval($_POST['note_sur']);
    $annee_academique = trim($_POST['annee_academique']);
    $semestre = trim($_POST['semestre']);
    $commentaire = trim($_POST['commentaire']);

    try {
        $stmt = $conn->prepare("
            INSERT INTO evaluations 
            (id_etudiant, id_module, id_classe, id_type_evaluation, note, note_sur, date_evaluation, annee_academique, semestre, commentaire)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
        ");
        $stmt->execute([
            $id_etudiant,
            $id_module,
            $id_classe,
            $id_type_evaluation,
            $note,
            $note_sur,
            $annee_academique,
            $semestre,
            $commentaire
        ]);
        $message = "Évaluation ajoutée avec succès !";
    } catch(PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Évaluation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>
<div class="container py-4">
    <h2>Ajouter une Évaluation</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="id_etudiant" class="form-label">Étudiant</label>
            <select class="form-select" id="id_etudiant" name="id_etudiant" required>
                <option value="">-- Sélectionnez un étudiant --</option>
                <?php foreach($etudiants as $e): ?>
                    <option value="<?= $e['id_etudiant'] ?>"><?= htmlspecialchars($e['matricule'].' - '.$e['nom_complet']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="id_classe" class="form-label">Classe</label>
            <select class="form-select" id="id_classe" name="id_classe" required>
                <option value="">-- Sélectionnez une classe --</option>
                <?php foreach($classes as $c): ?>
                    <option value="<?= $c['id_classe'] ?>"><?= htmlspecialchars($c['nom_classe']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="id_module" class="form-label">Module</label>
            <select class="form-select" id="id_module" name="id_module" required>
                <option value="">-- Sélectionnez un module --</option>
                <?php foreach($modules as $m): ?>
                    <option value="<?= $m['id_module'] ?>"><?= htmlspecialchars($m['nom_module']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="id_type_evaluation" class="form-label">Type d'évaluation</label>
            <select class="form-select" id="id_type_evaluation" name="id_type_evaluation" required>
                <option value="">-- Sélectionnez le type --</option>
                <?php foreach($types as $t): ?>
                    <option value="<?= $t['id_type_evaluation'] ?>"><?= htmlspecialchars($t['nom_type']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="note" class="form-label">Note</label>
            <input type="number" step="0.01" class="form-control" id="note" name="note" required>
        </div>

        <div class="mb-3">
            <label for="note_sur" class="form-label">Note sur</label>
            <input type="number" step="0.01" class="form-control" id="note_sur" name="note_sur" value="20" required>
        </div>

        <div class="mb-3">
            <label for="annee_academique" class="form-label">Année académique</label>
            <input type="text" class="form-control" id="annee_academique" name="annee_academique" placeholder="Ex: 2025-2026" required>
        </div>

        <div class="mb-3">
            <label for="semestre" class="form-label">Semestre</label>
            <select class="form-select" id="semestre" name="semestre" required>
                <option value="">-- Sélectionnez le semestre --</option>
                <option value="1">1</option>
                <option value="2">2</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="commentaire" class="form-label">Commentaire</label>
            <textarea class="form-control" id="commentaire" name="commentaire" rows="2"></textarea>
        </div>

        <button type="submit" name="ajouter" class="btn btn-primary">Ajouter Évaluation</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
                    