<?php
// Load Login model
require_once 'Models/Login.php';

// Create view object
$view = new stdClass();
$view->pageTitle = 'Login';
$view->errorMessage = '';
$view->email = ''; // Keep email for repopulating field on error

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Save email to refill the field if error happens
    $view->email = htmlspecialchars($email);

    // Check if fields are filled
    if ($email && $password) {
        // Try to authenticate user
        $authResult = Login::authenticate($email, $password);

        if (is_array($authResult)) {
            // Successful login -> Start session
            session_start();
            
            $_SESSION['user_id'] = $authResult['id'];  // User ID for identification
            $_SESSION['firstname'] = $authResult['first_name'];  // Store the user's first name
            $_SESSION['lastname'] = $authResult['last_name'];    // Store the user's last name
            $_SESSION['username'] = $authResult['username'];     // Store the username

            // Fetch role title based on role_id
            switch ($authResult['role_id']) {
                case 1:
                    $_SESSION['role'] = 'Admin';
                    break;
                case 2:
                    $_SESSION['role'] = 'HomeOwner';
                    break;
                case 3:
                    $_SESSION['role'] = 'RentalUser';
                    break;
                default:
                    $_SESSION['role'] = 'Guest';  // Default role if no valid role_id found
            }

            // Redirect to homepage or user dashboard
            header('Location: index.php'); // Change this to your dashboard or home page
            exit;
        } else {
            // Set specific error messages based on authentication result
            if ($authResult === 'email_not_found') {
                $view->errorMessage = 'Email does not exist.';
            } elseif ($authResult === 'incorrect_password') {
                $view->errorMessage = 'Incorrect password.';
            }
        }
    } else {
        // Missing fields
        $view->errorMessage = 'Please fill in both fields.';
    }
}

// Load login view
require_once 'Views/login.phtml';