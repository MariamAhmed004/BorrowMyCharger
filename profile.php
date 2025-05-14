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
    // For security, require password confirmation for deletion
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'on') {
        if ($profile->deleteUserAccount()) {
            session_destroy();
            header('Location: index.php?msg=account_deleted');
            exit;
        } else {
            $errorMessage = "Unable to delete account. Please try again later.";
        }
    } else {
        $errorMessage = "You must confirm deletion by checking the box.";
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    
    if (empty($firstName) || empty($lastName) || empty($phoneNumber)) {
        $errorMessage = "All fields are required.";
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