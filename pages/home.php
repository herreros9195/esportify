<?php
$pageTitle = 'Accueil';
require_once __DIR__ . '/../includes/header.php';

// Récupérer les événements visibles à venir
$stmt = $pdo->prepare("SELECT e.*, u.pseudo as organizer_pseudo 
                       FROM events e 
                       JOIN users u ON e.organizer_id = u.id 
                       WHERE e.visible = 1 AND e.status = 'valide' AND e.start_date >= NOW()
                       ORDER BY e.start_date ASC LIMIT 6");
$stmt->execute();
$upcomingEvents = $stmt->fetchAll();
?>

<!-- Hero / Diaporama -->
<div id="heroCarousel" class="carousel slide mb-5 rounded overflow-hidden shadow" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <div class="d-block w-100 bg-primary text-white text-center py-5" style="min-height:300px;">
                <div class="container py-5">
                    <h1 class="display-4 fw-bold">Bienvenue sur Esportify</h1>
                    <p class="lead">La plateforme dédiée aux compétitions e-sport en France.</p>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <div class="d-block w-100 bg-dark text-white text-center py-5" style="min-height:300px;">
                <div class="container py-5">
                    <h1 class="display-4 fw-bold">Inscrivez-vous aux tournois</h1>
                    <p class="lead">Rejoignez des centaines de joueurs et grimpez dans le classement.</p>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <div class="d-block w-100 bg-success text-white text-center py-5" style="min-height:300px;">
                <div class="container py-5">
                    <h1 class="display-4 fw-bold">Organisez vos événements</h1>
                    <p class="lead">Devenez organisateur et créez vos propres compétitions.</p>
                </div>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- Présentation -->
<section class="mb-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bold">Qui sommes-nous ?</h2>
            <p>
                Fondée le 17 mars 2021, <strong>Esportify</strong> est une startup innovante spécialisée dans l'organisation 
                d'événements e-sport en France. Notre mission est de connecter les joueurs passionnés et de leur offrir 
                une expérience compétitive unique.
            </p>
            <p>
                Grâce à notre plateforme, inscrivez-vous facilement aux tournois, suivez vos performances et échangez 
                avec la communauté.
            </p>
            <a href="index.php?page=events" class="btn btn-primary">Découvrir les événements</a>
        </div>
        <div class="col-md-6 text-center">
            <div class="bg-light border rounded p-5">
                <span class="display-1">🎮</span>
                <p class="mt-3 text-muted">Esportify, le futur du e-sport.</p>
            </div>
        </div>
    </div>
</section>

<!-- Événements à venir -->
<section class="mb-5">
    <h2 class="fw-bold mb-4">Événements à venir</h2>
    <?php if (empty($upcomingEvents)): ?>
        <div class="alert alert-info">Aucun événement à venir pour le moment.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= e($event['title']) ?></h5>
                            <p class="card-text text-muted small">
                                📅 <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?><br>
                                👥 <?= e($event['max_players']) ?> joueurs max<br>
                                👤 Par <?= e($event['organizer_pseudo']) ?>
                            </p>
                            <a href="index.php?page=event_detail&id=<?= (int)$event['id'] ?>" class="btn btn-outline-primary btn-sm">Voir les détails</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="index.php?page=events" class="btn btn-dark">Voir tous les événements</a>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
