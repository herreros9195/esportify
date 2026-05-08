<?php
$pageTitle = 'Espace Organisateur'; // Feature: Organizer Space
require_once __DIR__ . '/../includes/header.php';

if (!isOrganizerOrAbove()) {
    setFlash('Accès réservé aux organisateurs.', 'danger');
    redirect('index.php?page=home');
}

$organizerId = $_SESSION['user_id'];

// Démarrer un événement
if (isset($_GET['action']) && $_GET['action'] === 'start' && isset($_GET['event_id']) && isset($_GET['csrf'])) {
    if (!verifyCsrf($_GET['csrf'])) {
        setFlash('Token invalide.', 'danger');
        redirect('index.php?page=organizer');
    }
    $eventId = (int)$_GET['event_id'];
    $event = getEventById($pdo, $eventId);
    if ($event && $event['organizer_id'] == $organizerId && $event['status'] === 'valide') {
        // Vérifier que l'heure actuelle est dans les 30 min avant le début
        $startTimestamp = strtotime($event['start_date']);
        $now = time();
        if ($now >= ($startTimestamp - 1800) && $now <= strtotime($event['end_date'])) {
            $stmt = $pdo->prepare("UPDATE events SET started = 1, started_at = NOW() WHERE id = :id");
            $stmt->execute([':id' => $eventId]);
            setFlash('L\'événement a été démarré.', 'success');
        } else {
            setFlash('Le démarrage n\'est possible que 30 minutes avant l\'heure prévue.', 'warning');
        }
    }
    redirect('index.php?page=organizer');
}

// Accepter / Refuser une inscription
if (isset($_GET['action']) && in_array($_GET['action'], ['accept', 'reject']) && isset($_GET['reg_id']) && isset($_GET['csrf'])) {
    if (!verifyCsrf($_GET['csrf'])) {
        setFlash('Token invalide.', 'danger');
        redirect('index.php?page=organizer');
    }
    $regId = (int)$_GET['reg_id'];
    $newStatus = $_GET['action'] === 'accept' ? 'accepte' : 'refuse';
    $stmt = $pdo->prepare("UPDATE event_registrations SET status = :status WHERE id = :id AND event_id IN (SELECT id FROM events WHERE organizer_id = :org)");
    $stmt->execute([':status' => $newStatus, ':id' => $regId, ':org' => $organizerId]);
    setFlash('Inscription mise à jour.', 'success');
    redirect('index.php?page=organizer');
}

// Modifier un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $maxPlayers = (int)($_POST['max_players'] ?? 10);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    $stmt = $pdo->prepare("UPDATE events SET title = :title, description = :desc, max_players = :max, start_date = :start, end_date = :end, status = 'en_attente', visible = 0 WHERE id = :id AND organizer_id = :org");
    $stmt->execute([
        ':title' => $title, ':desc' => $description, ':max' => $maxPlayers,
        ':start' => $startDate, ':end' => $endDate, ':id' => $eventId, ':org' => $organizerId
    ]);
    setFlash('Événement modifié et soumis à nouvelle validation.', 'success');
    redirect('index.php?page=organizer');
}

// Récupérer les événements de l'organisateur
$eventsStmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = :org ORDER BY start_date ASC");
$eventsStmt->execute([':org' => $organizerId]);
$events = $eventsStmt->fetchAll();

// Inscriptions en attente pour ses événements
$regsStmt = $pdo->prepare("SELECT er.id, er.status, er.user_id, e.id as event_id, e.title as event_title, u.pseudo as user_pseudo 
                           FROM event_registrations er 
                           JOIN events e ON er.event_id = e.id 
                           JOIN users u ON er.user_id = u.id 
                           WHERE e.organizer_id = :org AND er.status = 'en_attente'");
$regsStmt->execute([':org' => $organizerId]);
$pendingRegs = $regsStmt->fetchAll();

$editEvent = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editEvent = getEventById($pdo, $editId);
    if ($editEvent && $editEvent['organizer_id'] != $organizerId) {
        $editEvent = null;
    }
}
?>

<h2 class="fw-bold mb-4">Espace Organisateur</h2>

<?php if ($editEvent): ?>
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white fw-bold">Modifier l'événement</div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="event_id" value="<?= (int)$editEvent['id'] ?>">
            <div class="mb-3">
                <label class="form-label">Titre</label>
                <input type="text" name="title" class="form-control" required value="<?= e($editEvent['title']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($editEvent['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Nombre de joueurs max</label>
                <input type="number" name="max_players" class="form-control" required min="1" value="<?= (int)$editEvent['max_players'] ?>">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date et heure de début</label>
                    <input type="datetime-local" name="start_date" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime($editEvent['start_date'])) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date et heure de fin</label>
                    <input type="datetime-local" name="end_date" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime($editEvent['end_date'])) ?>">
                </div>
            </div>
            <button type="submit" name="update_event" class="btn btn-success">Enregistrer les modifications</button>
            <a href="index.php?page=organizer" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header bg-dark text-white fw-bold">Mes événements</div>
            <div class="card-body">
                <?php if (empty($events)): ?>
                    <p class="text-muted">Aucun événement.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($events as $evt): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= e($evt['title']) ?></strong>
                                        <span class="badge bg-<?= $evt['status'] === 'valide' ? 'success' : ($evt['status'] === 'en_attente' ? 'warning text-dark' : 'danger') ?>">
                                            <?= e($evt['status']) ?>
                                        </span><br>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($evt['start_date'])) ?></small>
                                    </div>
                                    <div>
                                        <?php if (strtotime($evt['end_date']) < time()): ?>
                                            <span class="badge bg-secondary">Terminé</span>
                                        <?php elseif ($evt['status'] === 'valide' && !$evt['started'] && time() >= (strtotime($evt['start_date']) - 1800) && time() <= strtotime($evt['end_date'])): ?>
                                            <a href="index.php?page=organizer&action=start&event_id=<?= (int)$evt['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-success">Démarrer</a>
                                        <?php elseif ($evt['started']): ?>
                                            <span class="badge bg-success">En cours</span>
                                        <?php endif; ?>
                                        <?php if (strtotime($evt['start_date']) > time()): ?>
                                            <a href="index.php?page=organizer&edit=<?= (int)$evt['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header bg-info text-white fw-bold">Inscriptions en attente</div>
            <div class="card-body">
                <?php if (empty($pendingRegs)): ?>
                    <p class="text-muted">Aucune inscription en attente.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pendingRegs as $reg): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($reg['user_pseudo']) ?></strong> 
                                    <small class="text-muted">pour <?= e($reg['event_title']) ?></small>
                                </div>
                                <div>
                                    <a href="index.php?page=organizer&action=accept&reg_id=<?= (int)$reg['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-success">Accepter</a>
                                    <a href="index.php?page=organizer&action=reject&reg_id=<?= (int)$reg['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-danger">Refuser</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
