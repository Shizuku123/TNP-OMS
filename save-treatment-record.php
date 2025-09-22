<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session
session_start();

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
$requiredFields = ['childId', 'medicineName'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Path to the treatment records JSON file (go up one level from api folder)
    $filePath = 'api/data/children-treatment-records.json';
    
    // Create data directory if it doesn't exist
    if (!file_exists('../data')) {
        mkdir('../data', 0755, true);
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
    $recordId = 'TRT' . time() . rand(100, 999);
    
    // Get child information from children records
    $childrenFilePath = '../data/children-records.json';
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
    
    // Prepare treatment record data
    $treatmentRecord = [
        'recordId' => $recordId,
        'childId' => $data['childId'],
        'childName' => $childName,
        'dateCreated' => date('Y-m-d H:i:s'),
        'lastUpdated' => date('Y-m-d H:i:s'),
        'createdBy' => $_SESSION['username'] ?? 'System',
        'conditionTreated' => $data['conditionTreated'] ?? '',
        'medicineName' => $data['medicineName'],
        'dosage' => $data['dosage'] ?? '',
        'startDate' => $data['startDate'] ?? '',
        'endDate' => $data['endDate'] ?? '',
        'status' => $data['status'] ?? 'Ongoing',
        'notes' => $data['notes'] ?? '',
        'prescribedBy' => $data['prescribedBy'] ?? ''
    ];
    
    // Add new record to existing records
    $existingRecords[] = $treatmentRecord;
    
    // Save updated records to file
    $jsonData = json_encode($existingRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($filePath, $jsonData) === false) {
        throw new Exception('Failed to write to file');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Treatment record saved successfully',
        'recordId' => $recordId,
        'data' => $treatmentRecord
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving treatment record: ' . $e->getMessage()
    ]);
}
?>
