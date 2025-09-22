<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Read accounts from JSON file
$accountsFile = 'data/accounts.json';

if (!file_exists($accountsFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Accounts file not found']);
    exit;
}

$accountsContent = file_get_contents($accountsFile);
$accountsData = json_decode($accountsContent, true);

if (!$accountsData || !isset($accountsData['accounts'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid accounts data']);
    exit;
}

// Return the accounts array
echo json_encode($accountsData['accounts']);
?>
