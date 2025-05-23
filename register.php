<?php
require_once 'Models/register.php';
$view = new stdClass();
$view->pageTitle = 'Register';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $firstname = ucfirst(trim($_POST['firstname'] ?? ''));
    $lastname = ucfirst(trim($_POST['lastname'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $userType = $_POST['user_type'] ?? '';

    $errors = [];

    // Validate
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }

    if (strlen($firstname) < 2) {
        $errors[] = "Firstname must be at least 2 characters.";
    }

    if (strlen($lastname) < 2) {
        $errors[] = "Lastname must be at least 2 characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (!preg_match('/^\d{7,15}$/', $phone)) {
        $errors[] = "Phone number must be 7 to 15 digits.";
    }

    if (empty($userType)) {
        $errors[] = "Please select a user type.";
    }

    // Map userType to role_id
    $roleMapping = [
        'home-owner' => 2,
        'rental-user' => 3
    ];

    $roleId = $roleMapping[$userType] ?? null;

    // Set account status based on user type
    if ($userType == 'home-owner') {
        $accountStatusId = 3; // Pending status for home owners
    } else if ($userType == 'rental-user') {
        $accountStatusId = 1; // Approved status for rental users
    } else {
        $accountStatusId = null;
    }

    if (!empty($errors)) {
        $view->errorMessage = implode("<br>", $errors);
    } else {
        $register = new Register();

        // Check if user already exists before trying to register
        if ($register->checkUserExists($username, $email)) {
            $view->errorMessage = "Username or email already exists.";
        } else {
            $success = $register->createUser($username, $firstname, $lastname, $email, $password, $phone, $roleId, $accountStatusId);

            if ($success) {
                header("Location: login.php");
                exit();
            } else {
                $view->errorMessage = "Something went wrong. Please try again.";
            }
        }
    }
}

require_once 'Views/register.phtml';
?>
