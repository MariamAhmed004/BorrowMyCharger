<?php
require_once 'Models/PasswordReset.php';

$view = new stdClass();
$view->email = '';
$view->errorMessage = '';
$view->message = '';
$view->tokenSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_token'])) {
        $email = trim($_POST['email'] ?? '');
        $view->email = htmlspecialchars($email);

        $user = PasswordReset::emailExists($email);
        if ($user) {
            $token = PasswordReset::generateNumericToken(); // 6-digit token
            PasswordReset::createToken($user['user_id'], $token);

            if (PasswordReset::sendEmail($email, $token)) {
                $view->message = "Token has been sent to your email.";
                $view->tokenSent = true;
            } else {
                $view->errorMessage = "Failed to send email. Please try again.";
            }
        } else {
            $view->errorMessage = "Email does not exist.";
        }
    } elseif (isset($_POST['reset_password'])) {
        $token = trim($_POST['token'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $view->email = htmlspecialchars($_POST['email'] ?? '');
        $view->tokenSent = true; // Keep form expanded if validation fails
        
        // Validate token format (6 digits only)
        if (!preg_match('/^\d{6}$/', $token)) {
            $view->errorMessage = "Token must be exactly 6 digits.";
        } else {
            $user = PasswordReset::validateToken($token);
            if ($user) {
                if (validatePassword($newPassword)) {
                    PasswordReset::updatePassword($user['user_id'], $newPassword);
                    PasswordReset::deleteToken($token);
                    header('Location: login.php?message=Password reset successfully. Please login.');
                    exit;
                } else {
                    $view->errorMessage = "Password must be at least 8 characters long, contain 1 uppercase, 1 lowercase, and 1 number.";
                }
            } else {
                $view->errorMessage = "Invalid or expired token.";
            }
        }
    }
}

function validatePassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/', $password);
}


require_once 'Views/forgot_password.phtml';