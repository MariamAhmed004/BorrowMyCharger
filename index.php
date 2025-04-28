<?php
// Start session
session_start();
// Include Composer's autoloader
require 'vendor/autoload.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$view = new stdClass();
$view->pageTitle = '';

// Process form submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    // Collect form data
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $messageContent = htmlspecialchars($_POST['message']);
    
    // Create a new PHPMailer instance for confirmation to user
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'borrowmycharger@gmail.com'; 
        $mail->Password = 'wfev cxol dfcw qbjd'; // Use your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port = 587;
        
        // Recipients - only send to the user
        $mail->setFrom('borrowmycharger@gmail.com', 'BorrowMyCharger');
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Confirmation: Your message has been received";
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e1e1; border-radius: 5px;'>
                    <h2 style='color: #B68942;'>Thank You for Contacting Us!</h2>
                    <p>Hello $name,</p>
                    <p>We have received your message and will get back to you shortly.</p>
                    <p>Here's a copy of what you sent us:</p>
                    <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                        <p><strong>Message:</strong><br>$messageContent</p>
                    </div>
                    <p>Best regards,<br>The Borrow My Charger Team</p>
                </div>
            </body>
            </html>
        ";
        
        // Send the email to user
        $mail->send();
        
        // Create a separate email for admin notification
        $adminMail = new PHPMailer(true);
        
        // Server settings (same as above)
        $adminMail->isSMTP();
        $adminMail->Host = 'smtp.gmail.com';
        $adminMail->SMTPAuth = true;
        $adminMail->Username = 'borrowmycharger@gmail.com'; 
        $adminMail->Password = 'wfev cxol dfcw qbjd';
        $adminMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $adminMail->Port = 587;
        
        // Set up admin notification email
        $adminMail->setFrom('borrowmycharger@gmail.com', 'Contact Form Notification');
        $adminMail->addAddress('borrowmycharger@gmail.com', 'BorrowMyCharger Admin');
        
        // Content for admin
        $adminMail->isHTML(true);
        $adminMail->Subject = "New Contact Form Submission from $name";
        $adminMail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e1e1; border-radius: 5px;'>
                    <h2 style='color: #B68942;'>New Contact Form Submission</h2>
                    <p><strong>From:</strong> $name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Message:</strong></p>
                    <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                        $messageContent
                    </div>
                    <p><small>This is an automated notification from your website's contact form.</small></p>
                </div>
            </body>
            </html>
        ";
        
        // Send admin notification
        $adminMail->send();
        
        $response['success'] = true;
        $response['message'] = 'Thank you! Your message has been sent successfully. We will be in touch soon.';
    } catch (Exception $e) {
        $response['message'] = "Sorry, there was an error sending your message. Please try again later.";
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Include the view
require_once 'Views/index.phtml';