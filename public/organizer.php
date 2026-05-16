<?php
require_once __DIR__ . '/../includes/functions.php';

if (!isOrganizerOrAbove()) {
    setFlash('Acces reserve aux organisateurs.', 'danger');
    redirect('index.php?page=home');
}

$pageTitle = 'Espace Organisateur';
$organizerId = (int)$_SESSION['user_id'];

if (isset($_GET['action'], $_GET['event_id'], $_GET['csrf']) && $_GET['action'] === 'start') {
    if (!verifyCsrf($_GET['csrf'])) {
        setFlash('Token invalide.', 'danger');
        redirect('index.php?page=organizer');
    }

    $eventId = (int)$_GET['event_id'];
    $event = getEventById($pdo, $eventId);

    if ($event && (isAdmin() || (int)$event['organizer_id'] === $organizerId) && $event['status'] === 'valide') {
        $startTimestamp = strtotime($event['start_date']);
        $now = time();
        if ($startTimestamp !== false && $now >= ($startTimestamp - 1800) && $now <= strtotime($event['end_date'])) {
            $stmt = $pdo->prepare("UPDATE events SET started = 1, started_at = NOW() WHERE id = :event_id");
            $stmt->execute([':event_id' => $eventId]);
            setFlash('L\'evenement a ete demarre.', 'success');
        } else {
            setFlash('Le demarrage n\'est possible que 30 minutes avant l\'heure prevue.', 'warning');
        }
    }

    redirect('index.php?page=organizer');
}

if (isset($_GET['action'], $_GET['rid'], $_GET['csrf']) && in_array($_GET['action'], ['accept', 'reject'], true)) {
    if (!verifyCsrf($_GET['csrf'])) {
        setFlash('Token invalide.', 'danger');
        redirect('index.php?page=organizer');
    }

    $registrationId = (int)$_GET['rid'];
    $newStatus = $_GET['action'] === 'accept' ? 'accepte' : 'refuse';

    if (isAdmin()) {
        $stmt = $pdo->prepare("UPDATE event_registrations SET status = :status WHERE id = :registration_id");
        $stmt->execute([':status' => $newStatus, ':registration_id' => $registrationId]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE event_registrations
             SET status = :status
             WHERE id = :registration_id
               AND event_id IN (SELECT id FROM events WHERE organizer_id = :organizer_id)"
        );
        $stmt->execute([
            ':status' => $newStatus,
            ':registration_id' => $registrationId,
            ':organizer_id' => $organizerId,
        ]);
    }

    if ($stmt->rowCount() > 0) {
        setFlash('Inscription mise a jour.', 'success');
    } else {
        setFlash('Aucune inscription n\'a ete mise a jour. Verifiez vos droits.', 'warning');
    }

    redirect('index.php?page=organizer');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $maxPlayers = (int)($_POST['max_players'] ?? 10);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $eventImages = parseEventImageReferences($_POST['image_urls'] ?? '');
    $uploadedImagePath = null;
    $uploadError = null;

    if (!verifyPostCsrf()) {
        setFlash('Jeton de securite invalide.', 'danger');
    } elseif ($title === '' || $startDate === '' || $endDate === '') {
        setFlash('Veuillez remplir les champs obligatoires.', 'danger');
    } elseif (strtotime($endDate) <= strtotime($startDate)) {
        setFlash('La date de fin doit etre posterieure a la date de debut.', 'danger');
    } else {
        $uploadedImagePath = saveUploadedEventImage($_FILES['uploaded_image'] ?? null, $uploadError);
        if ($uploadError !== null) {
            setFlash($uploadError, 'danger');
        } else {
            if ($uploadedImagePath !== null) {
                $eventImages[] = $uploadedImagePath;
                $eventImages = array_values(array_unique($eventImages));
            }
            if (empty($eventImages)) {
                setFlash('Ajoutez au moins une URL/chemin d\'image ou televersez une image.', 'danger');
            } else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE events
                         SET title = :title,
                             description = :description,
                             max_players = :max_players,
                             start_date = :start_date,
                             end_date = :end_date,
                             image_url = :image_url,
                             status = 'en_attente',
                             visible = 0
                         WHERE id = :event_id AND organizer_id = :organizer_id"
                    );
                    $stmt->execute([
                        ':title' => $title,
                        ':description' => $description,
                        ':max_players' => $maxPlayers,
                        ':start_date' => $startDate,
                        ':end_date' => $endDate,
                        ':image_url' => $eventImages[0],
                        ':event_id' => $eventId,
                        ':organizer_id' => $organizerId,
                    ]);
                    syncEventImages($pdo, $eventId, $eventImages);
                    $pdo->commit();
                    setFlash('Evenement modifie et soumis a une nouvelle validation.', 'success');
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    deleteUploadedEventImage($uploadedImagePath);
                    setFlash('Une erreur est survenue pendant la mise a jour de l\'evenement.', 'danger');
                }
            }
        }
    }

    redirect('index.php?page=organizer');
}

if (isAdmin()) {
    $eventsStmt = $pdo->prepare("SELECT * FROM events ORDER BY start_date ASC");
    $eventsStmt->execute();
} else {
    $eventsStmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = :organizer_id ORDER BY start_date ASC");
    $eventsStmt->execute([':organizer_id' => $organizerId]);
}
$events = $eventsStmt->fetchAll();

if (isAdmin()) {
    $pendingRegistrationsStmt = $pdo->prepare(
        "SELECT er.id, er.status, er.user_id, e.id AS event_id, e.title AS event_title, u.pseudo AS user_pseudo
         FROM event_registrations er
         JOIN events e ON er.event_id = e.id
         JOIN users u ON er.user_id = u.id
         WHERE er.status = 'en_attente'"
    );
    $pendingRegistrationsStmt->execute();
} else {
    $pendingRegistrationsStmt = $pdo->prepare(
        "SELECT er.id, er.status, er.user_id, e.id AS event_id, e.title AS event_title, u.pseudo AS user_pseudo
         FROM event_registrations er
         JOIN events e ON er.event_id = e.id
         JOIN users u ON er.user_id = u.id
         WHERE e.organizer_id = :organizer_id AND er.status = 'en_attente'"
    );
    $pendingRegistrationsStmt->execute([':organizer_id' => $organizerId]);
}
$pendingRegs = $pendingRegistrationsStmt->fetchAll();

$editEvent = null;
$editEventImageList = '';
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $candidateEvent = getEventById($pdo, $editId);
    if ($candidateEvent && (isAdmin() || (int)$candidateEvent['organizer_id'] === $organizerId)) {
        $editEvent = $candidateEvent;
        $editEventImageList = implode(PHP_EOL, getEventImages($pdo, (int)$candidateEvent['id'], $candidateEvent['image_url'] ?? null));
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="fw-bold mb-4">Espace Organisateur</h2>

<?php if ($editEvent): ?>
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white fw-bold">Modifier l'evenement</div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
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
                <label class="form-label">URLs ou chemins d'images</label>
                <textarea name="image_urls" class="form-control" rows="4"><?= e($editEventImageList) ?></textarea>
                <div class="form-text">Une image par ligne. La premiere sera utilisee comme image principale.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Ou televerser une image</label>
                <input type="file" name="uploaded_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                <div class="form-text">Si aucune URL n'est renseignee, vous pouvez envoyer une image ici (5 Mo max).</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Nombre de joueurs max</label>
                <input type="number" name="max_players" class="form-control" required min="1" value="<?= (int)$editEvent['max_players'] ?>">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date et heure de debut</label>
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
            <div class="card-header bg-dark text-white fw-bold">Mes evenements</div>
            <div class="card-body">
                <?php if (empty($events)): ?>
                    <p class="text-muted">Aucun evenement.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($events as $event): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?= e($event['title']) ?></strong>
                                        <span class="badge bg-<?= $event['status'] === 'valide' ? 'success' : ($event['status'] === 'en_attente' ? 'warning text-dark' : 'danger') ?>">
                                            <?= e($event['status']) ?>
                                        </span><br>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($event['start_date'])) ?></small>
                                    </div>
                                    <div>
                                        <?php if (strtotime($event['end_date']) < time()): ?>
                                            <span class="badge bg-secondary">Termine</span>
                                        <?php elseif (!empty($event['started'])): ?>
                                            <span class="badge bg-success">En cours</span>
                                        <?php elseif ($event['status'] === 'valide'): ?>
                                            <?php if (time() >= (strtotime($event['start_date']) - 1800) && time() <= strtotime($event['end_date'])): ?>
                                                <a href="index.php?page=organizer&action=start&event_id=<?= (int)$event['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-success">Demarrer</a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled title="Demarrage possible 30 min avant le debut">Demarrer</button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if (strtotime($event['start_date']) > time()): ?>
                                            <a href="index.php?page=organizer&edit=<?= (int)$event['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
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
                        <?php foreach ($pendingRegs as $registration): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($registration['user_pseudo']) ?></strong>
                                    <small class="text-muted">pour <?= e($registration['event_title']) ?></small>
                                </div>
                                <div>
                                    <a href="index.php?page=organizer&action=accept&rid=<?= (int)$registration['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-success">Accepter</a>
                                    <a href="index.php?page=organizer&action=reject&rid=<?= (int)$registration['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-danger">Refuser</a>
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
