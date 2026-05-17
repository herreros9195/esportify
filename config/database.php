<?php
/**
 * Configuration et connexion à la base de données relationnelle MySQL/MariaDB.
 * Compatible :
 * - local : WAMP / XAMPP
 * - Railway : MYSQL_URL ou variables MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
 * - autre hébergeur : DATABASE_URL
 */

$databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: null;

if (!empty($databaseUrl)) {
    $parsed = parse_url($databaseUrl);

    define('DB_HOST', $parsed['host'] ?? 'localhost');
    define('DB_PORT', $parsed['port'] ?? 3306);
    define('DB_NAME', isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'esportify');
    define('DB_USER', isset($parsed['user']) ? urldecode($parsed['user']) : 'root');
    define('DB_PASS', isset($parsed['pass']) ? urldecode($parsed['pass']) : '');
} else {
    define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);
    define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_DATABASE') ?: 'esportify');
    define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USERNAME') ?: 'root');
    define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '');
}

define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Erreur de connexion PDO : " . $e->getMessage());
    die("Une erreur de connexion à la base de données est survenue. Veuillez réessayer plus tard.");
}