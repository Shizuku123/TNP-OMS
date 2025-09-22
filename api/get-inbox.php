<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_GET['user'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User parameter is required']);
    exit;
}

$user = trim($_GET['user']);
$messagesFile = './data/messages.json';

// Load messages
$messages = [];
if (file_exists($messagesFile)) {
    $content = file_get_contents($messagesFile);
    $messages = json_decode($content, true) ?: [];
}

// Find unique senders who messaged this user
$senders = [];
foreach ($messages as $message) {
    if ($message['receiver'] === $user && !in_array($message['sender'], $senders)) {
        $senders[] = $message['sender'];
    }
}

echo json_encode($senders);
?>