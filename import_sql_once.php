<?php

$expectedToken = getenv('IMPORT_SQL_TOKEN') ?: '';
$givenToken = $_GET['token'] ?? '';

if (!$expectedToken || !hash_equals($expectedToken, $givenToken)) {
    http_response_code(403);
    exit('Accès refusé.');
}

require __DIR__ . '/config/database.php';

$sqlFile = __DIR__ . '/sql/esportify_railway.sql';

if (!file_exists($sqlFile)) {
    http_response_code(500);
    exit('Fichier SQL introuvable : ' . htmlspecialchars($sqlFile));
}

try {
    $sql = file_get_contents($sqlFile);

    // Supprime le BOM UTF-8 éventuel
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);

    // Normalise les retours ligne
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);

    // Supprime les commentaires SQL et les lignes vides
    $lines = explode("\n", $sql);
    $cleanLines = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            continue;
        }

        if (str_starts_with($trimmed, '--')) {
            continue;
        }

        if (stripos($trimmed, 'DROP DATABASE') === 0) {
            continue;
        }

        if (stripos($trimmed, 'CREATE DATABASE') === 0) {
            continue;
        }

        if (stripos($trimmed, 'USE esportify') === 0) {
            continue;
        }

        $cleanLines[] = $line;
    }

    $sql = implode("\n", $cleanLines);

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('DROP TABLE IF EXISTS chat_messages, scores, favorites, event_registrations, event_images, events, users');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    // Exécute tout le script d'un coup
    $pdo->exec($sql);

    echo "Import SQL terminé avec succès.";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Erreur pendant l'import SQL : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}