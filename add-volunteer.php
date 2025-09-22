<?php
header('Content-Type: application/json');

$uploadDir = 'data/volunteerpictures/';
$dataFile = 'data/volunteer-records.json';

// Function to generate unique volunteer ID
function generateVolunteerId($volunteers) {
    $maxId = 0;
    foreach ($volunteers as $rec) {
        if (isset($rec['volunteerId']) && preg_match('/^VL(\d+)$/', $rec['volunteerId'], $m)) {
            $num = intval($m[1]);
            if ($num > $maxId) $maxId = $num;
        }
    }
    return 'VL' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
}

// Create the directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $volunteer = [];

    // Validate and sanitize input
    foreach ($_POST as $key => $value) {
        $volunteer[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
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
        $filename = 'volunteer-' . uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $volunteer['photoData'] = $targetPath;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    } else {
        $volunteer['photoData'] = '/placeholder.svg?height=150&width=150';
    }

    // Read existing records with file locking
    $volunteers = [];
    if (file_exists($dataFile)) {
        $fp = fopen($dataFile, 'r');
        if ($fp) {
            flock($fp, LOCK_SH);
            $json = fread($fp, filesize($dataFile));
            flock($fp, LOCK_UN);
            fclose($fp);
            $data = json_decode($json, true);
            if (isset($data['volunteers']) && is_array($data['volunteers'])) {
                $volunteers = $data['volunteers'];
            }
        }
    }

    // Generate unique volunteer ID
    $volunteer['volunteerId'] = generateVolunteerId($volunteers);
    $volunteer['id'] = uniqid('volunteer_', true);

    // Append new volunteer data
    $volunteers[] = $volunteer;

    // Prepare final data structure
    $finalData = ['volunteers' => $volunteers];

    // Save updated records with file locking
    $fp = fopen($dataFile, 'w');
    if ($fp) {
        flock($fp, LOCK_EX);
        $result = fwrite($fp, json_encode($finalData, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        if ($result !== false) {
            echo json_encode(['success' => true, 'volunteerId' => $volunteer['volunteerId']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save record.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to open data file.']);
    }
}
?>
