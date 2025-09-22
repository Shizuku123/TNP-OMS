<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function saveAttendance($attendanceData) {
    $dataFile = '../data/attendance.json';
    
    // Read existing data
    if (file_exists($dataFile)) {
        $jsonData = file_get_contents($dataFile);
        $data = json_decode($jsonData, true);
    } else {
        $data = ['attendance' => []];
    }
    
    // Add new attendance record
    $data['attendance'][] = $attendanceData;
    
    // Save back to file
    $result = file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    
    return $result !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }
    
    // Validate required fields
    $requiredFields = ['accountName', 'accountId', 'accountType', 'date'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Generate attendance ID if not provided
    if (!isset($input['attendanceId'])) {
        $input['attendanceId'] = 'AT' . substr(strval(time()), -6);
    }
    
    // Add timestamp if not provided
    if (!isset($input['dateAdded'])) {
        $input['dateAdded'] = date('Y-m-d H:i:s');
    }
    
    if (saveAttendance($input)) {
        echo json_encode(['success' => true, 'message' => 'Attendance saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save attendance']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
