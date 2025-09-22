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
$required_fields = ['taskId', 'action', 'username'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

// Validate action
if (!in_array($input['action'], ['accept', 'complete'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action must be accept or complete']);
    exit;
}

$data_file = '../data/tasks.json';

if (!file_exists($data_file)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Tasks file not found']);
    exit;
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
    $file_content = file_get_contents($data_file);
    $tasks_data = json_decode($file_content, true);
    
    if (!$tasks_data || !isset($tasks_data['tasks'])) {
        throw new Exception('Invalid tasks data structure');
    }

    // Find the task
    $task_foun  {
        throw new Exception('Invalid tasks data structure');
    }

    // Find the task
    $task_found = false;
    $task_index = -1;
    
    for ($i = 0; $i < count($tasks_data['tasks']); $i++) {
        if ($tasks_data['tasks'][$i]['taskId'] === $input['taskId']) {
            $task_found = true;
            $task_index = $i;
            break;
        }
    }

    if (!$task_found) {
        throw new Exception('Task not found');
    }

    $task = &$tasks_data['tasks'][$task_index];
    $username = trim($input['username']);

    if ($input['action'] === 'accept') {
        // Add user to acceptedBy array if not already present
        if (!in_array($username, $task['acceptedBy'])) {
            $task['acceptedBy'][] = $username;
        }
    } elseif ($input['action'] === 'complete') {
        // Add user to completedBy array if not already present
        if (!in_array($username, $task['completedBy'])) {
            $task['completedBy'][] = $username;
        }
        
        // Also ensure user is in acceptedBy array
        if (!in_array($username, $task['acceptedBy'])) {
            $task['acceptedBy'][] = $username;
        }
    }

    // Save updated data
    $json_data = json_encode($tasks_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($data_file, $json_data) === false) {
        throw new Exception('Failed to write to file');
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => ucfirst($input['action']) . ' action completed successfully'
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
