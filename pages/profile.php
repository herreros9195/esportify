<?php
$pageTitle = 'Mon espace';
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    setFlash('Veuillez vous connecter pour accéder à votre espace.', 'warning');
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];

// Actions rapides
if (isset($_GET['action']) && isset($_GET['event_id']) && isset($_GET['csrf'])) {
    if (!verifyCsrf($_GET['csrf'])) {
        setFlash('Token de sécurité invalide.', 'danger');
        redirect('index.php?page=profile');
    }
    $eventId = (int)$_GET['event_id'];

    switch ($_GET['action']) {
        case 'register':
            // Vérifier si l'événement existe et a de la place
            $event = getEventById($pdo, $eventId);
            if ($event && $event['visible'] && $event['status'] === 'valide') {
                $count = countAcceptedRegistrations($pdo, $eventId);
                if ($count < $event['max_players']) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO event_registrations (event_id, user_id, status) VALUES (:eid, :uid, 'en_attente')");
                    $stmt->execute([':eid' => $eventId, ':uid' => $userId]);
                    setFlash('Inscription enregistrée (en attente de validation par l\'organisateur).', 'success');
                } else {
                    setFlash('La jauge maximale est atteinte.', 'danger');
                }
            }
            redirect('index.php?page=profile');
            break;

        case 'unregister':
            $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = :eid AND user_id = :uid");
            $stmt->execute([':eid' => $eventId, ':uid' => $userId]);
            setFlash('Vous vous êtes désinscrit de l\'événement.', 'info');
            redirect('index.php?page=profile');
            break;

        case 'favorite':
            $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, event_id) VALUES (:uid, :eid)");
            $stmt->execute([':uid' => $userId, ':eid' => $eventId]);
            setFlash('Ajouté aux favoris.', 'success');
            redirect('index.php?page=profile');
            break;

        case 'remove_favorite':
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND event_id = :eid");
            $stmt->execute([':uid' => $userId, ':eid' => $eventId]);
            setFlash('Retiré des favoris.', 'info');
            redirect('index.php?page=profile');
            break;
    }
}

// Création d'événement (accessible aussi aux organisateurs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $maxPlayers = (int)($_POST['max_players'] ?? 10);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    if ($title && $startDate && $endDate) {
        $stmt = $pdo->prepare("INSERT INTO events (title, description, max_players, start_date, end_date, organizer_id, status, visible) 
                               VALUES (:title, :desc, :max, :start, :end, :org, 'en_attente', 0)");
        $stmt->execute([
            ':title' => $title, ':desc' => $description, ':max' => $maxPlayers,
            ':start' => $startDate, ':end' => $endDate, ':org' => $userId
        ]);
        setFlash('Événement créé et soumis à validation.', 'success');
    } else {
        setFlash('Veuillez remplir les champs obligatoires.', 'danger');
    }
    redirect('index.php?page=profile');
}

// Récupération des données
$favoritesStmt = $pdo->prepare("SELECT e.* FROM favorites f JOIN events e ON f.event_id = e.id WHERE f.user_id = :uid ORDER BY e.start_date ASC");
$favoritesStmt->execute([':uid' => $userId]);
$favorites = $favoritesStmt->fetchAll();

$registrationsStmt = $pdo->prepare("SELECT e.*, er.status as reg_status FROM event_registrations er JOIN events e ON er.event_id = e.id WHERE er.user_id = :uid ORDER BY e.start_date ASC");
$registrationsStmt->execute([':uid' => $userId]);
$registrations = $registrationsStmt->fetchAll();

$myEventsStmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = :uid ORDER BY created_at DESC");
$myEventsStmt->execute([':uid' => $userId]);
$myEvents = $myEventsStmt->fetchAll();

$scoresStmt = $pdo->prepare("SELECT s.score, e.title, e.start_date FROM scores s JOIN events e ON s.event_id = e.id WHERE s.user_id = :uid ORDER BY e.start_date DESC");
$scoresStmt->execute([':uid' => $userId]);
$scores = $scoresStmt->fetchAll();
?>

<h2 class="fw-bold mb-4">Mon espace joueur</h2>

<div class="row g-4">
    <!-- Mes inscriptions -->
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white fw-bold">Mes inscriptions</div>
            <div class="card-body">
                <?php if (empty($registrations)): ?>
                    <p class="text-muted">Vous n'êtes inscrit à aucun événement.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($registrations as $reg): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($reg['title']) ?></strong><br>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($reg['start_date'])) ?></small>
                                    <?php if ($reg['reg_status'] === 'en_attente'): ?>
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    <?php elseif ($reg['reg_status'] === 'accepte'): ?>
                                        <span class="badge bg-success">Accepté</span>
                                        <?php if ($reg['started']): ?>
                                            <a href="index.php?page=event_detail&id=<?= (int)$reg['id'] ?>" class="btn btn-sm btn-success mt-1">Rejoindre</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <a href="index.php?page=profile&action=unregister&event_id=<?= (int)$reg['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm">Se désinscrire</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mes favoris -->
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header bg-warning text-dark fw-bold">Mes favoris</div>
            <div class="card-body">
                <?php if (empty($favorites)): ?>
                    <p class="text-muted">Aucun favori.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($favorites as $fav): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($fav['title']) ?></strong><br>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($fav['start_date'])) ?></small>
                                    <?php if ($fav['started']): ?>
                                        <a href="index.php?page=event_detail&id=<?= (int)$fav['id'] ?>" class="btn btn-sm btn-success mt-1">Rejoindre</a>
                                    <?php endif; ?>
                                </div>
                                <a href="index.php?page=profile&action=remove_favorite&event_id=<?= (int)$fav['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-secondary btn-sm">Retirer</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Créer un événement -->
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-info text-white fw-bold">Créer un événement</div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de joueurs max *</label>
                        <input type="number" name="max_players" class="form-control" required min="1" value="10">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date et heure de début *</label>
                            <input type="datetime-local" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date et heure de fin *</label>
                            <input type="datetime-local" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" name="create_event" class="btn btn-primary">Soumettre l'événement</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mon historique d'événements -->
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header bg-secondary text-white fw-bold">Mon historique d'événements proposés</div>
            <div class="card-body">
                <?php if (empty($myEvents)): ?>
                    <p class="text-muted">Aucun événement proposé.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($myEvents as $evt): ?>
                            <li class="list-group-item">
                                <strong><?= e($evt['title']) ?></strong>
                                <span class="badge bg-<?= $evt['status'] === 'valide' ? 'success' : ($evt['status'] === 'en_attente' ? 'warning text-dark' : 'danger') ?>">
                                    <?= e($evt['status']) ?>
                                </span>
                                <?php if ($evt['status'] !== 'non_valide' && strtotime($evt['start_date']) > time()): ?>
                                    <a href="index.php?page=organizer&edit=<?= (int)$evt['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Historique des scores -->
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-dark text-white fw-bold">Mes scores</div>
            <div class="card-body">
                <?php if (empty($scores)): ?>
                    <p class="text-muted">Aucun score enregistré.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Événement</th>
                                <th>Date</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scores as $sc): ?>
                                <tr>
                                    <td><?= e($sc['title']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($sc['start_date'])) ?></td>
                                    <td><span class="badge bg-primary"><?= (int)$sc['score'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
