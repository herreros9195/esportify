<?php
/**
 * API asynchrone pour le fil de discussion d'un evenement.
 * Utilise MongoDB si disponible, sinon MySQL en fallback.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/mongodb.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifie']);
    exit;
}

$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$event = getEventById($pdo, $eventId);

if (!$event || !$event['visible'] || $event['status'] !== 'valide') {
    echo json_encode(['success' => false, 'error' => 'Evenement indisponible']);
    exit;
}

$registrationStmt = $pdo->prepare(
    "SELECT id
     FROM event_registrations
     WHERE event_id = :event_id
       AND user_id = :user_id
       AND status = 'accepte'
     LIMIT 1"
);
$registrationStmt->execute([
    ':event_id' => $eventId,
    ':user_id' => (int)$_SESSION['user_id'],
]);

if (!$registrationStmt->fetch() || !isEventJoinable($event, true)) {
    echo json_encode(['success' => false, 'error' => 'Le chat est disponible uniquement pendant l\'evenement pour les joueurs acceptes.']);
    exit;
}

if ($mongoDB !== null) {
    $bulk = new MongoDB\Driver\BulkWrite();
    $query = new MongoDB\Driver\Query(['event_id' => $eventId], ['sort' => ['created_at' => 1], 'limit' => 100]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($token)) {
            echo json_encode(['success' => false, 'error' => 'Token invalide']);
            exit;
        }

        $message = trim($_POST['message'] ?? '');
        if ($message === '' || strlen($message) > 2000) {
            echo json_encode(['success' => false, 'error' => 'Message invalide']);
            exit;
        }

        $bulk->insert([
            'event_id' => $eventId,
            'user_id' => (int)$_SESSION['user_id'],
            'pseudo' => $_SESSION['user_pseudo'],
            'message' => $message,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
        ]);
        $mongoDB->executeBulkWrite(MONGODB_DB . '.chat_messages', $bulk);

        echo json_encode(['success' => true, 'message' => 'Message envoye']);
        exit;
    }

    $cursor = $mongoDB->executeQuery(MONGODB_DB . '.chat_messages', $query);
    $output = [];
    foreach ($cursor as $message) {
        $date = $message->created_at instanceof MongoDB\BSON\UTCDateTime
            ? $message->created_at->toDateTime()->format('d/m/Y H:i')
            : date('d/m/Y H:i');
        $output[] = [
            'id' => (string)$message->_id,
            'pseudo' => $message->pseudo,
            'message' => $message->message,
            'created_at' => $date,
        ];
    }

    echo json_encode(['success' => true, 'messages' => $output]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        echo json_encode(['success' => false, 'error' => 'Token invalide']);
        exit;
    }

    $message = trim($_POST['message'] ?? '');
    if ($message === '' || strlen($message) > 2000) {
        echo json_encode(['success' => false, 'error' => 'Message invalide']);
        exit;
    }

    $insertStmt = $pdo->prepare(
        "INSERT INTO chat_messages (event_id, user_id, pseudo, message)
         VALUES (:event_id, :user_id, :pseudo, :message)"
    );
    $insertStmt->execute([
        ':event_id' => $eventId,
        ':user_id' => (int)$_SESSION['user_id'],
        ':pseudo' => $_SESSION['user_pseudo'],
        ':message' => $message,
    ]);

    echo json_encode(['success' => true, 'message' => 'Message envoye']);
    exit;
}

$messagesStmt = $pdo->prepare(
    "SELECT id, pseudo, message, created_at
     FROM chat_messages
     WHERE event_id = :event_id
     ORDER BY created_at ASC
     LIMIT 100"
);
$messagesStmt->execute([':event_id' => $eventId]);
$messages = $messagesStmt->fetchAll();

$output = [];
foreach ($messages as $message) {
    $output[] = [
        'id' => (int)$message['id'],
        'pseudo' => $message['pseudo'],
        'message' => $message['message'],
        'created_at' => date('d/m/Y H:i', strtotime($message['created_at'])),
    ];
}

echo json_encode(['success' => true, 'messages' => $output]);
