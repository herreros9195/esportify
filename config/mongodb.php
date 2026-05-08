<?php
/**
 * Configuration MongoDB (NoSQL)
 * Pour MongoDB Atlas : remplacez MONGODB_URI par votre URI de connexion
 * Exemple : mongodb+srv://user:password@cluster.mongodb.net/esportify_nosql?retryWrites=true&w=majority
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

// Mettez ici votre URI MongoDB Atlas (ou localhost:27017 pour local)
define('MONGODB_URI', 'mongodb://localhost:27017');
define('MONGODB_DB', 'esportify_nosql');

$mongoDB = null;

try {
    $mongoClient = new Client(MONGODB_URI);
    // Test de connexion
    $mongoClient->admin->command(['ping' => 1]);
    $mongoDB = $mongoClient->selectDatabase(MONGODB_DB);
} catch (Exception $e) {
    // MongoDB non disponible - le chat utilisera MySQL en fallback
    error_log("MongoDB non disponible : " . $e->getMessage());
    $mongoDB = null;
}
?>
