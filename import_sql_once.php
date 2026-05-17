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
    exit('Fichier SQL introuvable.');
}

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('DROP TABLE IF EXISTS chat_messages, scores, favorites, event_registrations, event_images, events, users');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $count = 0;

    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }

        $pdo->exec($statement);
        $count++;
    }

    echo "Import SQL terminé avec succès. Blocs exécutés : " . $count;
} catch (Throwable $e) {
    http_response_code(500);
    echo "Erreur pendant l'import SQL : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}