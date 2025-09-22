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

// Validate input
if (!isset($input['taskId']) || !isset($input['userEmail']) || !isset($input['userName'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$taskId = trim($input['taskId']);
$userEmail = trim($input['userEmail']);
$userName = trim($input['userName']);

// Validate that fields are not empty
if (empty($taskId) || empty($userEmail) || empty($userName)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
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
if (!isset($task['completedBy']) || !is_array($task['completedBy'])) {
    $task['completedBy'] = [];
}

// Check if user has accepted the task
if (!in_array($userEmail, $task['acceptedBy'])) {
    echo json_encode(['success' => false, 'message' => 'You must accept the task before completing it']);
    exit;
}

// Check if user already completed
if (in_array($userEmail, $task['completedBy'])) {
    echo json_encode(['success' => false, 'message' => 'You have already completed this task']);
    exit;
}

// Add user to completedBy array (individual completion)
$task['completedBy'][] = $userEmail;

// Check if the overall task should be marked as completed
$peopleNeeded = isset($task['peopleNeeded']) ? intval($task['peopleNeeded']) : 1;
$completedCount = count($task['completedBy']);

// Update overall task status based on completion threshold
if ($completedCount >= $peopleNeeded) {
    // All required people have completed the task
    $task['status'] = 'completed';
    $message = "Task completed successfully! All required people have finished this task.";
} else {
    // Individual completion, but task still needs more people
    $remaining = $peopleNeeded - $completedCount;
    $task['status'] = 'in-progress'; // Keep as in-progress until all required people complete
    $message = "Your part of the task is completed! $remaining more people needed to complete the entire task.";
}

// Save updated data
if (file_put_contents($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'individualCompleted' => true,
        'overallCompleted' => $task['status'] === 'completed',
        'completedCount' => $completedCount,
        'peopleNeeded' => $peopleNeeded
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save task data']);
}

// End output buffering
ob_end_flush();
?>
