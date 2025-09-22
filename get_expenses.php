<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$expenses_file = 'expenses.json';
$expenses = [];

if (file_exists($expenses_file)) {
    $json_data = file_get_contents($expenses_file);
    $expenses = json_decode($json_data, true) ?: [];
}

// Sort by date (newest first)
usort($expenses, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

echo json_encode(['success' => true, 'data' => $expenses]);
?>
