<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $deletedRecordsPath = '../data/children-deleted-records.json';
    
    if (!file_exists($deletedRecordsPath)) {
        echo json_encode(['deletedRecords' => []]);
        exit;
    }
    
    $deletedRecordsData = json_decode(file_get_contents($deletedRecordsPath), true);
    
    echo json_encode($deletedRecordsData);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
