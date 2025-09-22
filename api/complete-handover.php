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
    // Create uploads directory if it doesn't exist
    $uploadDir = '../uploads/handover-documents/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $dischargeId = $_POST['dischargeId'];

    // Handle file uploads
    $uploadedFiles = [];
    $requiredFiles = ['validId', 'acknowledgmentForm'];

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

    // Load existing discharge records
    $recordsFile = '../data/discharge-records.json';
    if (!file_exists($recordsFile)) {
        throw new Exception('Discharge records file not found');
    }

    $records = json_decode(file_get_contents($recordsFile), true);
    if ($records === null) {
        throw new Exception('Invalid JSON data in discharge records file');
    }

    // Find and update the discharge record
    $recordFound = false;
    $childId = null;
    for ($i = 0; $i < count($records); $i++) {
        if ($records[$i]['dischargeId'] === $dischargeId) {
            $records[$i]['status'] = 'Completed';
            $records[$i]['handoverDetails'] = [
                'recipientName' => $_POST['recipientName'],
                'relationship' => $_POST['relationship'],
                'recipientAddress' => $_POST['recipientAddress'],
                'recipientContact' => $_POST['recipientContact'],
                'handoverCompletedBy' => $_POST['handoverCompletedBy'],
                'completedDate' => date('Y-m-d H:i:s'),
                'documents' => $uploadedFiles
            ];
            
            $childId = $records[$i]['childId'];
            $recordFound = true;
            break;
        }
    }

    if (!$recordFound) {
        throw new Exception('Discharge record not found');
    }

    // Save updated discharge records
    if (!file_put_contents($recordsFile, json_encode($records, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save updated discharge records');
    }

    // Remove child from children records
    $childrenFile = '../data/children-records.json';
    if (file_exists($childrenFile)) {
        $children = json_decode(file_get_contents($childrenFile), true);
        if ($children !== null) {
            // Filter out the discharged child
            $children = array_filter($children, function($child) use ($childId) {
                return $child['childId'] !== $childId;
            });
            
            // Re-index array to maintain proper JSON structure
            $children = array_values($children);
            
            // Save updated children records
            if (!file_put_contents($childrenFile, json_encode($children, JSON_PRETTY_PRINT))) {
                error_log('Warning: Failed to update children records after discharge completion');
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Handover completed successfully. Child has been removed from orphanage records.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
