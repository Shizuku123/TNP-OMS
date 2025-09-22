<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: homepage.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
        'department' => $_SESSION['user_department'] ?? '',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

function login($user) {
    $_SESSION['user_id'] = $user['username']; // Using username as ID for now
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_department'] = $user['department'] ?? '';
    $_SESSION['user_email'] = $user['email'] ?? '';
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
