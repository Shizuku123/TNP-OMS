<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

try {
    // Load deleted records
    $deletedRecordsPath = '../data/children-deleted-records.json';
    $deletedRecordsData = json_decode(file_get_contents($deletedRecordsPath), true);
    
    $now = new DateTime();
    $beforeCount = count($deletedRecordsData['deletedRecords']);
    $validRecords = [];
    $expiredRecords = [];
    
    // Separate valid and expired records
    foreach ($deletedRecordsData['deletedRecords'] as $record) {
        $expiryDate = new DateTime($record['expiryDate']);
        if ($expiryDate > $now) {
            $validRecords[] = $record;
        } else {
            $expiredRecords[] = $record;
        }
    }
    
    // Update deleted records with only valid ones
    $deletedRecordsData['deletedRecords'] = $validRecords;
    file_put_contents($deletedRecordsPath, json_encode($deletedRecordsData, JSON_PRETTY_PRINT));
    
    // Also clean up main children records
    $childrenRecordsPath = '../data/children-records.json';
    $childrenRecords = json_decode(file_get_contents($childrenRecordsPath), true);
    
    // Remove expired records from main records
    foreach ($expiredRecords as $expiredRecord) {
        foreach ($childrenRecords as $index => $record) {
            if ($record['childId'] === $expiredRecord['childId'] && 
                isset($record['status']) && $record['status'] === 'deleted') {
                array_splice($childrenRecords, $index, 1);
                break;
            }
        }
    }
    
    file_put_contents($childrenRecordsPath, json_encode($childrenRecords, JSON_PRETTY_PRINT));
    
    $removedCount = $beforeCount - count($validRecords);
    
    echo json_encode([
        'success' => true, 
        'message' => "$removedCount expired record(s) permanently deleted",
        'removedCount' => $removedCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
