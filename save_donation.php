<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/data/donationpictures/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Handle both JSON and form data
    $donationData = [];
    $photoPath = '';
    
    // Check if this is a multipart form (with file upload)
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $uploadedFile = $_FILES['photo'];
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF files are allowed.');
        }
        
        // Generate unique filename
        $uniqueId = uniqid('donation-');
        $fileName = $uniqueId . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        $photoPath = 'data/donationpictures/' . $fileName;
        
        // Get form data
        foreach ($_POST as $key => $value) {
            $donationData[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
    } else {
        // Handle JSON data (for money donations or in-kind without photo)
        $input = file_get_contents('php://input');
        $donationData = json_decode($input, true);
        
        if (!$donationData) {
            throw new Exception('Invalid JSON data');
        }
    }
    
    // Validate required fields
    $requiredFields = ['donationType', 'dateOfDonation', 'emailAddress'];
    foreach ($requiredFields as $field) {
        if (!isset($donationData[$field]) || empty($donationData[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Load existing donations with file locking
    $donationsFile = __DIR__ . '/data/donations.json';
    $donations = ['donations' => []];
    
    if (file_exists($donationsFile)) {
        $fp = fopen($donationsFile, 'r');
        if ($fp) {
            flock($fp, LOCK_SH);
            $existingData = fread($fp, filesize($donationsFile));
            flock($fp, LOCK_UN);
            fclose($fp);
            $donations = json_decode($existingData, true);
            if (!$donations || !isset($donations['donations'])) {
                $donations = ['donations' => []];
            }
        }
    }
    
    // Generate unique donation ID
    $donationId = generateUniqueDonationId($donations['donations']);
    
    // Prepare the donation record
    $newDonation = [
        'donationId' => $donationId,
        'donationType' => $donationData['donationType'],
        'dateOfDonation' => $donationData['dateOfDonation'],
        'donorName' => $donationData['donorName'] ?? '',
        'contactNumber' => $donationData['contactNumber'] ?? '',
        'emailAddress' => $donationData['emailAddress'],
        'address' => $donationData['address'] ?? '',
        'dateAdded' => date('Y-m-d H:i:s')
    ];
    
    // Add type-specific fields
    if ($donationData['donationType'] === 'money') {
        $newDonation['typeOfDonation'] = $donationData['typeOfDonation'];
        $newDonation['amount'] = floatval($donationData['amount']);
        $newDonation['purpose'] = $donationData['purpose'] ?? '';
        $newDonation['tin'] = $donationData['tin'] ?? '';
    } else if ($donationData['donationType'] === 'in-kind') {
        $newDonation['itemCategory'] = $donationData['itemCategory'];
        $newDonation['itemDescription'] = $donationData['itemDescription'];
        $newDonation['quantity'] = $donationData['quantity'];
        $newDonation['unit'] = $donationData['unit'] ?? 'pieces';
        $newDonation['expiryDate'] = $donationData['expiryDate'] ?? null;
        $newDonation['conditionOfItems'] = $donationData['conditionOfItems'];
        $newDonation['receiverName'] = $donationData['receiverName'] ?? '';
        $newDonation['photoData'] = $photoPath;
    }
    
    // Add the new donation
    $donations['donations'][] = $newDonation;
    
    // Save to file with file locking
    $fp = fopen($donationsFile, 'w');
    if ($fp) {
        flock($fp, LOCK_EX);
        $jsonData = json_encode($donations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $result = fwrite($fp, $jsonData);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        
        if ($result === false) {
            throw new Exception('Failed to write to donations file');
        }
    } else {
        throw new Exception('Failed to open donations file for writing');
    }
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Donation saved successfully',
        'donationId' => $donationId,
        'photoPath' => $photoPath
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

function generateUniqueDonationId($existingDonations) {
    $maxId = 0;
    foreach ($existingDonations as $donation) {
        if (isset($donation['donationId']) && preg_match('/^DN(\d+)$/', $donation['donationId'], $matches)) {
            $num = intval($matches[1]);
            if ($num > $maxId) $maxId = $num;
        }
    }
    return 'DN' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
}
?>
