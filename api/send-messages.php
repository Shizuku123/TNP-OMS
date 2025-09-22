<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['sender']) || !isset($input['receiver']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: sender, receiver, message']);
    exit;
}

$sender = trim($input['sender']);
$receiver = trim($input['receiver']);
$message = trim($input['message']);

if (empty($sender) || empty($receiver) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields must be non-empty']);
    exit;
}

// Create data directory if it doesn't exist
if (!file_exists('data')) {
    mkdir('data', 0755, true);
}

$messagesFile = 'data/messages.json';

// Load existing messages
$messages = [];
if (file_exists($messagesFile)) {
    $content = file_get_contents($messagesFile);
    $messages = json_decode($content, true) ?: [];
}

// Create new message
$newMessage = [
    'sender' => $sender,
    'receiver' => $receiver,
    'message' => $message,
    'timestamp' => date('Y-m-d\TH:i:s')
];

// Add to messages array
$messages[] = $newMessage;

// Save back to file
if (file_put_contents($messagesFile, json_encode($messages, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save message']);
}
?>
