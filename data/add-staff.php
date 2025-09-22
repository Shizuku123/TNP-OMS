<?php
header('Content-Type: application/json');

$uploadDir = 'data/profilepictures/';
$dataFile = 'data/staff-records.json';
$accountsFile = 'data/accounts.json';

// Create the directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff = [];
    foreach ($_POST as $key => $value) {
        $staff[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
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
        $filename = 'staff-' . uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $staff['photoData'] = 'data/profilepictures/' . $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    } else {
        $staff['photoData'] = '/placeholder.svg?height=150&width=150';
    }

    // Assign a unique ID if not present
    if (!isset($staff['staffId'])) {
        $staff['staffId'] = uniqid('staff_', true);
    }

    // Read existing staff records
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

    // Read existing accounts
    $accounts = ['accounts' => []];
    if (file_exists($accountsFile)) {
        $json = file_get_contents($accountsFile);
        $accounts = json_decode($json, true);
        if (!is_array($accounts) || !isset($accounts['accounts'])) $accounts = ['accounts' => []];
    }

    // Check for duplicate username
    foreach ($accounts['accounts'] as $acc) {
        if ($acc['username'] === $staff['username']) {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
            exit;
        }
    }

    // Append new staff data
    $records[] = $staff;

    // Save updated staff records
    $fp = fopen($dataFile, 'w');
    if ($fp) {
        flock($fp, LOCK_EX);
        $result = fwrite($fp, json_encode($records, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to open staff data file.']);
        exit;
    }

    // Add to accounts
    $accounts['accounts'][] = [
        'username' => $staff['username'],
        'password' => $staff['password'],
        'role' => 'staff',
        'name' => $staff['firstName'] . ' ' . $staff['lastName'],
        'staffId' => $staff['staffId'],
        'department' => $staff['department'],
        'email' => $staff['emailAddress'],
        'dateCreated' => $staff['dateAdded'] ?? date('Y-m-d')
    ];

    // Save updated accounts
    file_put_contents($accountsFile, json_encode($accounts, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'downloadUrl' => '/data/staff-records.json']);
}
?>