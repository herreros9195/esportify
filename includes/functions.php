<?php
/**
 * Fonctions utilitaires globales
 */

date_default_timezone_set('Europe/Paris');
session_start();

require_once __DIR__ . '/../config/database.php';

define('APP_MAIL_LOG_DIR', dirname(__DIR__) . '/logs/mails');
define('EVENT_IMAGE_UPLOAD_DIR', dirname(__DIR__) . '/assets/uploads/events');
define('EVENT_IMAGE_UPLOAD_BASE_PATH', 'assets/uploads/events');

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
 * VÃ©rifie si l'utilisateur peut utiliser les parcours joueur.
 */
function canUsePlayerFeatures(): bool {
    return isLoggedIn() && in_array($_SESSION['user_role'] ?? '', ['joueur', 'organisateur', 'administrateur'], true);
}

/**
 * Sécurise une chaîne contre les attaques XSS
 */
function e(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Nettoie un texte simple issu d'un formulaire.
 */
function cleanInput(?string $text): string {
    return trim((string)($text ?? ''));
}

/**
 * Valide une reference d'image distante ou locale.
 */
function isValidImageReference(string $value): bool {
    if ($value === '') {
        return false;
    }

    if (filter_var($value, FILTER_VALIDATE_URL)) {
        return true;
    }

    return (bool)preg_match('/^[a-zA-Z0-9_\/\.-]+\.(jpg|jpeg|png|webp|gif)$/i', $value);
}

/**
 * Construit une liste d'images depuis un champ texte multi-lignes.
 */
function parseEventImageReferences(?string $raw): array {
    $lines = preg_split('/\r\n|\r|\n/', (string)($raw ?? '')) ?: [];
    $images = [];

    foreach ($lines as $line) {
        $candidate = cleanInput($line);
        if ($candidate === '' || !isValidImageReference($candidate)) {
            continue;
        }
        $images[] = $candidate;
    }

    return array_values(array_unique($images));
}

/**
 * Enregistre une image envoyee depuis un formulaire multipart.
 */
function saveUploadedEventImage(?array $file, ?string &$error = null): ?string {
    $error = null;

    if (!is_array($file) || !isset($file['error'])) {
        return null;
    }

    $uploadError = (int)$file['error'];
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($uploadError !== UPLOAD_ERR_OK) {
        $error = 'Le televersement de l\'image a echoue.';
        return null;
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);

    if ($tmpName === '' || !is_uploaded_file($tmpName) || $size <= 0) {
        $error = 'Le fichier image envoye est invalide.';
        return null;
    }

    if ($size > 5 * 1024 * 1024) {
        $error = 'L\'image ne doit pas depasser 5 Mo.';
        return null;
    }

    $imageInfo = @getimagesize($tmpName);
    $mime = $imageInfo['mime'] ?? '';
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowedMimes[$mime])) {
        $error = 'Formats acceptes : JPG, PNG, WEBP ou GIF.';
        return null;
    }

    if (!is_dir(EVENT_IMAGE_UPLOAD_DIR) && !mkdir(EVENT_IMAGE_UPLOAD_DIR, 0775, true) && !is_dir(EVENT_IMAGE_UPLOAD_DIR)) {
        $error = 'Impossible de preparer le dossier d\'upload des images.';
        return null;
    }

    try {
        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $allowedMimes[$mime];
    } catch (Throwable $e) {
        $filename = uniqid('event_', true) . '.' . $allowedMimes[$mime];
    }

    $destination = EVENT_IMAGE_UPLOAD_DIR . '/' . $filename;
    if (!move_uploaded_file($tmpName, $destination)) {
        $error = 'Impossible d\'enregistrer l\'image sur le serveur.';
        return null;
    }

    return EVENT_IMAGE_UPLOAD_BASE_PATH . '/' . $filename;
}

/**
 * Supprime une image evenement precedemment uploadee par l'application.
 */
function deleteUploadedEventImage(?string $imagePath): void {
    $imagePath = cleanInput($imagePath);
    $prefix = EVENT_IMAGE_UPLOAD_BASE_PATH . '/';

    if ($imagePath === '' || strncmp($imagePath, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $absolutePath = dirname(__DIR__) . '/' . $imagePath;
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
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
 * VÃ©rifie la validitÃ© du token CSRF soumis en POST.
 */
function verifyPostCsrf(): bool {
    return verifyCsrf($_POST['csrf_token'] ?? '');
}

/**
 * VÃ©rifie la robustesse minimale d'un mot de passe.
 */
function validateStrongPassword(string $password): bool {
    return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $password);
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
 * VÃ©rifie l'existence d'un pseudo dans la base.
 */
function pseudoExists(PDO $pdo, string $pseudo): bool {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE pseudo = :pseudo LIMIT 1");
    $stmt->execute([':pseudo' => $pseudo]);
    return (bool)$stmt->fetch();
}

/**
 * Envoi d'email avec fallback vers un fichier de log local.
 */
function sendAppEmail(string $to, string $subject, string $message, string $from = 'noreply@esportify.local'): bool {
    $headers = "From: {$from}\r\nContent-Type: text/plain; charset=utf-8";
    $sent = @mail($to, $subject, $message, $headers);

    if ($sent) {
        return true;
    }

    if (!is_dir(APP_MAIL_LOG_DIR)) {
        mkdir(APP_MAIL_LOG_DIR, 0777, true);
    }

    $filename = APP_MAIL_LOG_DIR . '/' . date('Y-m-d_H-i-s') . '_' . uniqid('mail_', true) . '.txt';
    $content = "Date: " . date('d/m/Y H:i:s') . PHP_EOL;
    $content .= "From: {$from}" . PHP_EOL;
    $content .= "To: {$to}" . PHP_EOL;
    $content .= "Subject: {$subject}" . PHP_EOL;
    $content .= str_repeat('-', 24) . PHP_EOL;
    $content .= $message . PHP_EOL;

    file_put_contents($filename, $content);
    return true;
}

/**
 * Récupère les événements visibles (pour la page publique)
 */
function getVisibleEvents(PDO $pdo, array $filters = []): array {
    $sql = "SELECT e.*, ei.image_path AS main_image_path, u.pseudo as organizer_pseudo 
            FROM events e 
            LEFT JOIN event_images ei ON ei.event_id = e.id AND ei.is_main = 1
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
    $events = $stmt->fetchAll();

    foreach ($events as &$event) {
        if (!empty($event['main_image_path'])) {
            $event['image_url'] = $event['main_image_path'];
        }
        unset($event['main_image_path']);
    }

    return $events;
}

/**
 * Récupère un événement par son ID
 */
function getEventById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT e.*, ei.image_path AS main_image_path, u.pseudo as organizer_pseudo 
                           FROM events e 
                           LEFT JOIN event_images ei ON ei.event_id = e.id AND ei.is_main = 1
                           JOIN users u ON e.organizer_id = u.id 
                           WHERE e.id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $event = $stmt->fetch();
    if (!$event) {
        return null;
    }

    if (!empty($event['main_image_path'])) {
        $event['image_url'] = $event['main_image_path'];
    }
    unset($event['main_image_path']);

    return $event;
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

/**
 * Retourne la liste des images associees a un evenement.
 */
function getEventImages(PDO $pdo, int $eventId, ?string $fallbackImage = null): array {
    $stmt = $pdo->prepare("SELECT image_path FROM event_images WHERE event_id = :event_id ORDER BY is_main DESC, id ASC");
    $stmt->execute([':event_id' => $eventId]);
    $images = array_values(array_filter(array_map(static function ($row) {
        return cleanInput($row['image_path'] ?? '');
    }, $stmt->fetchAll())));

    if (empty($images) && !empty($fallbackImage)) {
        $images[] = $fallbackImage;
    }

    return array_values(array_unique($images));
}

/**
 * Synchronise les images d'un evenement dans la table relationnelle.
 */
function syncEventImages(PDO $pdo, int $eventId, array $images): void {
    $deleteStmt = $pdo->prepare("DELETE FROM event_images WHERE event_id = :event_id");
    $deleteStmt->execute([':event_id' => $eventId]);

    if (empty($images)) {
        return;
    }

    $insertStmt = $pdo->prepare("INSERT INTO event_images (event_id, image_path, is_main) VALUES (:event_id, :image_path, :is_main)");
    foreach (array_values($images) as $index => $imagePath) {
        $insertStmt->execute([
            ':event_id' => $eventId,
            ':image_path' => $imagePath,
            ':is_main' => $index === 0 ? 1 : 0,
        ]);
    }
}

/**
 * Indique si un evenement est rejoignable par le joueur.
 */
function isEventJoinable(array $event, bool $isRegistered = true): bool {
    if (!$isRegistered) {
        return false;
    }

    $start = strtotime($event['start_date'] ?? '');
    $end = strtotime($event['end_date'] ?? '');
    $now = time();

    if ($start === false || $end === false) {
        return false;
    }

    return !empty($event['started']) && $now >= $start && $now <= $end;
}
?>
