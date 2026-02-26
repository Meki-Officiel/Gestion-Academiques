<?php include_once __DIR__ . '/../traitements/db.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Gestion Académique</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 100px 20px;
            border-radius: 0 0 40px 40px;
        }
        .feature-card {
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-light">

<?php include_once "../includes/navbar.php"; ?>

<div class="hero-section text-center">
    <h1 class="display-4 fw-bold" data-aos="fade-down">
        🎓 Système de Gestion Académique
    </h1>
    <p class="lead mt-3" data-aos="fade-up" data-aos-delay="200">
        Une plateforme moderne pour gérer efficacement les niveaux, classes, étudiants et évaluations.
    </p>
    <a href="/Gestion_Academique/includes/dashboard.php" class="btn btn-light btn-lg mt-3">
        <i></i> Accéder au tableau de bord
    </a>
</div>

<div class="container py-5">
    <div class="row text-center mb-5">
        <div class="col-12">
            <h2 data-aos="fade-up">Pourquoi utiliser cette plateforme ?</h2>
            <p class="text-muted" data-aos="fade-up" data-aos-delay="150">
                Une gestion académique simplifiée, rapide et organisée.
            </p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3" data-aos="zoom-in">
            <div class="card feature-card h-100 text-center p-4 border-0 shadow-sm">
                <i class="bi bi-grid-fill text-primary fs-1 mb-3"></i>
                <h5>Niveaux & Classes</h5>
                <p class="text-muted">Organisez les niveaux de formation et gérez les classes efficacement.</p>
                <a href="/Gestion_Academique/pages/niveau.php" class="btn btn-outline-primary btn-sm mt-auto">Voir</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="100">
            <div class="card feature-card h-100 text-center p-4 border-0 shadow-sm">
                <i class="bi bi-people-fill text-success fs-1 mb-3"></i>
                <h5>Gestion des Étudiants</h5>
                <p class="text-muted">Inscription, modification et suivi académique des étudiants.</p>
                <a href="/Gestion_Academique/pages/liste_etudiant.php" class="btn btn-outline-success btn-sm mt-auto">Voir</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="200">
            <div class="card feature-card h-100 text-center p-4 border-0 shadow-sm">
                <i class="bi bi-book-fill text-warning fs-1 mb-3"></i>
                <h5>Modules</h5>
                <p class="text-muted">Gestion complète des modules et des matières enseignées.</p>
                <a href="/Gestion_Academique/pages/ajouter_module.php" class="btn btn-outline-warning btn-sm mt-auto">Voir</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="300">
            <div class="card feature-card h-100 text-center p-4 border-0 shadow-sm">
                <i class="bi bi-clipboard-check-fill text-info fs-1 mb-3"></i>
                <h5>Évaluations</h5>
                <p class="text-muted">Suivi des notes, calcul automatique des moyennes et bulletins PDF.</p>
                <a href="/Gestion_Academique/pages/evaluations.php" class="btn btn-outline-info btn-sm mt-auto">Voir</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init({ duration: 1000, once: true });</script>

</body>
</html>

<?php include_once "../includes/footer.php"; ?>
