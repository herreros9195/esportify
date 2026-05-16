<?php
/**
 * Configuration MongoDB (NoSQL)
 * Utilisee pour le fil de discussion asynchrone des evenements.
 * La connexion reste optionnelle : en son absence, le fallback MySQL prend le relai.
 */

$mongoLocalConfig = [];
$mongoLocalConfigFile = __DIR__ . '/mongodb.local.php';

if (file_exists($mongoLocalConfigFile)) {
    $loadedConfig = require $mongoLocalConfigFile;
    if (is_array($loadedConfig)) {
        $mongoLocalConfig = $loadedConfig;
    }
}

$mongoUri = $mongoLocalConfig['uri'] ?? (getenv('MONGODB_URI') ?: '');
$mongoDbName = $mongoLocalConfig['db'] ?? (getenv('MONGODB_DB') ?: 'esportify_nosql');

if (!defined('MONGODB_URI')) {
    define('MONGODB_URI', $mongoUri);
}

if (!defined('MONGODB_DB')) {
    define('MONGODB_DB', $mongoDbName);
}

$mongoDB = null;

if (!empty(MONGODB_URI)) {
    try {
        $mongoManager = new MongoDB\Driver\Manager(MONGODB_URI);
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $mongoManager->executeCommand('admin', $command);
        $mongoDB = $mongoManager;
    } catch (Throwable $e) {
        error_log("MongoDB non disponible : " . $e->getMessage());
        $mongoDB = null;
    }
}
?>
