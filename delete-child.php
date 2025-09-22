<?php
require_once 'includes/session.php';
require_once 'includes/XMLHandler.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['childId']) || !isset($input['adminPassword'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

// Verify admin password (simple check - in production, use proper authentication)
$xmlHandler = new XMLHandler();
$adminUser = $xmlHandler->authenticateUser('admin', $input['adminPassword']);

if (!$adminUser || $adminUser['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Invalid admin password']);
    exit();
}

if ($xmlHandler->deleteChild($input['childId'])) {
    echo json_encode(['success' => true, 'message' => 'Child record deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete child record']);
}
?>
