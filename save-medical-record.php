<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$requiredFields = ['childId'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Path to the medical records JSON file
    $filePath = 'data/Children-Medical-Records.json';
    
    // Create data directory if it doesn't exist
    if (!file_exists('data')) {
        mkdir('data', 0755, true);
    }
    
    // Read existing records
    $existingRecords = [];
    if (file_exists($filePath)) {
        $fileContent = file_get_contents($filePath);
        if ($fileContent !== false) {
            $existingRecords = json_decode($fileContent, true) ?: [];
        }
    }
    
    // Generate unique record ID
    $recordId = 'MR' . time() . rand(100, 999);
    
    // Get child information from children records
    $childrenFilePath = 'data/Children-Medical-Records.json';
    $childName = 'Unknown Child';
    
    if (file_exists($childrenFilePath)) {
        $childrenContent = file_get_contents($childrenFilePath);
        if ($childrenContent !== false) {
            $childrenRecords = json_decode($childrenContent, true) ?: [];
            foreach ($childrenRecords as $child) {
                if ($child['childId'] === $data['childId']) {
                    $childName = trim(($child['firstName'] ?? '') . ' ' . ($child['middleName'] ?? '') . ' ' . ($child['lastName'] ?? ''));
                    break;
                }
            }
        }
    }
    
    // Prepare medical record data
    $medicalRecord = [
        'recordId' => $recordId,
        'childId' => $data['childId'],
        'childName' => $childName,
        'dateCreated' => date('Y-m-d'),
        'lastUpdated' => date('Y-m-d'),
        'createdBy' => $_SESSION['username'] ?? 'System',
        'height' => $data['height'] ?? '',
        'weight' => $data['weight'] ?? '',
        'bloodType' => $data['bloodType'] ?? '',
        'immunizationStatus' => $data['immunizationStatus'] ?? '',
        'allergies' => $data['allergies'] ?? '',
        'physicalConditions' => $data['physicalConditions'] ?? '',
        'pastIllnesses' => $data['pastIllnesses'] ?? '',
        'pastSurgeries' => $data['pastSurgeries'] ?? '',
        'chronicConditions' => $data['chronicConditions'] ?? '',
        'mentalHealthNotes' => $data['mentalHealthNotes'] ?? '',
        'checkupDate' => $data['checkupDate'] ?? '',
        'doctorName' => $data['doctorName'] ?? '',
        'clinicHospital' => $data['clinicHospital'] ?? '',
        'observations' => $data['observations'] ?? '',
        'medicationsPrescribed' => $data['medicationsPrescribed'] ?? '',
        'conditionTreated' => $data['conditionTreated'] ?? '',
        'treatmentStartDate' => $data['treatmentStartDate'] ?? '',
        'nextAppointment' => $data['nextAppointment'] ?? '',
        'medicinesDosage' => $data['medicinesDosage'] ?? '',
        'treatmentSchedule' => $data['treatmentSchedule'] ?? '',
        'emergencyContactName' => $data['emergencyContactName'] ?? '',
        'emergencyContactRelationship' => $data['emergencyContactRelationship'] ?? '',
        'emergencyContactNumber' => $data['emergencyContactNumber'] ?? '',
        'additionalNotes' => $data['additionalNotes'] ?? ''
    ];
    
    // Add new record to existing records
    $existingRecords[] = $medicalRecord;
    
    // Save updated records to file
    $jsonData = json_encode($existingRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($filePath, $jsonData) === false) {
        throw new Exception('Failed to write to file');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Medical record saved successfully',
        'recordId' => $recordId,
        'data' => $medicalRecord
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving medical record: ' . $e->getMessage()
    ]);
}
?>
