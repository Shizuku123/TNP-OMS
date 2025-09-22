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

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['childId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing childId']);
    exit;
}

$childId = $input['childId'];

try {
    // Load deleted records
    $deletedRecordsPath = '../data/children-deleted-records.json';
    $deletedRecordsData = json_decode(file_get_contents($deletedRecordsPath), true);
    
    // Find and remove the record
    $recordFound = false;
    foreach ($deletedRecordsData['deletedRecords'] as $index => $record) {
        if ($record['childId'] === $childId) {
            array_splice($deletedRecordsData['deletedRecords'], $index, 1);
            $recordFound = true;
            break;
        }
    }
    
    if (!$recordFound) {
        http_response_code(404);
        echo json_encode(['error' => 'Record not found']);
        exit;
    }
    
    // Also remove from main children records if it exists with deleted status
    $childrenRecordsPath = '../data/children-records.json';
    $childrenRecords = json_decode(file_get_contents($childrenRecordsPath), true);
    
    foreach ($childrenRecords as $index => $record) {
        if ($record['childId'] === $childId && isset($record['status']) && $record['status'] === 'deleted') {
            array_splice($childrenRecords, $index, 1);
            break;
        }
    }
    
    // Save both files
    file_put_contents($deletedRecordsPath, json_encode($deletedRecordsData, JSON_PRETTY_PRINT));
    file_put_contents($childrenRecordsPath, json_encode($childrenRecords, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true, 'message' => 'Record permanently deleted']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
