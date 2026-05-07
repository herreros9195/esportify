<?php
$pageTitle = 'Administration';
require_once __DIR__ . '/../includes/header.php';

if (!isAdmin()) {
    setFlash('Accès réservé aux administrateurs.', 'danger');
    redirect('index.php?page=home');
}

// Actions
if (isset($_GET['action']) && isset($_GET['id']) && isset($_GET['csrf'])) {
    if (!verifyCsrf($_GET['csrf'])) {
        setFlash('Token invalide.', 'danger');
        redirect('index.php?page=admin');
    }
    $id = (int)$_GET['id'];

    switch ($_GET['action']) {
        case 'validate_event':
            $pdo->prepare("UPDATE events SET status = 'valide', visible = 1 WHERE id = :id")->execute([':id' => $id]);
            setFlash('Événement validé.', 'success');
            break;
        case 'reject_event':
            $pdo->prepare("UPDATE events SET status = 'non_valide', visible = 0 WHERE id = :id")->execute([':id' => $id]);
            setFlash('Événement rejeté.', 'danger');
            break;
        case 'suspend_event':
            $pdo->prepare("UPDATE events SET status = 'suspendu', visible = 0 WHERE id = :id")->execute([':id' => $id]);
            setFlash('Événement suspendu.', 'warning');
            break;
        case 'promote_organizer':
            $pdo->prepare("UPDATE users SET role = 'organisateur' WHERE id = :id AND role = 'joueur'")->execute([':id' => $id]);
            setFlash('Utilisateur promu organisateur.', 'success');
            break;
        case 'demote_joueur':
            $pdo->prepare("UPDATE users SET role = 'joueur' WHERE id = :id AND role = 'organisateur'")->execute([':id' => $id]);
            setFlash('Rôle réduit à joueur.', 'info');
            break;
        case 'delete_user':
            $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = :id AND role != 'administrateur'")->execute([':id' => $id]);
            setFlash('Utilisateur désactivé.', 'info');
            break;
        case 'add_score':
            if (isset($_POST['user_id'], $_POST['event_id'], $_POST['score'])) {
                $stmt = $pdo->prepare("INSERT INTO scores (user_id, event_id, score) VALUES (:uid, :eid, :score) ON DUPLICATE KEY UPDATE score = :score");
                $stmt->execute([':uid' => (int)$_POST['user_id'], ':eid' => (int)$_POST['event_id'], ':score' => (int)$_POST['score']]);
                setFlash('Score ajouté / mis à jour.', 'success');
            }
            break;
    }
    redirect('index.php?page=admin');
}

// Stats pour le tableau de bord
$stats = [];
$stats['users'] = $pdo->query("SELECT COUNT(*) as c FROM users WHERE is_active = 1")->fetch()['c'];
$stats['events'] = $pdo->query("SELECT COUNT(*) as c FROM events")->fetch()['c'];
$stats['events_pending'] = $pdo->query("SELECT COUNT(*) as c FROM events WHERE status = 'en_attente'")->fetch()['c'];
$stats['registrations'] = $pdo->query("SELECT COUNT(*) as c FROM event_registrations WHERE status = 'accepte'")->fetch()['c'];

// Données
$pendingEvents = $pdo->query("SELECT e.*, u.pseudo as organizer_pseudo FROM events e JOIN users u ON e.organizer_id = u.id WHERE e.status = 'en_attente' ORDER BY e.created_at DESC")->fetchAll();
$allEvents = $pdo->query("SELECT e.*, u.pseudo as organizer_pseudo FROM events e JOIN users u ON e.organizer_id = u.id ORDER BY e.created_at DESC LIMIT 50")->fetchAll();
$allUsers = $pdo->query("SELECT id, pseudo, email, role, created_at FROM users WHERE is_active = 1 ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>

<h2 class="fw-bold mb-4">Espace Administrateur</h2>

<!-- Tableau de bord -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow">
            <div class="card-body text-center">
                <h3 class="card-title"><?= (int)$stats['users'] ?></h3>
                <p class="card-text">Utilisateurs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success shadow">
            <div class="card-body text-center">
                <h3 class="card-title"><?= (int)$stats['events'] ?></h3>
                <p class="card-text">Événements</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-dark bg-warning shadow">
            <div class="card-body text-center">
                <h3 class="card-title"><?= (int)$stats['events_pending'] ?></h3>
                <p class="card-text">En attente</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info shadow">
            <div class="card-body text-center">
                <h3 class="card-title"><?= (int)$stats['registrations'] ?></h3>
                <p class="card-text">Inscriptions</p>
            </div>
        </div>
    </div>
</div>

<!-- Événements en attente -->
<div class="card shadow mb-4">
    <div class="card-header bg-warning text-dark fw-bold">Modération - Événements en attente</div>
    <div class="card-body">
        <?php if (empty($pendingEvents)): ?>
            <p class="text-muted">Aucun événement en attente.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Organisateur</th>
                            <th>Dates</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingEvents as $evt): ?>
                            <tr>
                                <td><?= e($evt['title']) ?></td>
                                <td><?= e($evt['organizer_pseudo']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($evt['start_date'])) ?></td>
                                <td>
                                    <a href="index.php?page=admin&action=validate_event&id=<?= (int)$evt['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-success">Valider</a>
                                    <a href="index.php?page=admin&action=reject_event&id=<?= (int)$evt['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-danger">Rejeter</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tous les événements -->
<div class="card shadow mb-4">
    <div class="card-header bg-dark text-white fw-bold">Tous les événements</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Organisateur</th>
                        <th>Status</th>
                        <th>Visible</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allEvents as $evt): ?>
                        <tr>
                            <td><?= (int)$evt['id'] ?></td>
                            <td><?= e($evt['title']) ?></td>
                            <td><?= e($evt['organizer_pseudo']) ?></td>
                            <td><span class="badge bg-<?= $evt['status'] === 'valide' ? 'success' : ($evt['status'] === 'en_attente' ? 'warning text-dark' : 'danger') ?>"><?= e($evt['status']) ?></span></td>
                            <td><?= $evt['visible'] ? 'Oui' : 'Non' ?></td>
                            <td>
                                <?php if ($evt['status'] !== 'suspendu'): ?>
                                    <a href="index.php?page=admin&action=suspend_event&id=<?= (int)$evt['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-warning">Suspendre</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Gestion des utilisateurs -->
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white fw-bold">Gestion des utilisateurs</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pseudo</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsers as $usr): ?>
                        <tr>
                            <td><?= (int)$usr['id'] ?></td>
                            <td><?= e($usr['pseudo']) ?></td>
                            <td><?= e($usr['email']) ?></td>
                            <td><?= e($usr['role']) ?></td>
                            <td><?= date('d/m/Y', strtotime($usr['created_at'])) ?></td>
                            <td>
                                <?php if ($usr['role'] === 'joueur'): ?>
                                    <a href="index.php?page=admin&action=promote_organizer&id=<?= (int)$usr['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-info">Promouvoir organisateur</a>
                                <?php elseif ($usr['role'] === 'organisateur'): ?>
                                    <a href="index.php?page=admin&action=demote_joueur&id=<?= (int)$usr['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-secondary">Rétrograder joueur</a>
                                <?php endif; ?>
                                <?php if ($usr['role'] !== 'administrateur'): ?>
                                    <a href="index.php?page=admin&action=delete_user&id=<?= (int)$usr['id'] ?>&csrf=<?= csrfToken() ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la désactivation ?')">Désactiver</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Ajouter un score -->
<div class="card shadow">
    <div class="card-header bg-secondary text-white fw-bold">Ajouter un score</div>
    <div class="card-body">
        <form method="POST" action="index.php?page=admin&action=add_score&id=0&csrf=<?= csrfToken() ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Joueur</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Choisir...</option>
                        <?php foreach ($allUsers as $usr): ?>
                            <option value="<?= (int)$usr['id'] ?>"><?= e($usr['pseudo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Événement</label>
                    <select name="event_id" class="form-select" required>
                        <option value="">Choisir...</option>
                        <?php foreach ($allEvents as $evt): ?>
                            <option value="<?= (int)$evt['id'] ?>"><?= e($evt['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Score</label>
                    <input type="number" name="score" class="form-control" required min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
