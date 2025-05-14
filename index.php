<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Start session
session_start();
// Include Composer's autoloader
require 'vendor/autoload.php'; 
require_once('Models/chargePoint1.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // Validate input
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'borrowmycharger@gmail.com'; // Your Gmail address
        $mail->Password = 'igpd eanh psic xlgt'; // Your Gmail password or App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587; // Use 587 for TLS

        // Recipients
        $mail->setFrom('borrowmycharger@gmail.com', 'BorrowMyCharger'); // Your email and name
        $mail->addAddress($email, $name); // User email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Thank you for contacting us';
        $mail->Body = "Hello $name,<br><br>Thank you for reaching out!<br>Your message:<br>$message<br><br>Best regards,<br>Your Company";

        // Send the email
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Your message has been sent!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }

    exit;
}
$chargePointModel = new MyChargePointModel();
$featuredChargePoints = $chargePointModel->getFeaturedChargePoints();
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    $minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
    $maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;

    if ($filter === "available") {
        $chargePoints = $chargePointModel->getAvailableChargePoints($minPrice, $maxPrice);
    } elseif ($filter === "unavailable") {
        $chargePoints = $chargePointModel->getUnAvailableChargePoints($minPrice, $maxPrice);
    } else {
        $chargePoints = $chargePointModel->getChargePointDetails($minPrice, $maxPrice);
    }

    header('Content-Type: application/json');
    echo json_encode($chargePoints);
    exit;
}

// Include the view
require_once 'Views/index.phtml';