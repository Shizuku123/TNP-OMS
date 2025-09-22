<?php
// Prevent any output before headers
ob_start();

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Clear any previous output
ob_clean();

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Debug: Log the received input
error_log('Received input: ' . json_encode($input));

// Validate input - check for required fields
if (!$input || !is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

if (!isset($input['taskId']) || !isset($input['userEmail']) || !isset($input['userName']) || !isset($input['userRole'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields. Required: taskId, userEmail, userName, userRole',
        'received' => array_keys($input)
    ]);
    exit;
}

$taskId = trim($input['taskId']);
$userEmail = trim($input['userEmail']);
$userName = trim($input['userName']);
$userRole = trim($input['userRole']);

// Validate that fields are not empty
if (empty($taskId) || empty($userEmail) || empty($userName) || empty($userRole)) {
    echo json_encode([
        'success' => false, 
        'message' => 'All fields must have values',
        'values' => [
            'taskId' => $taskId,
            'userEmail' => $userEmail,
            'userName' => $userName,
            'userRole' => $userRole
        ]
    ]);
    exit;
}

// Load tasks data
$tasksFile = '../data/tasks.json';
if (!file_exists($tasksFile)) {
    echo json_encode(['success' => false, 'message' => 'Tasks file not found']);
    exit;
}

$tasksData = json_decode(file_get_contents($tasksFile), true);
if (!$tasksData || !isset($tasksData['tasks'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid tasks data']);
    exit;
}

// Find the task
$taskIndex = -1;
foreach ($tasksData['tasks'] as $index => $task) {
    if ($task['taskId'] === $taskId) {
        $taskIndex = $index;
        break;
    }
}

if ($taskIndex === -1) {
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

$task = &$tasksData['tasks'][$taskIndex];

// Initialize arrays if they don't exist
if (!isset($task['acceptedBy']) || !is_array($task['acceptedBy'])) {
    $task['acceptedBy'] = [];
}

// Validate role assignment
if (!in_array($task['assignedTo'], ['all', 'staff', 'volunteers'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid task assignment']);
    exit;
}

// Check if user is allowed to accept this task based on role
$isAllowed = false;
if ($task['assignedTo'] === 'all') {
    $isAllowed = true;
} elseif ($task['assignedTo'] === 'staff' && $userRole === 'staff') {
    $isAllowed = true;
} elseif ($task['assignedTo'] === 'volunteers' && $userRole === 'volunteer') {
    $isAllowed = true;
}

if (!$isAllowed) {
    echo json_encode(['success' => false, 'message' => 'You are not allowed to accept this task']);
    exit;
}

// Check if task is already full
$acceptedCount = count($task['acceptedBy']);
$peopleNeeded = isset($task['peopleNeeded']) ? intval($task['peopleNeeded']) : 1;

if ($acceptedCount >= $peopleNeeded) {
    echo json_encode(['success' => false, 'message' => 'Task is already full']);
    exit;
}

// Check if user has already accepted this task (using EMAIL as unique identifier)
if (in_array($userEmail, $task['acceptedBy'])) {
    echo json_encode(['success' => false, 'message' => 'You have already accepted this task']);
    exit;
}

// Accept the task - add user EMAIL to acceptedBy array (NOT name)
$task['acceptedBy'][] = $userEmail;

// Update task status if needed
if (count($task['acceptedBy']) > 0 && $task['status'] === 'open') {
    $task['status'] = 'in-progress';
}

// Save updated data
if (file_put_contents($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true, 
        'message' => 'Task accepted successfully',
        'acceptedCount' => count($task['acceptedBy']),
        'peopleNeeded' => $peopleNeeded,
        'savedEmail' => $userEmail
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save task data']);
}

// End output buffering
ob_end_flush();
?>
