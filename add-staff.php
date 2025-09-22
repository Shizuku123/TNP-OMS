<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$uploadDir = 'data/profilepictures/';
$dataFile = 'data/staff-records.json';
$accountsFile = 'data/accounts.json';

// Function to generate unique staff ID
function generateStaffId($staff) {
    $maxId = 0;
    foreach ($staff as $rec) {
        if (isset($rec['staffId']) && preg_match('/^ST(\d+)$/', $rec['staffId'], $m)) {
            $num = intval($m[1]);
            if ($num > $maxId) $maxId = $num;
        }
    }
    return 'ST' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
}

// Create the directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff = [];

    // Validate and sanitize input
    foreach ($_POST as $key => $value) {
        if ($key !== 'createAccount') {
            $staff[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
    }

    $createAccount = isset($_POST['createAccount']) && $_POST['createAccount'] === '1';

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
            $staff['photoData'] = $targetPath;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    } else {
        $staff['photoData'] = '/placeholder.svg?height=150&width=150';
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

    // Generate unique staff ID
    $staff['staffId'] = generateStaffId($records);
    $staff['id'] = uniqid('staff_', true);

    // Handle account creation if requested
    if ($createAccount) {
        if (empty($staff['username']) || empty($staff['password'])) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required for account creation.']);
            exit;
        }

        // Read existing accounts
        $accounts = ['accounts' => []];
        if (file_exists($accountsFile)) {
            $json = file_get_contents($accountsFile);
            $accounts = json_decode($json, true);
            if (!is_array($accounts) || !isset($accounts['accounts'])) {
                $accounts = ['accounts' => []];
            }
        }

        // Check for duplicate username
        foreach ($accounts['accounts'] as $acc) {
            if ($acc['username'] === $staff['username']) {
                echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                exit;
            }
        }

        // Add to accounts
        $accounts['accounts'][] = [
            'username' => $staff['username'],
            'password' => $staff['password'],
            'role' => 'staff',
            'name' => $staff['firstName'] . ' ' . $staff['lastName'],
            'staffId' => $staff['staffId'],
            'department' => $staff['department'] ?? '',
            'email' => $staff['emailAddress'] ?? '',
            'dateCreated' => $staff['dateAdded'] ?? date('Y-m-d')
        ];

        // Save updated accounts
        if (file_put_contents($accountsFile, json_encode($accounts, JSON_PRETTY_PRINT)) === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to save account data.']);
            exit;
        }
    } else {
        // Remove username and password from staff record if account creation is not requested
        unset($staff['username']);
        unset($staff['password']);
    }

    // Append new staff data
    $records[] = $staff;

    // Save updated records with file locking
    $fp = fopen($dataFile, 'w');
    if ($fp) {
        flock($fp, LOCK_EX);
        $result = fwrite($fp, json_encode($records, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        if ($result !== false) {
            echo json_encode(['success' => true, 'staffId' => $staff['staffId']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save record.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to open data file.']);
    }
}
?>
