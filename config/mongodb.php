<?php
/**
 * Configuration MongoDB (NoSQL) - MongoDB Atlas
 * Utilisé pour le fil de discussion asynchrone des événements.
 */

// URI MongoDB Atlas (Cloud)
define('MONGODB_URI', 'mongodb+srv://esportify_user:BDgKmf5QK92GZJcH@cluster0.97j9zkv.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0');
define('MONGODB_DB', 'esportify_nosql');

$mongoDB = null;

try {
    $mongoManager = new MongoDB\Driver\Manager(MONGODB_URI);
    // Test de connexion avec une commande ping
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $mongoManager->executeCommand('admin', $command);
    $mongoDB = $mongoManager;
} catch (Throwable $e) {
    error_log("MongoDB non disponible : " . $e->getMessage());
    $mongoDB = null;
}
?>
