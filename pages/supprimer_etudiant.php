<?php
include_once __DIR__ . '/../traitements/db.php';

// Vérification de l'ID étudiant
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID de l'étudiant manquant.");
}

$id_etudiant = intval($_GET['id']);

try {
    // Supprimer l'étudiant
    $stmt = $conn->prepare("DELETE FROM etudiants WHERE id_etudiant = ?");
    $stmt->execute([$id_etudiant]);

    // Redirection vers la liste avec message de succès
    header("Location: liste_etudiant.php?msg=supprime");
    exit;

} catch (PDOException $e) {
    die("Erreur lors de la suppression : " . $e->getMessage());
}
