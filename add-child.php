
<?php
header('Content-Type: application/json');

$uploadDir = 'data/profilepictures/';
$dataFile = 'data/children-records.json';

// Assign a unique childId (CH001, CH002, ...)
function generateChildId($records) {
    $maxId = 0;
    foreach ($records as $rec) {
        if (isset($rec['childId']) && preg_match('/^CH(\d+)$/', $rec['childId'], $m)) {
            $num = intval($m[1]);
            if ($num > $maxId) $maxId = $num;
        }
    }
    return 'CH' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
}

// Read existing records with file locking
$records = [];
if (file_exists($dataFile)) {
    $fp = fopen($dataFile, 'r');
    if ($fp) {
        flock($fp, LOCK_SH);
        $json = fread($fp, filesize($dataFile));
        flock($fp, LOCK_UN);
        fclose($fp);
        $records = json_decode($json, true);
        if (!is_array($records)) $records = [];
    }
}

// Assign the unique childId
$child['childId'] = generateChildId($records);

// Optionally, keep your random id for internal use
$child['id'] = uniqid('child_', true);

// Create the directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child = [];

    // Validate and sanitize input
    foreach ($_POST as $key => $value) {
        $child[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif','image/webp'];
        // Use the type from $_FILES instead of mime_content_type
        $fileType = $_FILES['photo']['type'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
            exit;
        }
        if ($_FILES['photo']['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File too large.']);
            exit;
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'child-' . uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $child['photoData'] = 'data/profilepictures/' . $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    } else {
        $child['photoData'] = '/placeholder.svg?height=150&width=150';
    }

    // Assign a unique ID
    $child['id'] = uniqid('child_', true);

    // Read existing records with file locking
    $records = [];
    if (file_exists($dataFile)) {
        $fp = fopen($dataFile, 'r');
        if ($fp) {
            flock($fp, LOCK_SH);
            $json = fread($fp, filesize($dataFile));
            flock($fp, LOCK_UN);
            fclose($fp);
            $records = json_decode($json, true);
            if (!is_array($records)) $records = [];
        }
    }

    // Append new child data
    $records[] = $child;

    // Save updated records with file locking
    $fp = fopen($dataFile, 'w');
    if ($fp) {
        flock($fp, LOCK_EX);
        $result = fwrite($fp, json_encode($records, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        if ($result !== false) {
            echo json_encode(['success' => true, 'downloadUrl' => '/data/children-records.json']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save record.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to open data file.']);
    }
}
?>