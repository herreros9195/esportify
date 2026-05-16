<?php
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    setFlash('Veuillez vous connecter pour acceder a votre espace.', 'warning');
    redirect('index.php?page=login');
}

$pageTitle = 'Mon espace';
$userId = (int)$_SESSION['user_id'];
$editOwnEvent = null;

if (isset($_GET['edit'])) {
    $editId = (int)($_GET['edit'] ?? 0);
    $candidateEvent = getEventById($pdo, $editId);
    if ($candidateEvent && (int)$candidateEvent['organizer_id'] === $userId && strtotime($candidateEvent['start_date']) > time()) {
        $editOwnEvent = $candidateEvent;
    } else {
        setFlash('Cet evenement ne peut pas etre modifie depuis votre espace.', 'warning');
        redirect('index.php?page=profile');
    }
}

if (isset($_GET['action'], $_GET['event_id'], $_GET['csrf'])) {
    if (!verifyCsrf($_GET['csrf'])) {
        setFlash('Token de securite invalide.', 'danger');
        redirect('index.php?page=profile');
    }

    $eventId = (int)$_GET['event_id'];

    switch ($_GET['action']) {
        case 'register':
            $event = getEventById($pdo, $eventId);
            if ($event && $event['visible'] && $event['status'] === 'valide') {
                $existingStmt = $pdo->prepare("SELECT status FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id LIMIT 1");
                $existingStmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
                $existingStatus = $existingStmt->fetchColumn();

                if ($existingStatus === 'refuse') {
                    setFlash('Votre inscription a deja ete refusee pour cet evenement. Vous ne pouvez plus vous reinscrire.', 'danger');
                } elseif ($existingStatus === 'en_attente') {
                    setFlash('Votre inscription est deja en attente de validation.', 'warning');
                } elseif ($existingStatus === 'accepte') {
                    setFlash('Vous etes deja inscrit a cet evenement.', 'info');
                } else {
                    $count = countAcceptedRegistrations($pdo, $eventId);
                    if ($count < (int)$event['max_players']) {
                        $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (:event_id, :user_id, 'en_attente')");
                        $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
                        setFlash('Inscription enregistree (en attente de validation par l\'organisateur).', 'success');
                    } else {
                        setFlash('La jauge maximale est atteinte.', 'danger');
                    }
                }
            }
            redirect('index.php?page=profile');
            break;

        case 'unregister':
            $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id");
            $stmt->execute([':event_id' => $eventId, ':user_id' => $userId]);
            setFlash('Vous vous etes desinscrit de l\'evenement.', 'info');
            redirect('index.php?page=profile');
            break;

        case 'favorite':
            $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, event_id) VALUES (:user_id, :event_id)");
            $stmt->execute([':user_id' => $userId, ':event_id' => $eventId]);
            setFlash('Ajoute aux favoris.', 'success');
            redirect('index.php?page=profile');
            break;

        case 'remove_favorite':
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :user_id AND event_id = :event_id");
            $stmt->execute([':user_id' => $userId, ':event_id' => $eventId]);
            setFlash('Retire des favoris.', 'info');
            redirect('index.php?page=profile');
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_owned_event'])) {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $event = getEventById($pdo, $eventId);
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
    } elseif (!$event || (int)$event['organizer_id'] !== $userId || strtotime($event['start_date']) <= time()) {
        setFlash('Cet evenement ne peut plus etre modifie.', 'danger');
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
                        ':organizer_id' => $userId,
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

    redirect('index.php?page=profile');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
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
                        "INSERT INTO events (title, description, max_players, start_date, end_date, organizer_id, image_url, status, visible)
                         VALUES (:title, :description, :max_players, :start_date, :end_date, :organizer_id, :image_url, 'en_attente', 0)"
                    );
                    $stmt->execute([
                        ':title' => $title,
                        ':description' => $description,
                        ':max_players' => $maxPlayers,
                        ':start_date' => $startDate,
                        ':end_date' => $endDate,
                        ':organizer_id' => $userId,
                        ':image_url' => $eventImages[0],
                    ]);
                    $eventId = (int)$pdo->lastInsertId();
                    syncEventImages($pdo, $eventId, $eventImages);
                    $pdo->commit();
                    setFlash('Evenement cree et soumis a validation.', 'success');
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    deleteUploadedEventImage($uploadedImagePath);
                    setFlash('Une erreur est survenue pendant la creation de l\'evenement.', 'danger');
                }
            }
        }
    }

    redirect('index.php?page=profile');
}

$favoritesStmt = $pdo->prepare(
    "SELECT e.*, er.status AS reg_status
     FROM favorites f
     JOIN events e ON f.event_id = e.id
     LEFT JOIN event_registrations er ON er.event_id = e.id AND er.user_id = :registration_user_id
     WHERE f.user_id = :favorite_user_id
     ORDER BY e.start_date ASC"
);
$favoritesStmt->execute([
    ':registration_user_id' => $userId,
    ':favorite_user_id' => $userId,
]);
$favorites = $favoritesStmt->fetchAll();

$registrationsStmt = $pdo->prepare(
    "SELECT e.*, er.status AS reg_status
     FROM event_registrations er
     JOIN events e ON er.event_id = e.id
     WHERE er.user_id = :user_id
     ORDER BY e.start_date ASC"
);
$registrationsStmt->execute([':user_id' => $userId]);
$registrations = $registrationsStmt->fetchAll();

$myEventsStmt = $pdo->prepare("SELECT * FROM events WHERE organizer_id = :user_id ORDER BY created_at DESC");
$myEventsStmt->execute([':user_id' => $userId]);
$myEvents = $myEventsStmt->fetchAll();

$scoresStmt = $pdo->prepare(
    "SELECT s.score, e.title, e.start_date
     FROM scores s
     JOIN events e ON s.event_id = e.id
     WHERE s.user_id = :user_id
     ORDER BY e.start_date DESC"
);
$scoresStmt->execute([':user_id' => $userId]);
$scores = $scoresStmt->fetchAll();

$ownedEventImageList = $editOwnEvent
    ? implode(PHP_EOL, getEventImages($pdo, (int)$editOwnEvent['id'], $editOwnEvent['image_url'] ?? null))
    : '';

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="fw-bold mb-4">Mon espace joueur</h2>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white fw-bold">Mes inscriptions</div>
            <div class="card-body">
                <?php if (empty($registrations)): ?>
                    <p class="text-muted">Vous n'etes inscrit a aucun evenement.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($registrations as $registration): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($registration['title']) ?></strong><br>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($registration['start_date'])) ?></small>
                                    <?php if ($registration['reg_status'] === 'en_attente'): ?>
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    <?php elseif ($registration['reg_status'] === 'accepte'): ?>
                                        <span class="badge bg-success">Accepte</span>
                                        <?php if (strtotime($registration['end_date']) < time()): ?>
                                            <span class="badge bg-secondary">Termine</span>
                                        <?php elseif (isEventJoinable($registration, true)): ?>
                                            <a href="index.php?page=event_detail&id=<?= (int)$registration['id'] ?>" class="btn btn-sm btn-success mt-1">Rejoindre</a>
                                        <?php endif; ?>
                                    <?php elseif ($registration['reg_status'] === 'refuse'): ?>
                                        <span class="badge bg-danger">Refuse</span>
                                    <?php endif; ?>
                                </div>
                                <a href="index.php?page=profile&action=unregister&event_id=<?= (int)$registration['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-danger btn-sm">Se desinscrire</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header bg-warning text-dark fw-bold">Mes favoris</div>
            <div class="card-body">
                <?php if (empty($favorites)): ?>
                    <p class="text-muted">Aucun favori.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($favorites as $favorite): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($favorite['title']) ?></strong><br>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($favorite['start_date'])) ?></small>
                                    <?php if (strtotime($favorite['end_date']) < time()): ?>
                                        <span class="badge bg-secondary">Termine</span>
                                    <?php elseif (($favorite['reg_status'] ?? '') === 'accepte' && isEventJoinable($favorite, true)): ?>
                                        <a href="index.php?page=event_detail&id=<?= (int)$favorite['id'] ?>" class="btn btn-sm btn-success mt-1">Rejoindre</a>
                                    <?php elseif (($favorite['reg_status'] ?? '') === 'en_attente'): ?>
                                        <span class="badge bg-warning text-dark">Inscription en attente</span>
                                    <?php elseif (($favorite['reg_status'] ?? '') === 'refuse'): ?>
                                        <span class="badge bg-danger">Inscription refusee</span>
                                    <?php endif; ?>
                                </div>
                                <a href="index.php?page=profile&action=remove_favorite&event_id=<?= (int)$favorite['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-outline-secondary btn-sm">Retirer</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-info text-white fw-bold"><?= $editOwnEvent ? 'Modifier mon evenement' : 'Creer un evenement' ?></div>
            <div class="card-body">
                <form method="POST" action="" id="ownedEventForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?php if ($editOwnEvent): ?>
                        <input type="hidden" name="event_id" value="<?= (int)$editOwnEvent['id'] ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="title" class="form-control" required maxlength="150" value="<?= e($editOwnEvent['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($editOwnEvent['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URLs ou chemins d'images</label>
                        <textarea name="image_urls" class="form-control" rows="4" placeholder="Une URL ou un chemin d'image par ligne"><?= e($ownedEventImageList) ?></textarea>
                        <div class="form-text">Ajoutez une image par ligne. La premiere sera utilisee comme image principale.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ou televerser une image</label>
                        <input type="file" name="uploaded_image" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                        <div class="form-text">Si vous ne renseignez pas d'URL, vous pouvez envoyer une image ici (5 Mo max).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de joueurs max *</label>
                        <input type="number" name="max_players" class="form-control" required min="1" value="<?= (int)($editOwnEvent['max_players'] ?? 10) ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date et heure de debut *</label>
                            <input type="datetime-local" name="start_date" class="form-control" required value="<?= $editOwnEvent ? date('Y-m-d\TH:i', strtotime($editOwnEvent['start_date'])) : '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date et heure de fin *</label>
                            <input type="datetime-local" name="end_date" class="form-control" required value="<?= $editOwnEvent ? date('Y-m-d\TH:i', strtotime($editOwnEvent['end_date'])) : '' ?>">
                        </div>
                    </div>
                    <button type="submit" name="<?= $editOwnEvent ? 'update_owned_event' : 'create_event' ?>" class="btn btn-primary">
                        <?= $editOwnEvent ? 'Mettre a jour et resoumettre' : 'Soumettre l\'evenement' ?>
                    </button>
                    <?php if ($editOwnEvent): ?>
                        <a href="index.php?page=profile" class="btn btn-outline-secondary">Annuler</a>
                    <?php endif; ?>
                </form>
                <script>
                    const ownedStartInput = document.querySelector('#ownedEventForm input[name="start_date"]');
                    const ownedEndInput = document.querySelector('#ownedEventForm input[name="end_date"]');
                    if (ownedStartInput && ownedEndInput) {
                        if (ownedStartInput.value) {
                            ownedEndInput.min = ownedStartInput.value;
                        }
                        ownedStartInput.addEventListener('change', function () {
                            ownedEndInput.min = this.value;
                        });
                    }
                </script>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-header bg-secondary text-white fw-bold">Mon historique d'evenements proposes</div>
            <div class="card-body">
                <?php if (empty($myEvents)): ?>
                    <p class="text-muted">Aucun evenement propose.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($myEvents as $event): ?>
                            <li class="list-group-item">
                                <strong><?= e($event['title']) ?></strong>
                                <span class="badge bg-<?= $event['status'] === 'valide' ? 'success' : ($event['status'] === 'en_attente' ? 'warning text-dark' : 'danger') ?>">
                                    <?= e($event['status']) ?>
                                </span>
                                <?php if ($event['status'] !== 'non_valide' && strtotime($event['start_date']) > time()): ?>
                                    <a href="index.php?page=profile&edit=<?= (int)$event['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow">
            <div class="card-header bg-dark text-white fw-bold">Mes scores</div>
            <div class="card-body">
                <?php if (empty($scores)): ?>
                    <p class="text-muted">Aucun score enregistre.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Evenement</th>
                                <th>Date</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scores as $score): ?>
                                <tr>
                                    <td><?= e($score['title']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($score['start_date'])) ?></td>
                                    <td><span class="badge bg-primary"><?= (int)$score['score'] ?></span></td>
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
