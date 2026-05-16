<?php
/**
 * API asynchrone pour filtrer les événements
 * Retourne du JSON pour mise à jour sans rechargement.
 */

require_once __DIR__ . '/../includes/functions.php'; // Feature: Async Filters API

header('Content-Type: application/json; charset=utf-8');

$filters = [
    'sort'       => $_GET['sort'] ?? 'date_asc',
    'organizer'  => $_GET['organizer'] ?? '',
    'min_players'=> $_GET['min_players'] ?? '',
    'date_from'  => $_GET['date_from'] ?? ''
];

$events = getVisibleEvents($pdo, $filters);

$output = [];
foreach ($events as $event) {
    $output[] = [
        'id'               => (int)$event['id'],
        'title'            => e($event['title']),
        'max_players'      => (int)$event['max_players'],
        'start_date'       => date('d/m/Y H:i', strtotime($event['start_date'])),
        'end_date'         => date('d/m/Y H:i', strtotime($event['end_date'])),
        'organizer_pseudo' => e($event['organizer_pseudo']),
        'description'      => e($event['description'] ?? ''),
        'image_url'        => e($event['image_url'] ?? '')
    ];
}

echo json_encode(['success' => true, 'events' => $output]);
