<?php
/**
 * TNP-OMS Email Service
 * 
 * This file handles email sending for Tahanan ng Pagmamahal Organization Management System.
 * Features:
 * - Send thank you emails to donors after donation recording
 * - Legacy OTP email functionality for user authentication
 * 
 * Usage for donation emails:
 * POST to this file with action=send_donation_thanks and required donor information
 * 
 * Requirements:
 * - PHPMailer library installed in C:\xampp\htdocs\PHPMailer\
 * - Gmail SMTP credentials configured
 */

// Set up error handling for JSON responses
function handleError($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Set error handler for any uncaught errors
set_error_handler(function($severity, $message, $file, $line) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_donation_thanks') {
        handleError("PHP Error: $message in $file on line $line");
    }
});

session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check multiple possible PHPMailer installation locations
$possiblePaths = [
    __DIR__ . '/PHPMailer/PHPMailer/src/',  // PHPMailer in project folder (correct path)
    'C:\xampp\htdocs\PHPMailer\PHPMailer\src\\',  // Original path
    'C:\xampp\htdocs\vendor\phpmailer\phpmailer\src\\',  // Composer in htdocs
    'C:\xampp\vendor\phpmailer\phpmailer\src\\',  // Composer in xampp
    __DIR__ . '\vendor\phpmailer\phpmailer\src\\',  // Local vendor folder
    __DIR__ . '\PHPMailer\src\\',  // Local PHPMailer folder
];

$phpmailerPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path . 'PHPMailer.php')) {
        $phpmailerPath = $path;
        break;
    }
}

// If PHPMailer not found, provide helpful error message
if (!$phpmailerPath) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_donation_thanks') {
        header('Content-Type: application/json');
        
        // Try to use built-in PHP mail as fallback
        try {
            $donorName = $_POST['donorName'] ?? '';
            $donorEmail = $_POST['donorEmail'] ?? '';
            $donationType = $_POST['donationType'] ?? '';
            $donationAmount = $_POST['donationAmount'] ?? '';
            $donationItems = $_POST['donationItems'] ?? '';
            $donationId = $_POST['donationId'] ?? '';
            $donationDate = $_POST['donationDate'] ?? '';
            
            if (empty($donorEmail) || $donorEmail === 'Anonymous' || empty($donorName) || $donorName === 'Anonymous') {
                echo json_encode(['success' => false, 'message' => 'Anonymous donation - no email sent']);
                exit;
            }
            
            $result = sendSimpleThankYouEmail($donorName, $donorEmail, $donationType, $donationAmount, $donationItems, $donationId, $donationDate);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Thank you email sent successfully (using PHP mail)']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send email. PHPMailer not available and PHP mail failed.']);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'PHPMailer not installed. Please download from https://github.com/PHPMailer/PHPMailer or install via Composer: composer require phpmailer/phpmailer',
                'searched_paths' => $possiblePaths,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

$requiredFiles = [
    $phpmailerPath . 'Exception.php',
    $phpmailerPath . 'PHPMailer.php',
    $phpmailerPath . 'SMTP.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_donation_thanks') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PHPMailer file not found: ' . $file]);
            exit;
        }
    }
}

require $phpmailerPath . 'Exception.php';
require $phpmailerPath . 'PHPMailer.php';
require $phpmailerPath . 'SMTP.php';

// Test endpoint to check if PHP is working correctly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'PHP mail.php is working correctly', 'phpversion' => phpversion()]);
    exit;
}

// Check if this is an AJAX request for sending donation thank you email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_donation_thanks') {
    header('Content-Type: application/json');
    
    try {
        $donorName = $_POST['donorName'] ?? '';
        $donorEmail = $_POST['donorEmail'] ?? '';
        $donationType = $_POST['donationType'] ?? '';
        $donationAmount = $_POST['donationAmount'] ?? '';
        $donationItems = $_POST['donationItems'] ?? '';
        $donationId = $_POST['donationId'] ?? '';
        $donationDate = $_POST['donationDate'] ?? '';
        
        if (empty($donorEmail) || $donorEmail === 'Anonymous' || empty($donorName) || $donorName === 'Anonymous') {
            echo json_encode(['success' => false, 'message' => 'Anonymous donation - no email sent']);
            exit;
        }
        
        $result = sendDonationThankYouEmail($donorName, $donorEmail, $donationType, $donationAmount, $donationItems, $donationId, $donationDate);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Thank you email sent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send thank you email']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    } catch (Error $e) {
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $e->getMessage()]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Unexpected Error: ' . $e->getMessage()]);
    }
    exit;
}

function sendDonationThankYouEmail($donorName, $donorEmail, $donationType, $donationAmount = '', $donationItems = '', $donationId = '', $donationDate = '') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tahananngpagmamahalcapstone@gmail.com';  // Keep existing credentials
        $mail->Password   = 'sfxr wvap lbwj bszs';               // Keep existing credentials
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('tahananngpagmamahalcapstone@gmail.com', 'Tahanan ng Pagmamahal');
        $mail->addAddress($donorEmail, $donorName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Thank You for Your Generous Donation - Tahanan ng Pagmamahal";
        
        // Create donation details based on type
        $donationDetails = '';
        if ($donationType === 'money') {
            $donationDetails = "
                <div class='donation-details'>
                    <h3 class='detail-header'>Donation Details:</h3>
                    <p><strong>Donation Type:</strong> Money Donation</p>
                    <p><strong>Amount:</strong> ₱" . number_format((float)$donationAmount, 2) . "</p>
                    <p><strong>Donation ID:</strong> $donationId</p>
                    <p><strong>Date:</strong> $donationDate</p>
                </div>";
        } else if ($donationType === 'in-kind') {
            $donationDetails = "
                <div class='donation-details'>
                    <h3 class='detail-header'>Donation Details:</h3>
                    <p><strong>Donation Type:</strong> In-Kind Donation</p>
                    <p><strong>Items:</strong> $donationItems</p>
                    <p><strong>Donation ID:</strong> $donationId</p>
                    <p><strong>Date:</strong> $donationDate</p>
                </div>";
        }

        $mail->Body = "
        <html>
        <head>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center; 
                    align-items: center; 
                    color: #2c3e50;
                    font-family: Arial, sans-serif;
                    background-color: #f8f9fa;
                }
                .email-container {
                    width: 700px;
                    background-color: #ffffff;
                    border-radius: 10px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #dc2626, #ef4444);
                    color: white;
                    padding: 40px 30px;
                    text-align: center;
                }
                .content {
                    padding: 40px 30px;
                }
                .logo {
                    width: 120px;
                    height: auto;
                    margin-bottom: 20px;
                }
                .main-title {
                    font-size: 36px;
                    margin: 0 0 10px 0;
                    font-weight: bold;
                }
                .sub-title {
                    font-size: 20px;
                    margin: 0;
                    opacity: 0.9;
                }
                .greeting {
                    font-size: 24px;
                    color: #dc2626;
                    margin: 0 0 20px 0;
                    font-weight: bold;
                }
                .message {
                    font-size: 16px;
                    line-height: 1.6;
                    color: #555;
                    margin: 20px 0;
                }
                .donation-details {
                    background-color: #f8f9fa;
                    border-left: 4px solid #dc2626;
                    padding: 20px;
                    margin: 30px 0;
                    border-radius: 0 8px 8px 0;
                }
                .detail-header {
                    color: #dc2626;
                    margin: 0 0 15px 0;
                    font-size: 18px;
                }
                .donation-details p {
                    margin: 8px 0;
                    font-size: 15px;
                }
                .impact-section {
                    background: #f8f9fa;
                    padding: 25px;
                    border-radius: 8px;
                    margin: 30px 0;
                    border: 1px solid #fecaca;
                }
                .impact-title {
                    color: #dc2626;
                    font-size: 20px;
                    margin: 0 0 15px 0;
                    font-weight: bold;
                }
                .footer {
                    background-color: #f8f9fa;
                    padding: 30px;
                    text-align: center;
                    color: #666;
                    font-size: 14px;
                }
                .contact-info {
                    margin: 15px 0;
                }
                .signature {
                    margin-top: 30px;
                    font-style: italic;
                    color: #dc2626;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <table width='100%' height='100%' border='0' cellspacing='0' cellpadding='0'>
                <td align='center' valign='middle'> 
                    <div class='email-container'>
                        <div class='header'>
                            <h1 class='main-title'>THANK YOU</h1>
                            <h2 class='sub-title'>for your generous donation</h2>
                        </div>
                        
                        <div class='content'>
                            <h2 class='greeting'>Dear $donorName,</h2>
                            
                            <p class='message'>
                                On behalf of all the children and staff at <strong>Tahanan ng Pagmamahal</strong>, 
                                we want to express our heartfelt gratitude for your generous donation. Your kindness 
                                and compassion make a real difference in the lives of the children we care for.
                            </p>
                            
                            $donationDetails
                            
                            <div class='impact-section'>
                                <h3 class='impact-title'>🌟 Your Impact</h3>
                                <p>Your donation helps us provide:</p>
                                <ul>
                                    <li>Nutritious meals and clean water</li>
                                    <li>Safe and comfortable living spaces</li>
                                    <li>Educational opportunities and supplies</li>
                                    <li>Medical care and health services</li>
                                    <li>Love, care, and hope for a better future</li>
                                </ul>
                                <p><strong>Because of donors like you, these children have a chance to grow, learn, and thrive.</strong></p>
                            </div>
                            
                            <p class='message'>
                                Your donation has been recorded in our system and will be used responsibly to 
                                ensure the best care for our children. We are committed to transparency and 
                                will keep you updated on how your contribution is making a difference.
                            </p>
                            
                            <p class='message'>
                                If you have any questions about your donation or would like to learn more about 
                                our programs, please don't hesitate to contact us.
                            </p>
                            
                            <p class='signature'>
                                With sincere appreciation,<br>
                                <strong>The Tahanan ng Pagmamahal Family</strong>
                            </p>
                        </div>
                        
                        <div class='footer'>
                            <div class='contact-info'>
                                <strong>Tahanan ng Pagmamahal</strong><br>
                                Children's Home and Care Center<br>
                                Email: tahananpch@gmail.com<br>
                                Phone: 0917 525 7188
                            </div>
                            <p>This email was sent as a confirmation of your donation. Please keep this for your records.</p>
                        </div>
                    </div>   
                </td>
            </table>
        </html>";

        $mail->AltBody = "Dear $donorName,\n\nThank you for your generous donation to Tahanan ng Pagmamahal. Your kindness makes a real difference in the lives of the children we care for.\n\nDonation Details:\nType: $donationType\nID: $donationId\nDate: $donationDate\n\nWith sincere appreciation,\nThe Tahanan ng Pagmamahal Family";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Simple fallback email function using PHP's built-in mail()
function sendSimpleThankYouEmail($donorName, $donorEmail, $donationType, $donationAmount = '', $donationItems = '', $donationId = '', $donationDate = '') {
    // Create donation details based on type
    $donationDetails = '';
    if ($donationType === 'money') {
        $donationDetails = "Amount: ₱" . number_format((float)$donationAmount, 2) . "\n";
    } else if ($donationType === 'in-kind') {
        $donationDetails = "Items: $donationItems\n";
    }
    
    $subject = "Thank You for Your Generous Donation - Tahanan ng Pagmamahal";
    
    $message = "Dear $donorName,\n\n";
    $message .= "On behalf of all the children and staff at Tahanan ng Pagmamahal, we want to express our heartfelt gratitude for your generous donation.\n\n";
    $message .= "Donation Details:\n";
    $message .= "Type: $donationType\n";
    $message .= $donationDetails;
    $message .= "Donation ID: $donationId\n";
    $message .= "Date: $donationDate\n\n";
    $message .= "Your donation helps us provide:\n";
    $message .= "- Nutritious meals and clean water\n";
    $message .= "- Safe and comfortable living spaces\n";
    $message .= "- Educational opportunities and supplies\n";
    $message .= "- Medical care and health services\n";
    $message .= "- Love, care, and hope for a better future\n\n";
    $message .= "Because of donors like you, these children have a chance to grow, learn, and thrive.\n\n";
    $message .= "With sincere appreciation,\n";
    $message .= "The Tahanan ng Pagmamahal Family\n\n";
    $message .= "---\n";
    $message .= "Tahanan ng Pagmamahal\n";
    $message .= "Children's Home Inc.\n";
    $message .= "Email: tahananpch@gmail.com\n";
    $message .= "Phone: 0917 525 7188";
    
    $headers = "From: tahananpagmamahal@email.com\r\n";
    $headers .= "Reply-To: tahananpagmamahal@email.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($donorEmail, $subject, $message, $headers);
}

// Legacy OTP functionality (keeping for backward compatibility)
function ReSendOTP() {
    $length = 6;
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $_SESSION["otp"] = '';

    for ($x = 0; $x < $length; $x++) {
        $_SESSION["otp"] .=  $characters[random_int(0, strlen($characters) - 1)];
    }
}

// Legacy OTP email sending (keeping existing functionality)
if (isset($_SESSION['receiver']) && isset($_SESSION['otp'])) {
    $Receiver = $_SESSION['receiver'];
    $username = $_SESSION['receivername'] ?? '';
    $OTP = $_SESSION['otp'];

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

        $mail->setFrom('coffeecornerofficial1@gmail.com', 'Tahanan ng Pagmamahal');
        $mail->addAddress($Receiver, $username);

        $mail->isHTML(true);
        $mail->Subject = "OTP Verification - Tahanan ng Pagmamahal";
        $mail->Body = "
        <html>
        <head>
            <style>
                body { margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; color: #2c3e50; font-family: Arial, sans-serif; }
                .cont1 { width: 700px; background-color: #dc2626; color: white; padding: 40px; text-align: center; border-radius: 10px; }
                .maintxt1 { font-size: 40px; margin: 20px 0 10px 0; }
                .maintxt2 { font-size: 20px; margin: 0 0 20px 0; }
                .maintxt3 { font-size: 15px; margin: 20px 0; line-height: 1.6; }
                .cont2 { width: 500px; background-color: #ef4444; margin: 20px auto; padding: 40px; font-size: 36px; font-weight: bold; border-radius: 8px; }
            </style>
        </head>
        <body>
            <table width='100%' height='100%' border='0' cellspacing='0' cellpadding='0'>
                <td align='center' valign='middle'> 
                    <div class='cont1'>
                        <h1 class='maintxt1'>HELLO</h1>
                        <h2 class='maintxt2'>$Receiver</h2>
                        <p class='maintxt3'>Thank you for choosing Tahanan ng Pagmamahal. Your One-Time Password (OTP) is provided below.</p>
                        <p class='maintxt3'>Please use this code to complete your authentication. For security purposes, do not share this code with anyone.</p>
                        <div class='cont2'>$OTP</div>
                    </div>   
                </td>
            </table>
        </html>";

        $mail->AltBody = 'Your OTP is: ' . $OTP;
        $mail->send();
    } catch (Exception $e) {
        echo "Failed To Send {$mail->ErrorInfo}";
    }
}
?>