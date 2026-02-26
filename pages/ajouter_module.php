<?php
include_once __DIR__ . '/../traitements/db.php';

$success_msg = '';
$error_msg = '';

/* =====================
   AJOUTER UN NOUVEAU MODULE
===================== */
if (isset($_POST['ajouter_module'])) {
    $code_module = trim($_POST['code_module']);
    $nom_module = trim($_POST['nom_module']);
    $coefficient = floatval($_POST['coefficient']);
    $description = trim($_POST['description']);
    $date_creation = date("Y-m-d H:i:s");

    if (empty($code_module) || empty($nom_module) || empty($coefficient)) {
        $error_msg = "Tous les champs du module sont obligatoires.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id_module FROM modules WHERE code_module = ?");
            $stmt->execute([$code_module]);
            $module = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($module) {
                $error_msg = "Ce code de module existe déjà !";
            } else {
                $stmt = $conn->prepare("INSERT INTO modules (code_module, nom_module, coefficient, description, date_creation)
                                        VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$code_module, $nom_module, $coefficient, $description, $date_creation]);
                $success_msg = "Module '$nom_module' ajouté avec succès !";
            }
        } catch(PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}

/* =====================
   AFFECTER UN MODULE À UNE OU PLUSIEURS CLASSES
===================== */
if (isset($_POST['affecter_module'])) {
    $id_module = intval($_POST['id_module']);
    $id_classes = $_POST['id_classes'] ?? [];
    $annee_academique = trim($_POST['annee_academique']);
    $semestre = trim($_POST['semestre']);

    if (empty($id_module) || empty($id_classes) || empty($annee_academique) || empty($semestre)) {
        $error_msg = "Tous les champs pour l'affectation sont obligatoires.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO classe_modules (id_classe, id_module, annee_academique, semestre, date_affectation)
                                    VALUES (?, ?, ?, ?, NOW())");
            foreach ($id_classes as $id_classe) {
                $stmt->execute([$id_classe, $id_module, $annee_academique, $semestre]);
            }
            $success_msg = "Module affecté aux classes avec succès !";
        } catch(PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}

/* =====================
   Récupération des classes
===================== */
$classes = $conn->query("
    SELECT c.id_classe, c.nom_classe, n.nom_niveau
    FROM classes c
    INNER JOIN niveaux n ON c.id_niveau = n.id_niveau
    ORDER BY n.ordre_niveau, c.nom_classe
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================
   Récupération des modules existants avec leurs classes
===================== */
$modules = $conn->query("
    SELECT m.*, GROUP_CONCAT(c.nom_classe ORDER BY c.nom_classe SEPARATOR ', ') AS classes_affectees
    FROM modules m
    LEFT JOIN classe_modules cm ON m.id_module = cm.id_module
    LEFT JOIN classes c ON cm.id_classe = c.id_classe
    GROUP BY m.id_module
    ORDER BY m.nom_module
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Modules - Gestion Académique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include_once "../includes/navbar.php"; ?>

<div class="container py-5">
    <h2>Gestion des modules</h2>

    <?php if($success_msg): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
    <?php endif; ?>
    <?php if($error_msg): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <!-- FORMULAIRE AJOUT MODULE -->
    <div class="card p-4 mb-4">
        <h5>Ajouter un module</h5>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Code du module</label>
                    <input type="text" name="code_module" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nom du module</label>
                    <input type="text" name="nom_module" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Coefficient</label>
                    <input type="number" step="0.1" name="coefficient" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="ajouter_module" class="btn btn-primary">Ajouter le module</button>
            </div>
        </form>
    </div>

    <!-- FORMULAIRE AFFECTATION MODULE À CLASSES -->
    <div class="card p-4 mb-4">
        <h5>Affecter un module à des classes</h5>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Module</label>
                    <select name="id_module" class="form-select" required>
                        <option value="">-- Sélectionner un module --</option>
                        <?php foreach($modules as $m): ?>
                            <option value="<?= $m['id_module'] ?>">
                                <?= htmlspecialchars($m['nom_module'] . " (" . $m['code_module'] . ")") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Année académique</label>
                    <input type="text" name="annee_academique" class="form-control" placeholder="2025-2026" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Semestre</label>
                    <select name="semestre" class="form-select" required>
                        <option value="">-- Choisir --</option>
                        <option value="1">Semestre 1</option>
                        <option value="2">Semestre 2</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Classes</label>
                    <select name="id_classes[]" class="form-select" multiple required>
                        <?php foreach($classes as $c): ?>
                            <option value="<?= $c['id_classe'] ?>">
                                <?= htmlspecialchars($c['nom_classe'] . " - " . $c['nom_niveau']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Maintenez Ctrl/Cmd pour sélectionner plusieurs classes</small>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="affecter_module" class="btn btn-success">Affecter le module</button>
            </div>
        </form>
    </div>

    <!-- LISTE DES MODULES EXISTANTS -->
    <h4>Modules existants et leurs classes</h4>
    <table class="table table-striped table-bordered">
        <thead class="table-light">
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Coefficient</th>
                <th>Description</th>
                <th>Classes affectées</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($modules as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['code_module']) ?></td>
                    <td><?= htmlspecialchars($m['nom_module']) ?></td>
                    <td><?= htmlspecialchars($m['coefficient']) ?></td>
                    <td><?= htmlspecialchars($m['description']) ?></td>
                    <td><?= htmlspecialchars($m['classes_affectees'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once "../includes/footer.php"; ?>
</body>
</html>
