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

$data = $_POST;

if (!$data || !isset($data['childId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid form data']);
    exit;
}

// Validate required fields
$requiredFields = ['childId', 'checkupDate', 'doctorName', 'facilityName'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Path to the checkup records JSON file
    $filePath = 'api/data/children-checkups-records.json';
    
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
    $recordId = 'CHK' . time() . rand(100, 999);
    
    // Get child information from children records
    $childrenFilePath = 'data/children-records.json';
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
    
    // Handle file upload if present
    $uploadedFile = '';
    if (isset($_FILES['checkupFile']) && $_FILES['checkupFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/checkups/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['checkupFile']['name'], PATHINFO_EXTENSION);
        $fileName = $recordId . '_' . time() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['checkupFile']['tmp_name'], $uploadPath)) {
            $uploadedFile = $uploadPath;
        }
    }
    
    // Prepare checkup record data
    $checkupRecord = [
        'recordId' => $recordId,
        'childId' => $data['childId'],
        'childName' => $childName,
        'dateCreated' => date('Y-m-d H:i:s'),
        'lastUpdated' => date('Y-m-d H:i:s'),
        'createdBy' => $_SESSION['username'] ?? 'System',
        'checkupDate' => $data['checkupDate'],
        'doctorName' => $data['doctorName'],
        'facilityName' => $data['facilityName'],
        'purposeOfVisit' => $data['purposeOfVisit'] ?? '',
        'diagnosis' => $data['diagnosis'] ?? '',
        'recommendations' => $data['recommendations'] ?? '',
        'uploadedFile' => $uploadedFile,
        'fileName' => $_FILES['checkupFile']['name'] ?? ''
    ];
    
    // Add new record to existing records
    $existingRecords[] = $checkupRecord;
    
    // Save updated records to file
    $jsonData = json_encode($existingRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($filePath, $jsonData) === false) {
        throw new Exception('Failed to write to file');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Checkup record saved successfully',
        'recordId' => $recordId,
        'data' => $checkupRecord
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving checkup record: ' . $e->getMessage()
    ]);
}
?>
