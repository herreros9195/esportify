<?php
/**
 * Fonctions utilitaires globales
 */

session_start();

require_once __DIR__ . '/../config/database.php';

/**
 * Redirige vers une URL interne
 */
function redirect(string $path): void {
    header("Location: " . $path);
    exit;
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 */
function hasRole(string $role): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === $role;
}

/**
 * Vérifie si l'utilisateur est au moins organisateur
 */
function isOrganizerOrAbove(): bool {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['user_role'] ?? '', ['organisateur', 'administrateur']);
}

/**
 * Vérifie si l'utilisateur est administrateur
 */
function isAdmin(): bool {
    return hasRole('administrateur');
}

/**
 * Sécurise une chaîne contre les attaques XSS
 */
function e(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un token CSRF
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 */
function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche un message flash
 */
function flashMessage(): string {
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $type = $msg['type'] ?? 'info';
        $text = e($msg['message'] ?? '');
        return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>{$text}<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
    return '';
}

/**
 * Définit un message flash
 */
function setFlash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Récupère les événements visibles (pour la page publique)
 */
function getVisibleEvents(PDO $pdo, array $filters = []): array {
    $sql = "SELECT e.*, u.pseudo as organizer_pseudo 
            FROM events e 
            JOIN users u ON e.organizer_id = u.id 
            WHERE e.visible = 1 AND e.status = 'valide'";
    $params = [];

    if (!empty($filters['organizer'])) {
        $sql .= " AND u.pseudo LIKE :organizer";
        $params[':organizer'] = '%' . $filters['organizer'] . '%';
    }
    if (!empty($filters['min_players'])) {
        $sql .= " AND e.max_players >= :min_players";
        $params[':min_players'] = (int)$filters['min_players'];
    }
    if (!empty($filters['date_from'])) {
        $sql .= " AND e.start_date >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }

    $sort = $filters['sort'] ?? 'date_asc';
    switch ($sort) {
        case 'players_desc':
            $sql .= " ORDER BY e.max_players DESC";
            break;
        case 'players_asc':
            $sql .= " ORDER BY e.max_players ASC";
            break;
        case 'date_desc':
            $sql .= " ORDER BY e.start_date DESC";
            break;
        default:
            $sql .= " ORDER BY e.start_date ASC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Récupère un événement par son ID
 */
function getEventById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT e.*, u.pseudo as organizer_pseudo 
                           FROM events e 
                           JOIN users u ON e.organizer_id = u.id 
                           WHERE e.id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $event = $stmt->fetch();
    return $event ?: null;
}

/**
 * Compte le nombre d'inscriptions acceptées pour un événement
 */
function countAcceptedRegistrations(PDO $pdo, int $eventId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations 
                           WHERE event_id = :event_id AND status = 'accepte'");
    $stmt->execute([':event_id' => $eventId]);
    return (int)($stmt->fetch()['count'] ?? 0);
}
?>
