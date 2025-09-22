<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null || !isset($data['deletedRecords'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

$filePath = '../data/children-deleted-records.json';

// Create directory if it doesn't exist
$dir = dirname($filePath);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Save the deleted records
if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Deleted records saved successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save deleted records']);
}
?>
