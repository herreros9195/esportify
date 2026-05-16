<?php
/**
 * Configuration et connexion a la base de donnees relationnelle (MySQL/MariaDB)
 * Utilisation de PDO avec gestion des erreurs en mode exception.
 * Compatible local (WAMP/XAMPP) et environnement de deploiement (via DATABASE_URL).
 */

if (!empty($_ENV['DATABASE_URL']) || !empty(getenv('DATABASE_URL'))) {
    $dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    $parsed = parse_url($dbUrl);
    define('DB_HOST', $parsed['host'] ?? 'localhost');
    define('DB_NAME', ltrim($parsed['path'] ?? '/esportify', '/'));
    define('DB_USER', $parsed['user'] ?? 'root');
    define('DB_PASS', $parsed['pass'] ?? '');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'esportify');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Erreur de connexion PDO : " . $e->getMessage());
    die("Une erreur de connexion a la base de donnees est survenue. Veuillez reessayer plus tard.");
}
?>
