<?php
/**
 * Configuration et connexion à MongoDB (NoSQL)
 * Utilisé pour le fil de discussion asynchrone des événements.
 * Extension requise : mongodb (pecl install mongodb)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

define('MONGODB_URI', 'mongodb://localhost:27017');
define('MONGODB_DB', 'esportify_nosql');

try {
    $mongoClient = new Client(MONGODB_URI);
    $mongoDB = $mongoClient->selectDatabase(MONGODB_DB);
} catch (Exception $e) {
    error_log("Erreur de connexion MongoDB : " . $e->getMessage());
    $mongoDB = null;
}
?>
