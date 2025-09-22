<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create data directory if it doesn't exist
    if (!file_exists('data')) {
        mkdir('data', 0755, true);
    }

    $accountsFile = 'data/accounts.json';
    
    // Get form data
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $middleName = trim($_POST['middleName']);
    $fullName = $middleName ? "$firstName $middleName $lastName" : "$firstName $lastName";
    
    $userData = [
        'username' => trim($_POST['username']),
        'password' => trim($_POST['password']),
        'name' => $fullName,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'middleName' => $middleName,
        'email' => trim($_POST['email']),
        'phoneNumber' => trim($_POST['phoneNumber']),
        'birthday' => trim($_POST['birthday']),
        'gender' => trim($_POST['gender']),
        'nationality' => trim($_POST['nationality']),
        'address' => trim($_POST['address']),
        'role' => trim($_POST['role']),
        'department' => trim($_POST['department']),
        'dateCreated' => date('Y-m-d')
    ];

    // Validate required fields
    if (empty($userData['firstName']) || empty($userData['lastName']) || 
        empty($userData['username']) || empty($userData['password']) || 
        empty($userData['email']) || empty($userData['phoneNumber']) || 
        empty($userData['birthday']) || empty($userData['gender']) || 
        empty($userData['nationality']) || empty($userData['address']) || 
        empty($userData['role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Load existing accounts
    $accounts = ['accounts' => []];
    if (file_exists($accountsFile)) {
        $content = file_get_contents($accountsFile);
        $accounts = json_decode($content, true) ?: ['accounts' => []];
    }

    // Check for duplicate username
    foreach ($accounts['accounts'] as $account) {
        if ($account['username'] === $userData['username']) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
            exit;
        }
    }

    // Check for duplicate email
    foreach ($accounts['accounts'] as $account) {
        if ($account['email'] === $userData['email']) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already exists']);
            exit;
        }
    }

    // Add new user
    $accounts['accounts'][] = $userData;

    // Save to file
    if (file_put_contents($accountsFile, json_encode($accounts, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => 'Account created successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save account']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
