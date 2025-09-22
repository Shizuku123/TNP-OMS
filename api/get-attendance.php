<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function getAttendanceRecords($filters = []) {
    $dataFile = '../data/attendance.json';
    
    if (!file_exists($dataFile)) {
        return ['attendance' => []];
    }
    
    $jsonData = file_get_contents($dataFile);
    $data = json_decode($jsonData, true);
    
    if (!$data || !isset($data['attendance'])) {
        return ['attendance' => []];
    }
    
    $records = $data['attendance'];
    
    // Apply filters
    if (!empty($filters['accountId'])) {
        $records = array_filter($records, function($record) use ($filters) {
            return $record['accountId'] === $filters['accountId'];
        });
    }
    
    if (!empty($filters['date'])) {
        $records = array_filter($records, function($record) use ($filters) {
            return $record['date'] === $filters['date'];
        });
    }
    
    if (!empty($filters['status'])) {
        $records = array_filter($records, function($record) use ($filters) {
            return $record['status'] === $filters['status'];
        });
    }
    
    // Sort by date and time (newest first)
    usort($records, function($a, $b) {
        $dateA = $a['date'] . ' ' . ($a['timeIn'] ?? '00:00');
        $dateB = $b['date'] . ' ' . ($b['timeIn'] ?? '00:00');
        return strtotime($dateB) - strtotime($dateA);
    });
    
    return ['attendance' => array_values($records)];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filters = [];
    
    if (isset($_GET['accountId'])) {
        $filters['accountId'] = $_GET['accountId'];
    }
    
    if (isset($_GET['date'])) {
        $filters['date'] = $_GET['date'];
    }
    
    if (isset($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    
    $result = getAttendanceRecords($filters);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
