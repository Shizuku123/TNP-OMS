<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_GET['user1']) || !isset($_GET['user2'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user1 or user2 parameter']);
    exit;
}

$user1 = trim($_GET['user1']);
$user2 = trim($_GET['user2']);

if (empty($user1) || empty($user2)) {
    http_response_code(400);
    echo json_encode(['error' => 'User parameters cannot be empty']);
    exit;
}

$messagesFile = 'data/messages.json';

// If messages file doesn't exist, return empty array
if (!file_exists($messagesFile)) {
    echo json_encode([]);
    exit;
}

$messagesContent = file_get_contents($messagesFile);
$messages = json_decode($messagesContent, true) ?: [];

// Filter messages between the two users
$chatMessages = array_filter($messages, function($message) use ($user1, $user2) {
    return ($message['sender'] === $user1 && $message['receiver'] === $user2) ||
           ($message['sender'] === $user2 && $message['receiver'] === $user1);
});

// Sort by timestamp
usort($chatMessages, function($a, $b) {
    return strtotime($a['timestamp']) - strtotime($b['timestamp']);
});

echo json_encode(array_values($chatMessages));
?>
