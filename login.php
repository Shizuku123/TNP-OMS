<?php
session_start();

require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Read accounts from JSON file
    $accountsFile = 'data/accounts.json';
    if (!file_exists($accountsFile)) {
        $error = "Accounts file not found";
    } else {
        $accountsData = json_decode(file_get_contents($accountsFile), true);
        $accounts = $accountsData['accounts'] ?? [];
        
        // Find matching user
        $user = null;
        foreach ($accounts as $account) {
            if ($account['username'] === $username && $account['password'] === $password) {
                $user = $account;
                break;
            }
        }
        
        if ($user) {
            // Store user data in session
            $_SESSION['user'] = $user;
            header('Location: index.html');
            exit;
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tahanan ng Pagmamahal OMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'red-primary': '#dc2626',
                        'red-secondary': '#ef4444',
                        'red-light': '#fef2f2'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-20 w-auto flex justify-center">
                <img src="/placeholder.svg?height=80&width=200" alt="Tahanan ng Pagmamahal Logo" class="h-20">
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Sign in to your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Tahanan ng Pagmamahal OMS
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input id="username" name="username" type="text" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-red-primary focus:border-red-primary focus:z-10 sm:text-sm" 
                           placeholder="Username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-red-primary focus:border-red-primary focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-red-primary hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-primary">
                    Sign in
                </button>
            </div>
        </form>
        
        <div class="mt-6">
            <div class="text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Test Accounts</h3>
                <div class="space-y-2 text-sm">
                    <div class="bg-blue-50 p-3 rounded">
                        <strong>Admin:</strong> admin / admin123
                    </div>
                    <div class="bg-green-50 p-3 rounded">
                        <strong>Staff:</strong> staff1 / staff123
                    </div>
                    <div class="bg-purple-50 p-3 rounded">
                        <strong>Volunteer:</strong> volunteer1 / vol123
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
