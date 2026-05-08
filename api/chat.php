<?php
/**
 * API asynchrone pour le fil de discussion (chat) d'un événement
 * Utilise MongoDB si disponible, sinon MySQL (fallback)
 * GET  : récupérer les messages d'un événement
 * POST : poster un nouveau message
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/mongodb.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);

// Vérifier que l'utilisateur est inscrit et accepté à l'événement
$stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = :eid AND user_id = :uid AND status = 'accepte' LIMIT 1");
$stmt->execute([':eid' => $eventId, ':uid' => $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Vous devez être inscrit à cet événement pour participer au chat']);
    exit;
}

// ===== MODE MONGODB =====
if ($mongoDB !== null) {
    $collection = $mongoDB->selectCollection('chat_messages');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($token)) {
            echo json_encode(['success' => false, 'error' => 'Token invalide']);
            exit;
        }

        $message = trim($_POST['message'] ?? '');
        if (empty($message) || strlen($message) > 2000) {
            echo json_encode(['success' => false, 'error' => 'Message invalide']);
            exit;
        }

        $collection->insertOne([
            'event_id'   => $eventId,
            'user_id'    => $_SESSION['user_id'],
            'pseudo'     => $_SESSION['user_pseudo'],
            'message'    => $message,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        echo json_encode(['success' => true, 'message' => 'Message envoyé']);
        exit;
    }

    // GET
    $cursor = $collection->find(
        ['event_id' => $eventId],
        ['sort' => ['created_at' => 1], 'limit' => 100]
    );

    $output = [];
    foreach ($cursor as $msg) {
        $date = $msg['created_at'] instanceof MongoDB\BSON\UTCDateTime
            ? $msg['created_at']->toDateTime()->format('d/m/Y H:i')
            : date('d/m/Y H:i', strtotime($msg['created_at']));
        $output[] = [
            'id' => (string)$msg['_id'],
            'pseudo' => e($msg['pseudo']),
            'message' => e($msg['message']),
            'created_at' => $date
        ];
    }

    echo json_encode(['success' => true, 'messages' => $output]);
    exit;
}

// ===== MODE MYSQL (FALLBACK) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        echo json_encode(['success' => false, 'error' => 'Token invalide']);
        exit;
    }

    $message = trim($_POST['message'] ?? '');
    if (empty($message) || strlen($message) > 2000) {
        echo json_encode(['success' => false, 'error' => 'Message invalide']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (event_id, user_id, pseudo, message) VALUES (:eid, :uid, :pseudo, :msg)");
    $stmt->execute([
        ':eid' => $eventId,
        ':uid' => $_SESSION['user_id'],
        ':pseudo' => $_SESSION['user_pseudo'],
        ':msg' => $message
    ]);

    echo json_encode(['success' => true, 'message' => 'Message envoyé']);
    exit;
}

// GET
$stmt = $pdo->prepare("SELECT id, pseudo, message, created_at FROM chat_messages WHERE event_id = :eid ORDER BY created_at ASC LIMIT 100");
$stmt->execute([':eid' => $eventId]);
$messages = $stmt->fetchAll();

$output = [];
foreach ($messages as $msg) {
    $output[] = [
        'id' => (int)$msg['id'],
        'pseudo' => e($msg['pseudo']),
        'message' => e($msg['message']),
        'created_at' => date('d/m/Y H:i', strtotime($msg['created_at']))
    ];
}

echo json_encode(['success' => true, 'messages' => $output]);
