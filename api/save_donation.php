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
    $uploadDir = __DIR__ . '/../data/donationpictures/';
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
        $uniqueId = uniqid('child-');
        $fileName = $uniqueId . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        $photoPath = 'data\/donationpictures\/' . $fileName;
        
        // Get form data
        foreach ($_POST as $key => $value) {
            $donationData[$key] = $value;
        }
    } else {
        // Handle JSON data (for money donations or in-kind without photo)
        $input = file_get_contents('php://input');
        $donationData = json_decode($input, true);
        
        if (!$donationData) {
            throw new Exception('Invalid JSON data');
        }
        
        // Handle base64 photo data if present
        if (isset($donationData['photoData']) && !empty($donationData['photoData']) && strpos($donationData['photoData'], 'data:image') === 0) {
            // Extract base64 data
            $imageData = $donationData['photoData'];
            $imageType = '';
            
            if (strpos($imageData, 'data:image/jpeg') === 0) {
                $imageType = 'jpg';
            } elseif (strpos($imageData, 'data:image/png') === 0) {
                $imageType = 'png';
            } elseif (strpos($imageData, 'data:image/gif') === 0) {
                $imageType = 'gif';
            }
            
            if ($imageType) {
                // Remove data URL prefix
                $base64Data = preg_replace('/^data:image\/[^;]+;base64,/', '', $imageData);
                $binaryData = base64_decode($base64Data);
                
                if ($binaryData !== false) {
                    // Generate unique filename
                    $uniqueId = uniqid('child-');
                    $fileName = $uniqueId . '.' . $imageType;
                    $filePath = $uploadDir . $fileName;
                    
                    // Save file
                    if (file_put_contents($filePath, $binaryData) !== false) {
                        $photoPath = 'data\/donationpictures\/' . $fileName;
                    }
                }
            }
        }
    }
    
    // Validate required fields
    $requiredFields = ['donationType', 'dateOfDonation', 'emailAddress'];
    foreach ($requiredFields as $field) {
        if (!isset($donationData[$field]) || empty($donationData[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Load existing donations
    $donationsFile = __DIR__ . './data/donations.json';
    $donations = ['donations' => []];
    
    if (file_exists($donationsFile)) {
        $existingData = file_get_contents($donationsFile);
        $donations = json_decode($existingData, true);
        if (!$donations || !isset($donations['donations'])) {
            $donations = ['donations' => []];
        }
    }
    
    // Check for existing donation ID based on name and email
    $existingDonationId = null;
    $donorName = $donationData['donorName'] ?? '';
    $emailAddress = $donationData['emailAddress'];
    
    // Only check for existing ID if donor name is not empty (not anonymous)
    if (!empty($donorName)) {
        foreach ($donations['donations'] as $existingDonation) {
            if (isset($existingDonation['donorName']) && 
                isset($existingDonation['emailAddress']) &&
                $existingDonation['donorName'] === $donorName && 
                $existingDonation['emailAddress'] === $emailAddress) {
                $existingDonationId = $existingDonation['donationId'];
                break;
            }
        }
    }
    
    // Generate new donation ID if none exists
    if (!$existingDonationId) {
        $existingDonationId = generateUniqueDonationId($donations['donations']);
    }
    
    // Prepare the donation record
    $newDonation = [
        'donationId' => $existingDonationId,
        'donationType' => $donationData['donationType'],
        'dateOfDonation' => $donationData['dateOfDonation'],
        'donorName' => $donorName,
        'contactNumber' => $donationData['contactNumber'] ?? '',
        'emailAddress' => $emailAddress,
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
        $newDonation['itemDescription'] = $donationData['itemDescription'];
        $newDonation['quantity'] = $donationData['quantity'];
        $newDonation['conditionOfItems'] = $donationData['conditionOfItems'];
        $newDonation['receiverName'] = $donationData['receiverName'] ?? '';
        $newDonation['photoData'] = $photoPath; // Use file path instead of base64
    }
    
    // Add the new donation
    $donations['donations'][] = $newDonation;
    
    // Save to file
    $jsonData = json_encode($donations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($donationsFile, $jsonData) === false) {
        throw new Exception('Failed to write to donations file');
    }
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Donation saved successfully',
        'donationId' => $existingDonationId,
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
    do {
        $id = 'DN' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $exists = false;
        
        foreach ($existingDonations as $donation) {
            if ($donation['donationId'] === $id) {
                $exists = true;
                break;
            }
        }
    } while ($exists);
    
    return $id;
}
?>
