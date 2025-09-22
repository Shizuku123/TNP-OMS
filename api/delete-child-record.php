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

if (!isset($input['childId']) || !isset($input['deletedBy'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$childId = $input['childId'];
$deletedBy = $input['deletedBy'];
$deletedByName = $input['deletedByName'] ?? $deletedBy;

try {
    // Load current children records
    $childrenRecordsPath = '../data/children-records.json';
    $childrenRecords = json_decode(file_get_contents($childrenRecordsPath), true);
    
    // Find the record to delete
    $recordToDelete = null;
    $recordIndex = -1;
    
    foreach ($childrenRecords as $index => $record) {
        if ($record['childId'] === $childId) {
            $recordToDelete = $record;
            $recordIndex = $index;
            break;
        }
    }
    
    if (!$recordToDelete) {
        http_response_code(404);
        echo json_encode(['error' => 'Record not found']);
        exit;
    }
    
    // Mark record as deleted in main records
    $childrenRecords[$recordIndex]['status'] = 'deleted';
    $childrenRecords[$recordIndex]['dateModified'] = date('Y-m-d H:i:s');
    
    // Save updated children records
    file_put_contents($childrenRecordsPath, json_encode($childrenRecords, JSON_PRETTY_PRINT));
    
    // Load deleted records
    $deletedRecordsPath = '../data/children-deleted-records.json';
    $deletedRecordsData = json_decode(file_get_contents($deletedRecordsPath), true);
    
    // Create deleted record entry
    $deletedRecord = [
        'childId' => $recordToDelete['childId'],
        'firstName' => $recordToDelete['firstName'],
        'middleName' => $recordToDelete['middleName'] ?? '',
        'lastName' => $recordToDelete['lastName'],
        'gender' => $recordToDelete['gender'] ?? '',
        'dateOfBirth' => $recordToDelete['dateOfBirth'] ?? '',
        'placeOfBirth' => $recordToDelete['placeOfBirth'] ?? '',
        'nationality' => $recordToDelete['nationality'] ?? '',
        'religion' => $recordToDelete['religion'] ?? '',
        'entryDate' => $recordToDelete['entryDate'] ?? '',
        'reasonForAdmission' => $recordToDelete['reasonForAdmission'] ?? '',
        'currentStatus' => $recordToDelete['currentStatus'] ?? '',
        'fatherName' => $recordToDelete['fatherName'] ?? '',
        'motherName' => $recordToDelete['motherName'] ?? '',
        'parentalStatus' => $recordToDelete['parentalStatus'] ?? '',
        'siblingsInOrphanage' => $recordToDelete['siblingsInOrphanage'] ?? '',
        'bloodType' => $recordToDelete['bloodType'] ?? '',
        'allergies' => $recordToDelete['allergies'] ?? '',
        'medicalConditions' => $recordToDelete['medicalConditions'] ?? '',
        'lastCheckupDate' => $recordToDelete['lastCheckupDate'] ?? '',
        'photoData' => $recordToDelete['photoData'] ?? '',
        'deletedBy' => $deletedBy,
        'deletedByName' => $deletedByName,
        'deletedDate' => date('Y-m-d H:i:s'),
        'expiryDate' => date('Y-m-d H:i:s', strtotime('+14 days')),
        'isDeleted' => true
    ];
    
    // Add to deleted records
    $deletedRecordsData['deletedRecords'][] = $deletedRecord;
    
    // Save deleted records
    file_put_contents($deletedRecordsPath, json_encode($deletedRecordsData, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
