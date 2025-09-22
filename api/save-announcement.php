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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required_fields = ['title', 'content', 'priority', 'author', 'authorRole', 'createdAt'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

// Validate field lengths and values
if (strlen($input['title']) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title must be less than 100 characters']);
    exit;
}

if (strlen($input['content']) > 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Content must be less than 1000 characters']);
    exit;
}

if (!in_array($input['priority'], ['normal', 'important', 'urgent'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Priority must be normal, important, or urgent']);
    exit;
}

$data_file = '../data/announcements.json';

// Create data directory if it doesn't exist
$data_dir = dirname($data_file);
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}

// File locking to prevent data corruption
$lock_file = $data_file . '.lock';
$lock_handle = fopen($lock_file, 'w');

if (!flock($lock_handle, LOCK_EX)) {
    fclose($lock_handle);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not acquire file lock']);
    exit;
}

try {
    // Load existing announcements
    $announcements_data = ['announcements' => []];
    if (file_exists($data_file)) {
        $file_content = file_get_contents($data_file);
        if ($file_content) {
            $decoded = json_decode($file_content, true);
            if ($decoded && isset($decoded['announcements'])) {
                $announcements_data = $decoded;
            }
        }
    }

    // Generate unique ID
    $announcement_id = 'ANN' . str_pad(count($announcements_data['announcements']) + 1, 3, '0', STR_PAD_LEFT);
    
    // Ensure unique ID
    $existing_ids = array_column($announcements_data['announcements'], 'announcementId');
    while (in_array($announcement_id, $existing_ids)) {
        $announcement_id = 'ANN' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    // Create new announcement
    $new_announcement = [
        'announcementId' => $announcement_id,
        'title' => trim($input['title']),
        'content' => trim($input['content']),
        'priority' => $input['priority'],
        'author' => trim($input['author']),
        'authorRole' => $input['authorRole'],
        'createdAt' => $input['createdAt']
    ];

    // Add to announcements array
    $announcements_data['announcements'][] = $new_announcement;

    // Save to file
    $json_data = json_encode($announcements_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($data_file, $json_data) === false) {
        throw new Exception('Failed to write to file');
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Announcement created successfully',
        'announcementId' => $announcement_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} finally {
    // Release file lock
    flock($lock_handle, LOCK_UN);
    fclose($lock_handle);
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}
?>
