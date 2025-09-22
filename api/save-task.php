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
$required_fields = [
    'title', 'description', 'assignedTo', 'dueDate', 'priority', 'author', 'authorRole', 'peopleNeeded'
];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || (is_string($input[$field]) && empty(trim($input[$field])))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

// Validate field values
if (!in_array($input['assignedTo'], ['all', 'staff', 'volunteers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'assignedTo must be all, staff, or volunteers']);
    exit;
}

if (!in_array($input['priority'], ['low', 'medium', 'high'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Priority must be low, medium, or high']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['dueDate'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Due date must be in YYYY-MM-DD format']);
    exit;
}

// Validate peopleNeeded
if (!is_numeric($input['peopleNeeded']) || intval($input['peopleNeeded']) < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'People Needed must be a positive integer']);
    exit;
}
$peopleNeeded = intval($input['peopleNeeded']);

$data_file = '../data/tasks.json';

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
    // Load existing tasks
    $tasks_data = ['tasks' => []];
    if (file_exists($data_file)) {
        $file_content = file_get_contents($data_file);
        if ($file_content) {
            $decoded = json_decode($file_content, true);
            if ($decoded && isset($decoded['tasks'])) {
                $tasks_data = $decoded;
            }
        }
    }

    // Generate unique ID
    $task_id = 'TSK' . str_pad(count($tasks_data['tasks']) + 1, 3, '0', STR_PAD_LEFT);

    // Ensure unique ID
    $existing_ids = array_column($tasks_data['tasks'], 'taskId');
    while (in_array($task_id, $existing_ids)) {
        $task_id = 'TSK' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    // Create new task (removed authority field)
    $new_task = [
        'taskId' => $task_id,
        'title' => trim($input['title']),
        'description' => trim($input['description']),
        'assignedTo' => $input['assignedTo'],
        'dueDate' => $input['dueDate'],
        'priority' => $input['priority'],
        'author' => trim($input['author']),
        'authorRole' => $input['authorRole'],
        'createdAt' => date('c'),
        'status' => 'open',
        'acceptedBy' => [],
        'completedBy' => [],
        'peopleNeeded' => $peopleNeeded
    ];

    // Add to tasks array
    $tasks_data['tasks'][] = $new_task;

    // Save to file
    $json_data = json_encode($tasks_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($data_file, $json_data) === false) {
        throw new Exception('Failed to write to file');
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Task created successfully',
        'taskId' => $task_id
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
