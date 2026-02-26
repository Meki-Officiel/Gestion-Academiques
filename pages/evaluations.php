<?php
include_once __DIR__ . '/../traitements/db.php';
$success_msg = '';
$error_msg = '';

// ===========================
// SUPPRESSION D'UNE ÉVALUATION
// ===========================
if (isset($_GET['supprimer'])) {
    $id_eval = intval($_GET['supprimer']);
    try {
        $conn->prepare("DELETE FROM evaluations WHERE id_evaluation = ?")->execute([$id_eval]);
        $success_msg = "Évaluation supprimée avec succès !";
    } catch(PDOException $e) {
        $error_msg = "Erreur suppression : " . $e->getMessage();
    }
}

// ===========================
// MODIFICATION D'UNE ÉVALUATION
// ===========================
if (isset($_POST['modifier'])) {
    $id_eval = intval($_POST['id_evaluation']);
    $note = floatval($_POST['note']);
    $note_sur = floatval($_POST['note_sur']);
    $commentaire = trim($_POST['commentaire']);
    $date_evaluation = $_POST['date_evaluation'];
    try {
        $conn->prepare("UPDATE evaluations SET note=?, note_sur=?, commentaire=?, date_evaluation=? WHERE id_evaluation=?")
            ->execute([$note, $note_sur, $commentaire, $date_evaluation, $id_eval]);
        $success_msg = "Évaluation modifiée avec succès !";
    } catch(PDOException $e) {
        $error_msg = "Erreur modification : " . $e->getMessage();
    }
}

// ===========================
// AJOUT D'UNE ÉVALUATION
// ===========================
if (isset($_POST['ajouter'])) {
    $id_etudiant = intval($_POST['id_etudiant']);
    $id_module = intval($_POST['id_module']);
    $id_classe = intval($_POST['id_classe']);
    $id_type_evaluation = intval($_POST['id_type_evaluation']);
    $note = floatval($_POST['note']);
    $note_sur = floatval($_POST['note_sur']);
    $date_evaluation = $_POST['date_evaluation'];
    $annee_academique = trim($_POST['annee_academique']);
    $semestre = trim($_POST['semestre']);
    $commentaire = trim($_POST['commentaire']);

    if ($note < 0 || $note > $note_sur) {
        $error_msg = "La note doit être entre 0 et " . $note_sur . ".";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO evaluations 
                (id_etudiant, id_module, id_classe, id_type_evaluation, note, note_sur, date_evaluation, annee_academique, semestre, commentaire, date_enregistrement)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $id_etudiant, $id_module, $id_classe, $id_type_evaluation,
                $note, $note_sur, $date_evaluation, $annee_academique, $semestre, $commentaire
            ]);
            $success_msg = "Évaluation ajoutée avec succès !";
        } catch(PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}

// ===========================
// RÉCUPÉRATION DES DONNÉES
// ===========================
$etudiants = $conn->query("SELECT id_etudiant, matricule, nom, prenom, id_classe FROM etudiants ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$modules = $conn->query("SELECT * FROM modules ORDER BY nom_module ASC")->fetchAll(PDO::FETCH_ASSOC);
$classes = $conn->query("SELECT c.id_classe, c.nom_classe, n.nom_niveau FROM classes c JOIN niveaux n ON c.id_niveau = n.id_niveau ORDER BY n.ordre_niveau, c.nom_classe")->fetchAll(PDO::FETCH_ASSOC);
$types_eval = $conn->query("SELECT * FROM types_evaluation ORDER BY nom_type ASC")->fetchAll(PDO::FETCH_ASSOC);

// Évaluation à modifier (si clic sur modifier)
$eval_edit = null;
if (isset($_GET['modifier'])) {
    $id_edit = intval($_GET['modifier']);
    $eval_edit = $conn->prepare("
        SELECT ev.*, e.nom AS nom_etudiant, e.prenom, m.nom_module, c.nom_classe, t.nom_type
        FROM evaluations ev
        JOIN etudiants e ON ev.id_etudiant = e.id_etudiant
        JOIN modules m ON ev.id_module = m.id_module
        JOIN classes c ON ev.id_classe = c.id_classe
        JOIN types_evaluation t ON ev.id_type_evaluation = t.id_type_evaluation
        WHERE ev.id_evaluation = ?
    ");
    $eval_edit->execute([$id_edit]);
    $eval_edit = $eval_edit->fetch(PDO::FETCH_ASSOC);
}

// Filtre par étudiant (matricule) et module
$filtre_matricule = trim($_GET['matricule'] ?? '');
$filtre_module = trim($_GET['id_module_filtre'] ?? '');

$where = "WHERE 1=1";
$params = [];
if ($filtre_matricule !== '') {
    $where .= " AND e.matricule = ?";
    $params[] = $filtre_matricule;
}
if ($filtre_module !== '') {
    $where .= " AND ev.id_module = ?";
    $params[] = $filtre_module;
}

$stmtEval = $conn->prepare("
    SELECT ev.id_evaluation, e.matricule, e.nom AS nom_etudiant, e.prenom AS prenom_etudiant,
           m.nom_module, c.nom_classe, t.nom_type AS type_eval, ev.note, ev.note_sur,
           ev.date_evaluation, ev.annee_academique, ev.semestre, ev.commentaire,
           t.inclus_dans_moyenne
    FROM evaluations ev
    INNER JOIN etudiants e ON ev.id_etudiant = e.id_etudiant
    INNER JOIN modules m ON ev.id_module = m.id_module
    INNER JOIN classes c ON ev.id_classe = c.id_classe
    INNER JOIN types_evaluation t ON ev.id_type_evaluation = t.id_type_evaluation
    $where
    ORDER BY ev.date_evaluation DESC
");
$stmtEval->execute($params);
$evaluations = $stmtEval->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Évaluations - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>
<div class="container py-4">
    <h2>Gestion des Évaluations</h2>

    <?php if($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <?php if ($eval_edit): ?>
    <!-- FORMULAIRE MODIFICATION -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            Modifier l'évaluation — <?= htmlspecialchars($eval_edit['nom_etudiant'].' '.$eval_edit['prenom']) ?>
            — <?= htmlspecialchars($eval_edit['nom_module']) ?> (<?= htmlspecialchars($eval_edit['nom_type']) ?>)
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id_evaluation" value="<?= $eval_edit['id_evaluation'] ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Note</label>
                        <input type="number" step="0.01" name="note" class="form-control" value="<?= $eval_edit['note'] ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Note sur</label>
                        <input type="number" step="0.01" name="note_sur" class="form-control" value="<?= $eval_edit['note_sur'] ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_evaluation" class="form-control" value="<?= $eval_edit['date_evaluation'] ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Commentaire</label>
                        <input type="text" name="commentaire" class="form-control" value="<?= htmlspecialchars($eval_edit['commentaire']) ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="modifier" class="btn btn-warning">Enregistrer modification</button>
                    <a href="evaluations.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- FORMULAIRE AJOUT -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Ajouter une nouvelle évaluation</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Étudiant</label>
                        <select name="id_etudiant" class="form-select" required>
                            <option value="">-- Sélectionner un étudiant --</option>
                            <?php foreach($etudiants as $e): ?>
                                <option value="<?= $e['id_etudiant'] ?>"><?= htmlspecialchars($e['matricule'].' - '.$e['nom'].' '.$e['prenom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Classe</label>
                        <select name="id_classe" class="form-select" required>
                            <option value="">-- Sélectionner une classe --</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id_classe'] ?>"><?= htmlspecialchars($c['nom_classe'].' - '.$c['nom_niveau']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Module</label>
                        <select name="id_module" class="form-select" required>
                            <option value="">-- Sélectionner un module --</option>
                            <?php foreach($modules as $m): ?>
                                <option value="<?= $m['id_module'] ?>"><?= htmlspecialchars($m['nom_module']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type d'évaluation</label>
                        <select name="id_type_evaluation" class="form-select" required>
                            <option value="">-- Sélectionner le type --</option>
                            <?php foreach($types_eval as $t): ?>
                                <option value="<?= $t['id_type_evaluation'] ?>"><?= htmlspecialchars($t['nom_type']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Note</label>
                        <input type="number" step="0.01" min="0" name="note" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Note sur</label>
                        <input type="number" step="0.01" name="note_sur" class="form-control" value="20" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date d'évaluation</label>
                        <input type="date" name="date_evaluation" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Année académique</label>
                        <input type="text" name="annee_academique" class="form-control" placeholder="2025-2026" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Semestre</label>
                        <select name="semestre" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <option value="1">Semestre 1</option>
                            <option value="2">Semestre 2</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Commentaire</label>
                        <textarea name="commentaire" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" name="ajouter" class="btn btn-success">Ajouter l'évaluation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FILTRE RECHERCHE PAR MATRICULE ET MODULE -->
    <div class="card mb-3">
        <div class="card-header bg-light">Rechercher / Filtrer les évaluations</div>
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Matricule étudiant</label>
                    <input type="text" name="matricule" class="form-control" placeholder="Ex: ETU2026011" value="<?= htmlspecialchars($filtre_matricule) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Module</label>
                    <select name="id_module_filtre" class="form-select">
                        <option value="">-- Tous les modules --</option>
                        <?php foreach($modules as $m): ?>
                            <option value="<?= $m['id_module'] ?>" <?= $filtre_module == $m['id_module'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nom_module']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="evaluations.php" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLEAU DES ÉVALUATIONS -->
    <div class="card">
        <div class="card-header bg-info text-white">
            Liste des évaluations (<?= count($evaluations) ?> résultat(s))
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Matricule</th>
                        <th>Étudiant</th>
                        <th>Module</th>
                        <th>Classe</th>
                        <th>Type</th>
                        <th>Note</th>
                        <th>Sur</th>
                        <th>/20</th>
                        <th>Dans moy.</th>
                        <th>Date</th>
                        <th>Commentaire</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($evaluations as $ev): ?>
                        <tr>
                            <td><?= htmlspecialchars($ev['matricule']) ?></td>
                            <td><?= htmlspecialchars($ev['nom_etudiant'].' '.$ev['prenom_etudiant']) ?></td>
                            <td><?= htmlspecialchars($ev['nom_module']) ?></td>
                            <td><?= htmlspecialchars($ev['nom_classe']) ?></td>
                            <td><?= htmlspecialchars($ev['type_eval']) ?></td>
                            <td><?= $ev['note'] ?></td>
                            <td><?= $ev['note_sur'] ?></td>
                            <td><?= number_format($ev['note'] / $ev['note_sur'] * 20, 2) ?></td>
                            <td class="text-center">
                                <?= $ev['inclus_dans_moyenne'] ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-secondary">Non (TP)</span>' ?>
                            </td>
                            <td><?= $ev['date_evaluation'] ?></td>
                            <td><?= htmlspecialchars($ev['commentaire']) ?></td>
                            <td>
                                <a href="evaluations.php?modifier=<?= $ev['id_evaluation'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                                <a href="evaluations.php?supprimer=<?= $ev['id_evaluation'] ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Supprimer cette évaluation ?')">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($evaluations) === 0): ?>
                        <tr><td colspan="12" class="text-center text-muted">Aucune évaluation trouvée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>
