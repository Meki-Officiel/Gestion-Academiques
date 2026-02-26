<?php
include_once __DIR__ . '/../traitements/db.php';

$id_niveau = isset($_GET['id_niveau']) ? intval($_GET['id_niveau']) : (isset($_GET['id']) ? intval($_GET['id']) : null);

if (!$id_niveau) {
    header("Location: niveau.php");
    exit;
}

try {
    $delete = $conn->prepare("DELETE FROM niveaux WHERE id_niveau = ?");
    $delete->execute([$id_niveau]);
    header("Location: niveau.php?msg=supprime");
    exit;
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
