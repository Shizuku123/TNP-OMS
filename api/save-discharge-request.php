<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Create necessary directories
    $dataDir = '../data/';
    $uploadDir = '../uploads/discharge-documents/';
    
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique discharge ID
    $dischargeId = 'DIS' . date('Ymd') . '_' . uniqid();

    // Handle file uploads
    $uploadedFiles = [];
    $requiredFiles = ['medicalClearance', 'educationalReport', 'socialWorkDocuments'];
    $optionalFiles = ['legalDocuments'];

    foreach ($requiredFiles as $fileField) {
        if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Required file $fileField is missing or has an error");
        }
        
        $file = $_FILES[$fileField];
        $fileName = $dischargeId . '_' . $fileField . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Failed to upload $fileField");
        }
        
        $uploadedFiles[$fileField] = $fileName;
    }

    // Handle optional files
    foreach ($optionalFiles as $fileField) {
        if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileField];
            $fileName = $dischargeId . '_' . $fileField . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $uploadedFiles[$fileField] = $fileName;
            }
        }
    }

    // Create discharge record
    $dischargeRecord = [
        'dischargeId' => $dischargeId,
        'childId' => $_POST['childId'],
        'childName' => $_POST['childName'],
        'dischargeDate' => $_POST['dischargeDate'],
        'dischargeType' => $_POST['dischargeType'],
        'dischargeReason' => $_POST['dischargeReason'],
        'remarks' => $_POST['remarks'] ?? '',
        'submittedBy' => $_POST['submittedBy'],
        'submittedDate' => date('Y-m-d H:i:s'),
        'status' => 'Waiting for approval',
        'documents' => $uploadedFiles
    ];

    // Load existing records or create new array
    $recordsFile = $dataDir . 'discharge-records.json';
    $records = [];
    
    if (file_exists($recordsFile)) {
        $existingData = file_get_contents($recordsFile);
        $records = json_decode($existingData, true) ?? [];
    }

    // Add new record
    $records[] = $dischargeRecord;

    // Save to file
    if (!file_put_contents($recordsFile, json_encode($records, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save discharge record');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Discharge request submitted successfully',
        'dischargeId' => $dischargeId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
