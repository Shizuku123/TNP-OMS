<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['childId']) || empty($input['medicineName'])) {
        throw new Exception('Child ID and Medicine Name are required');
    }
    
    // Load existing treatment records
    $treatmentFile = 'api/data/children-treatment-records.json';
    $treatmentRecords = [];
    
    if (file_exists($treatmentFile)) {
        $treatmentData = file_get_contents($treatmentFile);
        $treatmentRecords = json_decode($treatmentData, true) ?: [];
    }
    
    // Generate unique record ID
    $recordId = 'TR' . date('Ymd') . '_' . uniqid();
    
    // Create new treatment record
    $newRecord = [
        'recordId' => $recordId,
        'childId' => $input['childId'],
        'conditionTreated' => $input['conditionTreated'] ?? '',
        'medicineName' => $input['medicineName'],
        'dosage' => $input['dosage'] ?? '',
        'startDate' => $input['startDate'] ?? '',
        'endDate' => $input['endDate'] ?? '',
        'status' => $input['status'] ?? 'Ongoing',
        'prescribedBy' => $input['prescribedBy'] ?? '',
        'notes' => $input['notes'] ?? '',
        'createdBy' => $input['createdBy'] ?? 'System',
        'dateCreated' => date('Y-m-d H:i:s'),
        'lastModified' => date('Y-m-d H:i:s')
    ];
    
    // Add to records array
    $treatmentRecords[] = $newRecord;
    
    // Save to file
    if (!file_put_contents($treatmentFile, json_encode($treatmentRecords, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save treatment record to file');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Treatment record saved successfully',
        'recordId' => $recordId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
