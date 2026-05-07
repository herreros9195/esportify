<?php
$pageTitle = 'Tous les événements'; // Feature: Events Management
require_once __DIR__ . '/../includes/header.php';

$events = getVisibleEvents($pdo);
?>

<h2 class="fw-bold mb-4">Tous les événements</h2>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="col-md-3">
                <label for="sort" class="form-label">Trier par</label>
                <select id="sort" name="sort" class="form-select">
                    <option value="date_asc">Date croissante</option>
                    <option value="date_desc">Date décroissante</option>
                    <option value="players_asc">Joueurs (croissant)</option>
                    <option value="players_desc">Joueurs (décroissant)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="organizer" class="form-label">Organisateur</label>
                <input type="text" id="organizer" name="organizer" class="form-control" placeholder="Pseudo...">
            </div>
            <div class="col-md-3">
                <label for="min_players" class="form-label">Min. joueurs</label>
                <input type="number" id="min_players" name="min_players" class="form-control" min="1">
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">À partir du</label>
                <input type="date" id="date_from" name="date_from" class="form-control">
            </div>
            <div class="col-12 text-end">
                <button type="button" id="resetFilters" class="btn btn-outline-secondary">Réinitialiser</button>
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Liste des événements -->
<div id="eventsContainer" class="row g-4">
    <?php foreach ($events as $event): ?>
        <div class="col-md-4 event-card" data-id="<?= (int)$event['id'] ?>">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= e($event['title']) ?></h5>
                    <p class="card-text text-muted small mb-1">
                        👥 <?= (int)$event['max_players'] ?> joueurs max<br>
                        📅 <?= date('d/m/Y H:i', strtotime($event['start_date'])) ?><br>
                        👤 <?= e($event['organizer_pseudo']) ?>
                    </p>
                    <button class="btn btn-outline-primary btn-sm mt-2 btn-details" data-id="<?= (int)$event['id'] ?>">
                        Voir les détails
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal détails événement -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Détail de l'événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBody">
                Chargement...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
