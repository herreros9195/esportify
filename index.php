<?php
ob_start();
/**
 * Routeur principal d'Esportify
 * Redirige vers les vues publiques demandées via le paramètre ?page=
 */

require_once __DIR__ . '/includes/functions.php';

$page = $_GET['page'] ?? 'home';
$allowedPages = [
    'home', 'events', 'event_detail', 'login', 'register', 'logout',
    'profile', 'organizer', 'admin', 'contact'
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$pageFile = __DIR__ . '/public/' . $page . '.php';

if (file_exists($pageFile)) {
    require_once $pageFile;
} else {
    http_response_code(404);
    $pageTitle = 'Page non trouvée';
    require_once __DIR__ . '/includes/header.php';
    echo "<div class='alert alert-danger'>Page introuvable.</div>";
    require_once __DIR__ . '/includes/footer.php';
}
