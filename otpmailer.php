<?php
/**
 * TNP-OMS OTP Email Service
 * Sends OTP to user's email and returns OTP for client-side Firestore update.
 */

header('Content-Type: application/json; charset=UTF-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

// Generate a random 6-digit OTP
function generateOTP($length = 6)
{
    $characters = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $otp;
}

// PHPMailer path
$phpmailerPath = __DIR__ . '/PHPMailer/PHPMailer/src/';
require_once $phpmailerPath . 'Exception.php';
require_once $phpmailerPath . 'PHPMailer.php';
require_once $phpmailerPath . 'SMTP.php';

// Get POST data
$userId = $_POST['userId'] ?? '';
$userEmail = $_POST['userEmail'] ?? '';
$userName = $_POST['userName'] ?? '';

if (!$userId || !$userEmail || !$userName) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$otp = generateOTP();

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'coffeecornerofficial1@gmail.com';
    $mail->Password   = 'sfxr wvap lbwj bszs';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('tahananngpagmamahalcapstone@gmail.com', 'Tahanan ng Pagmamahal');
    $mail->addAddress($userEmail, $userName);

    $mail->isHTML(true);
    $mail->Subject = "Your OTP Code - Tahanan ng Pagmamahal";
    $mail->Body = "
        <h2>Your OTP Code</h2>
        <p>Dear $userName,</p>
        <p>Your OTP for login is: <strong>$otp</strong></p>
        <p>This code will expire in 5 minutes.</p>
    ";
    $mail->AltBody = "Your OTP for login is: $otp";

    $mail->send();

    // ✅ Return OTP to frontend for Firestore save
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully.',
        'otp' => $otp
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
?>
