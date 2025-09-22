<?php
require_once 'includes/session.php';
require_once 'includes/XMLHandler.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: staff-records.php');
    exit();
}

if (!isset($_POST['staffId'])) {
    $_SESSION['error'] = 'Missing staff ID';
    header('Location: staff-records.php');
    exit();
}

$xmlHandler = new XMLHandler();

// Handle photo upload if present
$photoData = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photoData = 'data:' . $_FILES['photo']['type'] . ';base64,' . base64_encode(file_get_contents($_FILES['photo']['tmp_name']));
    $_POST['photoData'] = $photoData;
}

if ($xmlHandler->updateStaff($_POST['staffId'], $_POST)) {
    $_SESSION['success'] = 'Staff record updated successfully';
} else {
    $_SESSION['error'] = 'Failed to update staff record';
}

header('Location: staff-records.php');
exit();
?>
