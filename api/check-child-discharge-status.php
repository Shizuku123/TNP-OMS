<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $childId = $_GET['childId'] ?? '';
    
    if (empty($childId)) {
        throw new Exception('Child ID is required');
    }

    $recordsFile = '../data/discharge-records.json';
    
    if (!file_exists($recordsFile)) {
        echo json_encode([
            'success' => true,
            'hasPendingDischarge' => false,
            'dischargeStatus' => null
        ]);
        exit;
    }

    $records = json_decode(file_get_contents($recordsFile), true);
    
    if ($records === null) {
        throw new Exception('Invalid JSON data in discharge records file');
    }

    // Check for pending discharge for this child
    $pendingDischarge = null;
    foreach ($records as $record) {
        if ($record['childId'] === $childId && 
            in_array($record['status'], ['Waiting for approval', 'Approved'])) {
            $pendingDischarge = $record;
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'hasPendingDischarge' => $pendingDischarge !== null,
        'dischargeStatus' => $pendingDischarge ? $pendingDischarge['status'] : null,
        'dischargeDetails' => $pendingDischarge
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
