<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Response function
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Only POST method is allowed');
}

try {
    // Configuration
    $dataFile = '../data/children-records.json';
    $uploadDir = '../data/profilepictures/';
    $maxFileSize = 2 * 1024 * 1024; // 2MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    // Create directories if they don't exist
    if (!file_exists(dirname($dataFile))) {
        mkdir(dirname($dataFile), 0755, true);
    }
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validate required fields
    $requiredFields = ['childId', 'firstName', 'gender', 'dateOfBirth', 'entryDate'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            sendResponse(false, "Required field '$field' is missing");
        }
    }

    $childId = $_POST['childId'];
    
    // Validate Child ID format
    if (!preg_match('/^CH\d{3}$/', $childId)) {
        sendResponse(false, 'Invalid Child ID format');
    }

    // Load existing records
    $records = [];
    if (file_exists($dataFile)) {
        $jsonContent = file_get_contents($dataFile);
        $records = json_decode($jsonContent, true);
        if ($records === null) {
            sendResponse(false, 'Error reading existing records');
        }
    }

    // Find the record to update
    $recordIndex = -1;
    $existingRecord = null;
    for ($i = 0; $i < count($records); $i++) {
        if ($records[$i]['childId'] === $childId) {
            $recordIndex = $i;
            $existingRecord = $records[$i];
            break;
        }
    }

    if ($recordIndex === -1) {
        sendResponse(false, 'Child record not found');
    }

    // Prepare updated data
    $updatedData = [
        'childId' => $childId,
        'firstName' => trim($_POST['firstName']),
        'middleName' => trim($_POST['middleName'] ?? ''),
        'lastName' => trim($_POST['lastName'] ?? ''),
        'gender' => $_POST['gender'],
        'dateOfBirth' => $_POST['dateOfBirth'],
        'placeOfBirth' => trim($_POST['placeOfBirth'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? 'Filipino'),
        'religion' => trim($_POST['religion'] ?? ''),
        'entryDate' => $_POST['entryDate'],
        'entryAge' => $_POST['entryAge'] ?? '',
        'reasonForAdmission' => $_POST['reasonForAdmission'] ?? '',
        'currentStatus' => $_POST['currentStatus'] ?? '',
        'fatherName' => trim($_POST['fatherName'] ?? ''),
        'motherName' => trim($_POST['motherName'] ?? ''),
        'parentalStatus' => $_POST['parentalStatus'] ?? '',
        'siblingsInOrphanage' => $_POST['siblingsInOrphanage'] ?? 'No',
        'siblingsNamesList' => trim($_POST['siblingsNamesList'] ?? ''),
        'bloodType' => $_POST['bloodType'] ?? '',
        'allergies' => trim($_POST['allergies'] ?? ''),
        'medicalConditions' => trim($_POST['medicalConditions'] ?? ''),
        'lastCheckupDate' => $_POST['lastCheckupDate'] ?? '',
        'dateAdded' => $existingRecord['dateAdded'] ?? date('Y-m-d'),
        'dateModified' => date('Y-m-d H:i:s'),
        'photoData' => $existingRecord['photoData'] ?? '' // Keep existing photo by default
    ];

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        
        // Validate file size
        if ($file['size'] > $maxFileSize) {
            sendResponse(false, 'File size exceeds 2MB limit');
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            sendResponse(false, 'Invalid file type. Only JPG, PNG, and GIF are allowed');
        }
        
        // Validate file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            sendResponse(false, 'Invalid file extension');
        }
        
        // Generate unique filename
        $fileName = 'child-' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Delete old photo if it exists and is different
            if (!empty($existingRecord['photoData']) && 
                $existingRecord['photoData'] !== $updatedData['photoData'] &&
                strpos($existingRecord['photoData'], 'data/profilepictures/') === 0) {
                $oldPhotoPath = '../' . $existingRecord['photoData'];
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
            
            $updatedData['photoData'] = 'data/profilepictures/' . $fileName;
        } else {
            sendResponse(false, 'Failed to upload photo');
        }
    }

    // Additional validation
    if (!empty($updatedData['dateOfBirth'])) {
        $birthDate = new DateTime($updatedData['dateOfBirth']);
        $today = new DateTime();
        if ($birthDate > $today) {
            sendResponse(false, 'Date of birth cannot be in the future');
        }
    }

    if (!empty($updatedData['entryDate'])) {
        $entryDate = new DateTime($updatedData['entryDate']);
        $today = new DateTime();
        if ($entryDate > $today) {
            sendResponse(false, 'Entry date cannot be in the future');
        }
    }

    if (!empty($updatedData['lastCheckupDate'])) {
        $checkupDate = new DateTime($updatedData['lastCheckupDate']);
        $today = new DateTime();
        if ($checkupDate > $today) {
            sendResponse(false, 'Last checkup date cannot be in the future');
        }
    }

    // Update the record
    $records[$recordIndex] = $updatedData;

    // Create backup of current file
    if (file_exists($dataFile)) {
        $backupFile = $dataFile . '.backup.' . date('Y-m-d-H-i-s');
        copy($dataFile, $backupFile);
        
        // Keep only last 5 backups
        $backupFiles = glob($dataFile . '.backup.*');
        if (count($backupFiles) > 5) {
            usort($backupFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            for ($i = 0; $i < count($backupFiles) - 5; $i++) {
                unlink($backupFiles[$i]);
            }
        }
    }

    // Save updated records
    $jsonData = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonData === false) {
        sendResponse(false, 'Error encoding data to JSON');
    }

    if (file_put_contents($dataFile, $jsonData, LOCK_EX) === false) {
        sendResponse(false, 'Error saving data to file');
    }

    // Log the update (optional)
    $logFile = '../logs/child-updates.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $logEntry = date('Y-m-d H:i:s') . " - Child record updated: {$childId} by IP: " . 
                ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    sendResponse(true, 'Child record updated successfully', [
        'childId' => $childId,
        'photoData' => $updatedData['photoData']
    ]);

} catch (Exception $e) {
    error_log("Update child error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while updating the record: ' . $e->getMessage());
}
?>
