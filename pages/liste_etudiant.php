<?php
include_once __DIR__ . '/../traitements/db.php'; // Connexion PDO
// Navbar commune
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des étudiants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include_once "../includes/navbar.php"; ?>

<div class="container my-4">
    <h2>Liste des étudiants par classe</h2>
    <a href="ajouter_etudiant.php" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle"></i> Ajouter un étudiant
    </a>

    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'supprime'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Étudiant supprimé avec succès !
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php
    // Récupérer les niveaux
    $stmt = $conn->query("SELECT * FROM niveaux ORDER BY ordre_niveau ASC");
    $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($niveaux as $niveau) {
        echo "<h3 class='mt-4'>{$niveau['nom_niveau']}</h3>";

        // Récupérer les classes de ce niveau
        $stmt2 = $conn->prepare("SELECT * FROM classes WHERE id_niveau = ? ORDER BY nom_classe ASC");
        $stmt2->execute([$niveau['id_niveau']]);
        $classes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if (count($classes) === 0) {
            echo "<p class='text-muted'>Aucune classe pour ce niveau.</p>";
            continue;
        }

        foreach ($classes as $classe) {
            echo "<h5 class='mt-3'>Classe : {$classe['nom_classe']}</h5>";

            // Récupérer les étudiants de cette classe
            $stmt3 = $conn->prepare("SELECT * FROM etudiants WHERE id_classe = ? ORDER BY nom ASC");
            $stmt3->execute([$classe['id_classe']]);
            $etudiants = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            if (count($etudiants) === 0) {
                echo "<p class='text-muted'>Aucun étudiant dans cette classe.</p>";
                continue;
            }

            echo "<table class='table table-bordered table-striped'>
                    <thead class='table-light'>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Date de naissance</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>";

            foreach ($etudiants as $etu) {
                echo "<tr>
                        <td>{$etu['matricule']}</td>
                        <td>{$etu['nom']}</td>
                        <td>{$etu['prenom']}</td>
                        <td>{$etu['date_naissance']}</td>
                        <td>{$etu['email']}</td>
                        <td>
                            <a href='modifier_etudiant.php?id={$etu['id_etudiant']}' class='btn btn-sm btn-primary'>Modifier</a>
                            <a href='supprimer_etudiant.php?id={$etu['id_etudiant']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Voulez-vous vraiment supprimer cet étudiant ?');\">Supprimer</a>
                            <a href='bulltin.php?id={$etu['id_etudiant']}' class='btn btn-sm btn-success'>
                                <i class='bi bi-file-earmark-text'></i> Bulletin
                            </a>
                        </td>
                      </tr>";
            }

            echo "</tbody></table>";
        }
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
include_once __DIR__ . '/../includes/footer.php'; // Footer commun
?>