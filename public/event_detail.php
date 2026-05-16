<?php
require_once __DIR__ . '/../includes/functions.php';

$eventId = (int)($_GET['id'] ?? 0);
$event = getEventById($pdo, $eventId);

if (!$event || !$event['visible'] || $event['status'] !== 'valide') {
    http_response_code(404);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo '<div class="alert alert-danger">Evenement introuvable ou non visible.</div>';
        exit;
    }
    $pageTitle = 'Evenement introuvable';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-danger">Evenement introuvable ou non visible.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$acceptedCount = countAcceptedRegistrations($pdo, $eventId);
$userRegistrationStatus = null;
$userFavorite = false;
$eventImages = getEventImages($pdo, $eventId, $event['image_url'] ?? null);
$isFinished = strtotime($event['end_date']) < time();
$isOngoing = !empty($event['started']) && !$isFinished;
$isJoinableNow = isEventJoinable($event, true);
$sessionPreparedEarly = !empty($event['started']) && !$isFinished && !$isJoinableNow;

if (isLoggedIn()) {
    $registrationStmt = $pdo->prepare("SELECT status FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id LIMIT 1");
    $registrationStmt->execute([
        ':event_id' => $eventId,
        ':user_id' => (int)$_SESSION['user_id'],
    ]);
    $userRegistrationStatus = $registrationStmt->fetchColumn() ?: null;

    $favoriteStmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND event_id = :event_id LIMIT 1");
    $favoriteStmt->execute([
        ':user_id' => (int)$_SESSION['user_id'],
        ':event_id' => $eventId,
    ]);
    $userFavorite = (bool)$favoriteStmt->fetch();
}

$canJoinEvent = $userRegistrationStatus === 'accepte' && $isJoinableNow;
$chatLink = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    ? 'index.php?page=event_detail&id=' . $eventId . '#chatCard'
    : '#chatCard';

function renderEventImageGallery(array $images, string $carouselId): void {
    if (empty($images)) {
        return;
    }

    if (count($images) === 1) {
        echo '<img src="' . e($images[0]) . '" class="img-fluid rounded mb-3" alt="Illustration de l\'evenement">';
        return;
    }
    ?>
    <div id="<?= e($carouselId) ?>" class="carousel slide mb-3">
        <div class="carousel-inner rounded overflow-hidden">
            <?php foreach ($images as $index => $image): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= e($image) ?>" class="d-block w-100" alt="Illustration <?= $index + 1 ?>" style="max-height: 320px; object-fit: cover;">
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#<?= e($carouselId) ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Precedent</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?= e($carouselId) ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Suivant</span>
        </button>
    </div>
    <?php
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    renderEventImageGallery($eventImages, 'eventModalCarousel');
    ?>
    <p><strong>Description :</strong></p>
    <p><?= nl2br(e($event['description'] ?: 'Aucune description.')) ?></p>
    <hr>
    <p><strong>Debut :</strong> <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></p>
    <p><strong>Fin :</strong> <?= date('d/m/Y H:i', strtotime($event['end_date'])) ?></p>
    <p><strong>Participants :</strong> <?= (int)$acceptedCount ?> / <?= (int)$event['max_players'] ?></p>
    <p><strong>Organisateur :</strong> <?= e($event['organizer_pseudo']) ?></p>

    <?php if ($isFinished): ?>
        <span class="badge bg-secondary">Termine</span>
    <?php elseif ($isOngoing): ?>
        <span class="badge bg-success">En cours</span>
    <?php else: ?>
        <span class="badge bg-primary">A venir</span>
    <?php endif; ?>

    <?php if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['joueur', 'organisateur', 'administrateur'], true)): ?>
        <div class="mt-3">
            <?php if ($userRegistrationStatus === null): ?>
                <a href="index.php?page=profile&action=register&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-success">S'inscrire</a>
            <?php elseif ($userRegistrationStatus === 'en_attente'): ?>
                <span class="badge bg-warning text-dark">Inscription en attente</span>
                <a href="index.php?page=profile&action=unregister&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm">Se desinscrire</a>
            <?php elseif ($userRegistrationStatus === 'accepte'): ?>
                <span class="badge bg-success">Inscrit</span>
                <?php if ($canJoinEvent): ?>
                    <a href="<?= $chatLink ?>" class="btn btn-primary btn-sm">Rejoindre</a>
                <?php elseif ($sessionPreparedEarly): ?>
                    <span class="badge bg-info text-dark">Session preparee - acces a l'heure de debut</span>
                <?php endif; ?>
                <a href="index.php?page=profile&action=unregister&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm">Se desinscrire</a>
            <?php elseif ($userRegistrationStatus === 'refuse'): ?>
                <span class="badge bg-danger">Inscription refusee</span>
            <?php endif; ?>

            <?php if (!$userFavorite): ?>
                <a href="index.php?page=profile&action=favorite&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-warning">Ajouter aux favoris</a>
            <?php else: ?>
                <span class="badge bg-warning text-dark">Favori</span>
            <?php endif; ?>
        </div>
    <?php elseif (!isLoggedIn()): ?>
        <div class="alert alert-info mt-3">Connectez-vous en tant que joueur pour vous inscrire.</div>
    <?php endif; ?>
    <?php
    exit;
}

$pageTitle = e($event['title']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body">
                <?php renderEventImageGallery($eventImages, 'eventPageCarousel'); ?>
                <div class="d-flex justify-content-between align-items-start">
                    <h1 class="card-title fw-bold"><?= e($event['title']) ?></h1>
                    <div>
                        <?php if ($isFinished): ?>
                            <span class="badge bg-secondary fs-6">Termine</span>
                        <?php elseif ($isOngoing): ?>
                            <span class="badge bg-success fs-6">En cours</span>
                        <?php else: ?>
                            <span class="badge bg-info fs-6">A venir</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted">Organise par <?= e($event['organizer_pseudo']) ?></p>
                <hr>
                <p><?= nl2br(e($event['description'] ?: 'Aucune description.')) ?></p>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Debut :</strong> <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></li>
                    <li class="list-group-item"><strong>Fin :</strong> <?= date('d/m/Y H:i', strtotime($event['end_date'])) ?></li>
                    <li class="list-group-item"><strong>Participants :</strong> <?= (int)$acceptedCount ?> / <?= (int)$event['max_players'] ?></li>
                </ul>
                <?php if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['joueur', 'organisateur', 'administrateur'], true)): ?>
                    <div class="mt-3">
                        <?php if ($userRegistrationStatus === null): ?>
                            <a href="index.php?page=profile&action=register&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-success">S'inscrire</a>
                        <?php elseif ($userRegistrationStatus === 'en_attente'): ?>
                            <span class="badge bg-warning text-dark">Inscription en attente</span>
                            <a href="index.php?page=profile&action=unregister&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm">Se desinscrire</a>
                        <?php elseif ($userRegistrationStatus === 'accepte'): ?>
                            <span class="badge bg-success">Inscrit</span>
                            <?php if ($canJoinEvent): ?>
                                <a href="<?= $chatLink ?>" class="btn btn-primary btn-sm">Rejoindre</a>
                            <?php elseif ($sessionPreparedEarly): ?>
                                <span class="badge bg-info text-dark">Session preparee - acces a l'heure de debut</span>
                            <?php endif; ?>
                            <a href="index.php?page=profile&action=unregister&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm">Se desinscrire</a>
                        <?php elseif ($userRegistrationStatus === 'refuse'): ?>
                            <span class="badge bg-danger">Inscription refusee</span>
                        <?php endif; ?>

                        <?php if (!$userFavorite): ?>
                            <a href="index.php?page=profile&action=favorite&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-warning">Ajouter aux favoris</a>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Favori</span>
                        <?php endif; ?>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <div class="alert alert-info mt-3">Connectez-vous en tant que joueur pour vous inscrire.</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canJoinEvent): ?>
        <div class="card shadow mt-4" id="chatCard">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="bi bi-chat-dots"></i> Fil de discussion
            </div>
            <div class="card-body">
                <div id="chatMessages" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                    <p class="text-muted text-center">Chargement des messages...</p>
                </div>
                <form id="chatForm">
                    <input type="hidden" id="chatEventId" value="<?= $eventId ?>">
                    <input type="hidden" id="chatCsrf" value="<?= csrfToken() ?>">
                    <div class="input-group">
                        <input type="text" id="chatInput" class="form-control" placeholder="Votre message..." maxlength="2000" required>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-body">
                <h5 class="card-title">Informations</h5>
                <p class="small text-muted">Les inscriptions sont conditionnees a la validation de l'organisateur ou a la jauge maximale.</p>
                <?php if ($isFinished): ?>
                    <div class="alert alert-secondary">Cet evenement est termine.</div>
                <?php elseif ($isOngoing): ?>
                    <div class="alert alert-success">Cet evenement est en cours.</div>
                <?php elseif ($sessionPreparedEarly): ?>
                    <div class="alert alert-info">La session a ete demarree en avance. Le bouton Rejoindre sera disponible a l'heure de debut.</div>
                <?php else: ?>
                    <div class="alert alert-info">Cet evenement n'a pas encore commence.</div>
                <?php endif; ?>

                <?php if ($canJoinEvent): ?>
                    <a href="<?= $chatLink ?>" class="btn btn-primary w-100 mt-2">Rejoindre l'evenement</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
