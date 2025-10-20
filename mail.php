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

// Check if this is an AJAX request for sending volunteer welcome email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_volunteer_welcome') {
    header('Content-Type: application/json');
    
    // Log that mail.php received the request
    error_log("=== MAIL.PHP: VOLUNTEER WELCOME EMAIL REQUEST RECEIVED ===");
    error_log("PHP Version: " . phpversion());
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("POST Data: " . json_encode($_POST));
    
    try {
        $volunteerName = $_POST['volunteerName'] ?? '';
        $volunteerEmail = $_POST['volunteerEmail'] ?? '';
        $volunteerId = $_POST['volunteerId'] ?? '';
        $dateAdded = $_POST['dateAdded'] ?? '';
        $preferredDepartment = $_POST['preferredDepartment'] ?? 'Not specified';
        $hasAccount = $_POST['hasAccount'] ?? '0';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $websiteLink = $_POST['websiteLink'] ?? 'https://tahananngpagmamahal.capstone-two.com/';
        $apkLink = $_POST['apkLink'] ?? 'https://drive.google.com/file/d/1LNQzny-4qzoEemFZ1xsFcu4gZsJWXwHV/view';
        
        error_log("📧 Extracted Data:");
        error_log("  - Name: " . $volunteerName);
        error_log("  - Email: " . $volunteerEmail);
        error_log("  - Volunteer ID: " . $volunteerId);
        error_log("  - Has Account: " . $hasAccount);
        error_log("  - Username: " . $username);
        
        if (empty($volunteerEmail) || empty($volunteerName)) {
            error_log("❌ VALIDATION FAILED: Missing volunteer email or name");
            echo json_encode(['success' => false, 'message' => 'Missing volunteer email or name']);
            exit;
        }
        
        error_log("✅ Validation passed, calling sendVolunteerWelcomeEmail()...");
        $result = sendVolunteerWelcomeEmail($volunteerName, $volunteerEmail, $volunteerId, $dateAdded, $preferredDepartment, $hasAccount, $username, $password, $websiteLink, $apkLink);
        
        if ($result) {
            error_log("✅ ✅ ✅ EMAIL SENT SUCCESSFULLY! ✅ ✅ ✅");
            echo json_encode(['success' => true, 'message' => 'Welcome email sent successfully']);
        } else {
            error_log("❌ sendVolunteerWelcomeEmail() returned FALSE");
            echo json_encode(['success' => false, 'message' => 'Failed to send welcome email']);
        }
    } catch (Exception $e) {
        error_log("❌ EXCEPTION in mail.php handler: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    } catch (Error $e) {
        error_log("❌ FATAL ERROR in mail.php handler: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $e->getMessage()]);
    } catch (Throwable $e) {
        error_log("❌ THROWABLE in mail.php handler: " . $e->getMessage());
        error_log("Throwable trace: " . $e->getTraceAsString());
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
        $mail->Username   = 'coffeecornerofficial1@gmail.com';  // Keep existing credentials
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

function sendVolunteerWelcomeEmail($volunteerName, $volunteerEmail, $volunteerId, $dateAdded, $preferredDepartment, $hasAccount, $username, $password, $websiteLink, $apkLink) {
    error_log("=== ENTERING sendVolunteerWelcomeEmail() FUNCTION ===");
    error_log("Parameters received:");
    error_log("  - volunteerName: " . $volunteerName);
    error_log("  - volunteerEmail: " . $volunteerEmail);
    error_log("  - volunteerId: " . $volunteerId);
    error_log("  - dateAdded: " . $dateAdded);
    error_log("  - preferredDepartment: " . $preferredDepartment);
    error_log("  - hasAccount: " . $hasAccount);
    error_log("  - username: " . $username);
    error_log("  - websiteLink: " . $websiteLink);
    error_log("  - apkLink: " . $apkLink);
    
    $mail = new PHPMailer(true);
    error_log("✅ PHPMailer object created");

    try {
        error_log("📧 Configuring SMTP settings...");
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'coffeecornerofficial1@gmail.com';
        $mail->Password   = 'sfxr wvap lbwj bszs';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        error_log("✅ SMTP settings configured (Host: smtp.gmail.com, Port: 587)");

        error_log("📧 Setting recipients...");
        // Recipients
        $mail->setFrom('tahananngpagmamahalcapstone@gmail.com', 'Tahanan ng Pagmamahal');
        $mail->addAddress($volunteerEmail, $volunteerName);
        error_log("✅ Recipients set (From: tahananngpagmamahalcapstone@gmail.com, To: " . $volunteerEmail . ")");

        error_log("📧 Building email content...");
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Welcome to Tahanan ng Pagmamahal - Volunteer Registration Successful";
        
        // Create login credentials section if account was created
        $loginSection = '';
        if ($hasAccount === '1') {
            error_log("ℹ️ Account creation enabled - adding credentials section");
            $loginSection = "
                <div class='credentials-section'>
                    <h3 class='section-title'>🔐 Your Login Credentials</h3>
                    <div class='credential-box'>
                        <p><strong>Username:</strong> $username</p>
                        <p><strong>Password:</strong> $password</p>
                    </div>
                    <p class='note'>⚠️ Please keep these credentials safe and change your password after your first login for security.</p>
                </div>";
        } else {
            error_log("ℹ️ Account creation disabled - no credentials section");
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
                .details-section {
                    background-color: #f8f9fa;
                    border-left: 4px solid #dc2626;
                    padding: 20px;
                    margin: 30px 0;
                    border-radius: 0 8px 8px 0;
                }
                .credentials-section {
                    background: #fef2f2;
                    border: 2px solid #dc2626;
                    padding: 25px;
                    border-radius: 8px;
                    margin: 30px 0;
                }
                .section-title {
                    color: #dc2626;
                    margin: 0 0 15px 0;
                    font-size: 18px;
                }
                .credential-box {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 15px 0;
                    border: 1px solid #fecaca;
                }
                .credential-box p {
                    margin: 10px 0;
                    font-size: 16px;
                    font-family: 'Courier New', monospace;
                }
                .note {
                    font-size: 14px;
                    color: #666;
                    margin-top: 15px;
                    font-style: italic;
                }
                .links-section {
                    background: #f8f9fa;
                    padding: 25px;
                    border-radius: 8px;
                    margin: 30px 0;
                    border: 1px solid #e5e7eb;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    margin: 10px 5px;
                    background: linear-gradient(135deg, #dc2626, #ef4444);
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    transition: transform 0.2s;
                }
                .button:hover {
                    transform: translateY(-2px);
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
                ul {
                    line-height: 1.8;
                }
            </style>
        </head>
        <body>
            <table width='100%' height='100%' border='0' cellspacing='0' cellpadding='0'>
                <td align='center' valign='middle'>
                    <div class='email-container'>
                        <div class='header'>
                            <h1 class='main-title'>WELCOME!</h1>
                            <h2 class='sub-title'>Thank you for joining our mission</h2>
                        </div>
                        
                        <div class='content'>
                            <h2 class='greeting'>Dear $volunteerName,</h2>
                            
                            <p class='message'>
                                Welcome to the <strong>Tahanan ng Pagmamahal</strong> family! We are thrilled to have you 
                                join us as a volunteer. Your willingness to dedicate your time and talents to help 
                                the children in our care is truly inspiring and deeply appreciated.
                            </p>
                            
                            <div class='details-section'>
                                <h3 class='section-title'>📋 Your Registration Details:</h3>
                                <p><strong>Volunteer ID:</strong> $volunteerId</p>
                                <p><strong>Registration Date:</strong> $dateAdded</p>
                                <p><strong>Preferred Department:</strong> $preferredDepartment</p>
                            </div>
                            
                            $loginSection
                            
                            <div class='links-section'>
                                <h3 class='section-title'>🌐 Access Our System</h3>
                                <p style='margin-bottom: 20px;'>You can access Tahanan ng Pagmamahal OMS through:</p>
                                
                                <div style='text-align: center;'>
                                    <a href='$websiteLink' class='button'>🌐 Visit Website</a>
                                    <a href='$apkLink' class='button'>📱 Download Mobile App</a>
                                </div>
                                
                                <p class='note' style='margin-top: 20px;'>
                                    💡 <strong>Tip:</strong> Download our mobile app for convenient access on your phone or tablet!
                                </p>
                            </div>
                            
                            <div class='details-section'>
                                <h3 class='section-title'>🎯 What's Next?</h3>
                                <ul>
                                    <li><strong>Log in to your account</strong> using the credentials provided above (if account was created)</li>
                                    <li><strong>Complete your profile</strong> with any additional information</li>
                                    <li><strong>Review your schedule</strong> and available volunteer opportunities</li>
                                    <li><strong>Join our orientation</strong> to learn more about our programs and policies</li>
                                    <li><strong>Start making a difference</strong> in the lives of our children!</li>
                                </ul>
                            </div>
                            
                            <p class='message'>
                                Your journey as a volunteer at Tahanan ng Pagmamahal begins today. Together, we will 
                                create a brighter future for the children who call this place home. Thank you for 
                                being part of our mission to provide love, care, and hope.
                            </p>
                            
                            <p class='message'>
                                If you have any questions or need assistance, please don't hesitate to contact us. 
                                We're here to support you every step of the way!
                            </p>
                            
                            <p class='signature'>
                                With gratitude and appreciation,<br>
                                <strong>The Tahanan ng Pagmamahal Team</strong>
                            </p>
                        </div>
                        
                        <div class='footer'>
                            <div class='contact-info'>
                                <strong>Tahanan ng Pagmamahal</strong><br>
                                Children's Home and Care Center<br>
                                45 Dr. Pilapil St., Brgy. Sagad, Pasig Metro Manila, Philippines<br>
                                Email: tahananpch@gmail.com<br>
                                Phone: (+63) 917 525 7168 | Tel: +632 8631 7188
                            </div>
                            <p>This email was sent as a confirmation of your volunteer registration.</p>
                        </div>
                    </div>
                </td>
            </table>
        </body>
        </html>";

        $altBody = "Dear $volunteerName,\n\n";
        $altBody .= "Welcome to Tahanan ng Pagmamahal! We are thrilled to have you join us as a volunteer.\n\n";
        $altBody .= "Your Registration Details:\n";
        $altBody .= "Volunteer ID: $volunteerId\n";
        $altBody .= "Registration Date: $dateAdded\n";
        $altBody .= "Preferred Department: $preferredDepartment\n\n";
        
        if ($hasAccount === '1') {
            $altBody .= "Your Login Credentials:\n";
            $altBody .= "Username: $username\n";
            $altBody .= "Password: $password\n\n";
            $altBody .= "Please keep these credentials safe and change your password after your first login.\n\n";
        }
        
        $altBody .= "Access Our System:\n";
        $altBody .= "Website: $websiteLink\n";
        $altBody .= "Mobile App: $apkLink\n\n";
        $altBody .= "What's Next?\n";
        $altBody .= "- Log in to your account using the credentials provided above\n";
        $altBody .= "- Complete your profile with any additional information\n";
        $altBody .= "- Review your schedule and available volunteer opportunities\n";
        $altBody .= "- Join our orientation to learn more about our programs\n";
        $altBody .= "- Start making a difference in the lives of our children!\n\n";
        $altBody .= "If you have any questions, please contact us at tahananpch@gmail.com or call (+63) 917 525 7168\n\n";
        $altBody .= "With gratitude and appreciation,\n";
        $altBody .= "The Tahanan ng Pagmamahal Team";

        $mail->AltBody = $altBody;
        error_log("✅ Email content prepared (HTML + Alt body)");

        error_log("📤 Attempting to send email via SMTP...");
        error_log("  - To: " . $volunteerEmail);
        error_log("  - Subject: " . $mail->Subject);
        
        $mail->send();
        
        error_log("✅ ✅ ✅ PHPMailer->send() executed successfully! ✅ ✅ ✅");
        error_log("Email sent to: " . $volunteerEmail);
        return true;
    } catch (Exception $e) {
        error_log("❌ ❌ ❌ EXCEPTION IN sendVolunteerWelcomeEmail() ❌ ❌ ❌");
        error_log("Exception Type: " . get_class($e));
        error_log("Exception Message: " . $e->getMessage());
        error_log("PHPMailer ErrorInfo: " . $mail->ErrorInfo);
        error_log("Exception File: " . $e->getFile());
        error_log("Exception Line: " . $e->getLine());
        error_log("Exception Trace: " . $e->getTraceAsString());
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