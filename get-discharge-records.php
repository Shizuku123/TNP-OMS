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
    $recordsFile = __DIR__ . '/data/discharge-records.json';
    
    if (!file_exists($recordsFile)) {
        echo json_encode([
            'success' => true,
            'records' => []
        ]);
        exit;
    }

    // Use file locking for reading
    $fp = fopen($recordsFile, 'r');
    if ($fp) {
        flock($fp, LOCK_SH);
        $json = fread($fp, filesize($recordsFile));
        flock($fp, LOCK_UN);
        fclose($fp);
        
        $records = json_decode($json, true);
        
        if ($records === null) {
            throw new Exception('Invalid JSON data in discharge records file');
        }

        echo json_encode([
            'success' => true,
            'records' => $records
        ]);
    } else {
        throw new Exception('Failed to open discharge records file');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
