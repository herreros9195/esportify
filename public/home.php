<?php
$pageTitle = 'Accueil';
require_once __DIR__ . '/../includes/header.php';

$stmt = $pdo->prepare(
    "SELECT e.*, u.pseudo AS organizer_pseudo
     FROM events e
     JOIN users u ON e.organizer_id = u.id
     WHERE e.visible = 1
       AND e.status = 'valide'
       AND e.end_date >= NOW()
     ORDER BY
       CASE WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 0 ELSE 1 END,
       e.start_date ASC
     LIMIT 6"
);
$stmt->execute();
$featuredEvents = $stmt->fetchAll();
$heroEvents = array_slice(array_values(array_filter($featuredEvents, static function ($event) {
    return !empty($event['image_url']);
})), 0, 3);

function eventTimingLabel(array $event) {
    $now = time();
    $start = strtotime($event['start_date']);
    $end = strtotime($event['end_date']);

    if ($start !== false && $end !== false && $now >= $start && $now <= $end) {
        return 'En cours';
    }

    return 'A venir';
}
?>

<div id="heroCarousel" class="carousel slide mb-5 rounded overflow-hidden shadow" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php if (!empty($heroEvents)): ?>
            <?php foreach ($heroEvents as $index => $event): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-label="Slide <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
        <?php else: ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-label="Slide 1"></button>
        <?php endif; ?>
    </div>

    <div class="carousel-inner">
        <?php if (!empty($heroEvents)): ?>
            <?php foreach ($heroEvents as $index => $event): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="hero-slide" style="background-image: linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.68)), url('<?= e($event['image_url']) ?>');">
                        <div class="container hero-slide-content">
                            <span class="badge rounded-pill text-bg-light mb-3"><?= e(eventTimingLabel($event)) ?></span>
                            <h1 class="display-4 fw-bold"><?= e($event['title']) ?></h1>
                            <p class="lead hero-slide-copy"><?= e($event['description']) ?></p>
                            <div class="hero-slide-meta">
                                <span><?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></span>
                                <span><?= (int) $event['max_players'] ?> joueurs</span>
                                <span>Par <?= e($event['organizer_pseudo']) ?></span>
                            </div>
                            <a href="index.php?page=event_detail&id=<?= (int) $event['id'] ?>" class="btn btn-primary mt-3">Voir l'evenement</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="carousel-item active">
                <div class="hero-slide hero-slide-fallback">
                    <div class="container hero-slide-content">
                        <span class="badge rounded-pill text-bg-light mb-3">Plateforme e-sport</span>
                        <h1 class="display-4 fw-bold">Bienvenue sur Esportify</h1>
                        <p class="lead hero-slide-copy">La plateforme dediee aux competitions e-sport, aux inscriptions et au suivi des performances des joueurs.</p>
                        <a href="index.php?page=events" class="btn btn-primary mt-3">Decouvrir les evenements</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Precedent</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Suivant</span>
    </button>
</div>

<section class="mb-5">
    <div class="row align-items-center g-4">
        <div class="col-md-6">
            <h2 class="fw-bold">Qui sommes-nous ?</h2>
            <p>
                Fondee le 17 mars 2021, <strong>Esportify</strong> est une startup francaise specialisee dans
                l'organisation de tournois et d'evenements competitifs autour du jeu video.
            </p>
            <p>
                La plateforme centralise la consultation des evenements, l'inscription des joueurs,
                l'organisation des sessions et le suivi des performances.
            </p>
            <a href="index.php?page=events" class="btn btn-primary">Voir tous les evenements</a>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="h5 fw-bold">Pourquoi Esportify ?</h3>
                    <ul class="mb-0">
                        <li>evenements visibles seulement apres moderation</li>
                        <li>filtres asynchrones sur la liste publique</li>
                        <li>gestion des inscriptions, favoris et scores</li>
                        <li>espaces dedies joueur, organisateur et administrateur</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Evenements a venir et en cours</h2>
            <p class="text-muted mb-0">Seuls les evenements valides et visibles apparaissent sur la page d'accueil.</p>
        </div>
        <a href="index.php?page=events" class="btn btn-outline-dark">Acceder au catalogue</a>
    </div>

    <?php if (empty($featuredEvents)): ?>
        <div class="alert alert-info">Aucun evenement valide n'est actuellement programme.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($featuredEvents as $event): ?>
                <div class="col-md-4">
                    <article class="card h-100 shadow-sm">
                        <?php if (!empty($event['image_url'])): ?>
                            <img src="<?= e($event['image_url']) ?>" class="card-img-top" alt="<?= e($event['title']) ?>" style="height: 180px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h3 class="h5 card-title mb-0"><?= e($event['title']) ?></h3>
                                <span class="badge text-bg-<?= eventTimingLabel($event) === 'En cours' ? 'success' : 'primary' ?>"><?= e(eventTimingLabel($event)) ?></span>
                            </div>
                            <p class="card-text text-muted small">
                                <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?><br>
                                <?= (int) $event['max_players'] ?> joueurs max<br>
                                Par <?= e($event['organizer_pseudo']) ?>
                            </p>
                            <a href="index.php?page=event_detail&id=<?= (int) $event['id'] ?>" class="btn btn-outline-primary btn-sm">Voir les details</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
