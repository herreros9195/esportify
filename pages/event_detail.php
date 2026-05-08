<?php
require_once __DIR__ . '/../includes/functions.php';

$eventId = (int)($_GET['id'] ?? 0);
$event = getEventById($pdo, $eventId);

if (!$event || !$event['visible'] || $event['status'] !== 'valide') {
    http_response_code(404);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo '<div class="alert alert-danger">Événement introuvable ou non visible.</div>';
        exit;
    }
    $pageTitle = 'Événement introuvable';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-danger">Événement introuvable ou non visible.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$acceptedCount = countAcceptedRegistrations($pdo, $eventId);
$userRegistered = false;
$userFavorite = false;
$isFinished = strtotime($event['end_date']) < time();
$isOngoing = $event['started'] && !$isFinished;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT status FROM event_registrations WHERE event_id = :eid AND user_id = :uid LIMIT 1");
    $stmt->execute([':eid' => $eventId, ':uid' => $_SESSION['user_id']]);
    $reg = $stmt->fetch();
    $userRegistered = ($reg && $reg['status'] === 'accepte');

    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :uid AND event_id = :eid LIMIT 1");
    $stmt->execute([':uid' => $_SESSION['user_id'], ':eid' => $eventId]);
    $userFavorite = (bool)$stmt->fetch();
}

// Si appel AJAX (modal)
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ?>
    <p><strong>Description :</strong></p>
    <p><?= nl2br(e($event['description'] ?: 'Aucune description.')) ?></p>
    <hr>
    <p><strong>📅 Début :</strong> <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></p>
    <p><strong>📅 Fin :</strong> <?= date('d/m/Y H:i', strtotime($event['end_date'])) ?></p>
    <p><strong>👥 Participants :</strong> <?= (int)$acceptedCount ?> / <?= (int)$event['max_players'] ?></p>
    <p><strong>👤 Organisateur :</strong> <?= e($event['organizer_pseudo']) ?></p>
    <?php if ($isFinished): ?>
        <span class="badge bg-secondary">Terminé</span>
    <?php elseif ($isOngoing): ?>
        <span class="badge bg-success">En cours</span>
    <?php endif; ?>

    <?php if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['joueur', 'organisateur', 'administrateur'])): ?>
        <div class="mt-3">
            <?php if (!$userRegistered): ?>
                <a href="index.php?page=profile&action=register&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-success">S'inscrire</a>
            <?php else: ?>
                <span class="badge bg-success">Inscrit</span>
                <a href="index.php?page=profile&action=unregister&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm">Se désinscrire</a>
            <?php endif; ?>

            <?php if (!$userFavorite): ?>
                <a href="index.php?page=profile&action=favorite&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-warning">⭐ Ajouter aux favoris</a>
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

// Page complète
$pageTitle = e($event['title']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow">
            <?php if (!empty($event['image_url'])): ?>
                <img src="<?= e($event['image_url']) ?>" class="card-img-top" alt="<?= e($event['title']) ?>" style="max-height: 300px; object-fit: cover;">
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h1 class="card-title fw-bold"><?= e($event['title']) ?></h1>
                    <div>
                        <?php if ($isFinished): ?>
                            <span class="badge bg-secondary fs-6">Terminé</span>
                        <?php elseif ($isOngoing): ?>
                            <span class="badge bg-success fs-6">En cours</span>
                        <?php else: ?>
                            <span class="badge bg-info fs-6">À venir</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted">Organisé par <?= e($event['organizer_pseudo']) ?></p>
                <hr>
                <p><?= nl2br(e($event['description'] ?: 'Aucune description.')) ?></p>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>📅 Début :</strong> <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></li>
                    <li class="list-group-item"><strong>📅 Fin :</strong> <?= date('d/m/Y H:i', strtotime($event['end_date'])) ?></li>
                    <li class="list-group-item"><strong>👥 Participants :</strong> <?= (int)$acceptedCount ?> / <?= (int)$event['max_players'] ?></li>
                </ul>
                <?php if (isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['joueur', 'organisateur', 'administrateur'])): ?>
                    <div class="mt-3">
                        <?php if (!$userRegistered): ?>
                            <a href="index.php?page=profile&action=register&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-success">S'inscrire</a>
                        <?php else: ?>
                            <span class="badge bg-success">Inscrit</span>
                            <a href="index.php?page=profile&action=unregister&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm">Se désinscrire</a>
                        <?php endif; ?>
                        <?php if (!$userFavorite): ?>
                            <a href="index.php?page=profile&action=favorite&event_id=<?= $eventId ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-warning">⭐ Ajouter aux favoris</a>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Favori</span>
                        <?php endif; ?>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <div class="alert alert-info mt-3">Connectez-vous en tant que joueur pour vous inscrire.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fil de discussion -->
        <?php if ($userRegistered): ?>
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
                <p class="small text-muted">Les inscriptions sont conditionnées à la validation de l'organisateur ou à la jauge maximale.</p>
                <?php if ($isFinished): ?>
                    <div class="alert alert-secondary">Cet événement est terminé.</div>
                <?php elseif ($isOngoing): ?>
                    <div class="alert alert-success">Cet événement est en cours !</div>
                <?php else: ?>
                    <div class="alert alert-info">Cet événement n'a pas encore commencé.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
