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
    
    // Find the deleted record
    $recordToRecover = null;
    $deletedRecordIndex = -1;
    
    foreach ($deletedRecordsData['deletedRecords'] as $index => $record) {
        if ($record['childId'] === $childId) {
            $recordToRecover = $record;
            $deletedRecordIndex = $index;
            break;
        }
    }
    
    if (!$recordToRecover) {
        http_response_code(404);
        echo json_encode(['error' => 'Deleted record not found']);
        exit;
    }
    
    // Check if record is still recoverable (not expired)
    $now = new DateTime();
    $expiryDate = new DateTime($recordToRecover['expiryDate']);
    
    if ($expiryDate <= $now) {
        http_response_code(400);
        echo json_encode(['error' => 'Record has expired and cannot be recovered']);
        exit;
    }
    
    // Load current children records
    $childrenRecordsPath = '../data/children-records.json';
    $childrenRecords = json_decode(file_get_contents($childrenRecordsPath), true);
    
    // Create restored record (remove deletion metadata)
    $restoredRecord = $recordToRecover;
    unset($restoredRecord['deletedBy']);
    unset($restoredRecord['deletedByName']);
    unset($restoredRecord['deletedDate']);
    unset($restoredRecord['expiryDate']);
    unset($restoredRecord['isDeleted']);
    unset($restoredRecord['status']); // Remove deleted status
    $restoredRecord['dateModified'] = date('Y-m-d H:i:s');
    
    // Update the record in children records (change status back from 'deleted')
    $recordFound = false;
    foreach ($childrenRecords as $index => $record) {
        if ($record['childId'] === $childId) {
            $childrenRecords[$index] = $restoredRecord;
            $recordFound = true;
            break;
        }
    }
    
    // If record wasn't found in active records, add it back
    if (!$recordFound) {
        $childrenRecords[] = $restoredRecord;
    }
    
    // Save updated children records
    file_put_contents($childrenRecordsPath, json_encode($childrenRecords, JSON_PRETTY_PRINT));
    
    // Remove from deleted records
    array_splice($deletedRecordsData['deletedRecords'], $deletedRecordIndex, 1);
    
    // Save updated deleted records
    file_put_contents($deletedRecordsPath, json_encode($deletedRecordsData, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true, 'message' => 'Record recovered successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
