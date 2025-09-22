<?php
require_once 'includes/session.php';
require_once 'includes/XMLHandler.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['attendanceId']) || !isset($input['data'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$xmlHandler = new XMLHandler();

try {
    if ($xmlHandler->updateAttendanceRecord($input['attendanceId'], $input['data'])) {
        echo json_encode(['success' => true, 'message' => 'Attendance record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update attendance record']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
