<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'Models/Profile.php';
$view = new stdClass();
$view->pageTitle = 'User Profile';
$view->activePage = 'profile';
$userId = $_SESSION['user_id'];
$profile = new Profile($userId);
$userData = $profile->getUserData();
$errorMessage = '';
$successMessage = '';

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    if ($profile->deleteUserAccount()) {
        session_destroy();
        header('Location: index.php?msg=account_deleted');
        exit;
    } else {
        $errorMessage = "Unable to delete account. Please try again later.";
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $firstName = trim(htmlentities($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $lastName = trim(htmlentities($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $phoneNumber = trim(htmlentities($_POST['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'));
    
    // Add validation for names containing spaces
    if (empty($firstName) || empty($lastName) || empty($phoneNumber)) {
        $errorMessage = "All fields are required.";
    } elseif (strpos($firstName, ' ') !== false) {
        $errorMessage = "First name cannot contain spaces.";
    } elseif (strpos($lastName, ' ') !== false) {
        $errorMessage = "Last name cannot contain spaces.";
    } elseif (!preg_match('/^[A-Za-z]+$/', $firstName)) {
        $errorMessage = "First name must contain only letters.";
    } elseif (!preg_match('/^[A-Za-z]+$/', $lastName)) {
        $errorMessage = "Last name must contain only letters.";
    } elseif (!preg_match('/^\d{8,}$/', $phoneNumber)) {
        $errorMessage = "Phone number must be at least 8 digits and contain only numbers.";
    } else {
        $result = $profile->updateUserProfile($firstName, $lastName, null, $phoneNumber);
        if ($result) {
            $successMessage = "Profile updated successfully!";
            $userData = $profile->getUserData();
        } else {
            $errorMessage = "Failed to update profile.";
        }
    }
}

require_once 'Views/profile.phtml';