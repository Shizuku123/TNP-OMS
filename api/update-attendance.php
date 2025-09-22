<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function updateAttendance($attendanceId, $updateData) {
    $dataFile = '../data/attendance.json';
    
    if (!file_exists($dataFile)) {
        return false;
    }
    
    $jsonData = file_get_contents($dataFile);
    $data = json_decode($jsonData, true);
    
    if (!$data || !isset($data['attendance'])) {
        return false;
    }
    
    // Find and update the record
    $updated = false;
    for ($i = 0; $i < count($data['attendance']); $i++) {
        if ($data['attendance'][$i]['attendanceId'] === $attendanceId) {
            // Update the record with new data
            foreach ($updateData as $key => $value) {
                $data['attendance'][$i][$key] = $value;
            }
            $updated = true;
            break;
        }
    }
    
    if (!$updated) {
        return false;
    }
    
    // Save back to file
    $result = file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    
    return $result !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['attendanceId'])) {
        echo json_encode(['success' => false, 'message' => 'Missing attendance ID']);
        exit;
    }
    
    $attendanceId = $input['attendanceId'];
    unset($input['attendanceId']); // Remove ID from update data
    
    if (updateAttendance($attendanceId, $input)) {
        echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update attendance or record not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
