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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $dischargeId = $input['dischargeId'];
    $action = $input['action']; // 'approve' or 'reject'
    $adminRemarks = $input['adminRemarks'];
    $reviewedBy = $input['reviewedBy'];
    $finalDischargeDate = isset($input['finalDischargeDate']) ? $input['finalDischargeDate'] : null;

    // Load existing records
    $recordsFile = '../data/discharge-records.json';
    if (!file_exists($recordsFile)) {
        throw new Exception('Discharge records file not found');
    }

    $records = json_decode(file_get_contents($recordsFile), true);
    if ($records === null) {
        throw new Exception('Invalid JSON data in discharge records file');
    }

    // Find and update the record
    $recordFound = false;
    for ($i = 0; $i < count($records); $i++) {
        if ($records[$i]['dischargeId'] === $dischargeId) {
            if ($action === 'approve') {
                $records[$i]['status'] = 'Approved';
                if ($finalDischargeDate) {
                    $records[$i]['finalDischargeDate'] = $finalDischargeDate;
                }
            } else if ($action === 'reject') {
                $records[$i]['status'] = 'Rejected';
            }
            
            $records[$i]['adminRemarks'] = $adminRemarks;
            $records[$i]['reviewedBy'] = $reviewedBy;
            $records[$i]['reviewedDate'] = date('Y-m-d H:i:s');
            
            $recordFound = true;
            break;
        }
    }

    if (!$recordFound) {
        throw new Exception('Discharge record not found');
    }

    // Save updated records
    if (!file_put_contents($recordsFile, json_encode($records, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save updated discharge records');
    }

    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' ? 'Discharge request approved successfully' : 'Discharge request rejected'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
