<?php
session_start();
require_once 'Models/userProfile.php';

class ProfileController {
    private $userId;
    private $profile;
    public $errorMessage = '';
    public $successMessage = '';
    public $userData = null;
    
    public function __construct() {
        // Assuming you have a session with user_id stored
        session_start();
        if (isset($_SESSION['user_id'])) {
            $this->userId = $_SESSION['user_id'];
            $this->profile = new UserProfile($this->userId);
            $this->userData = $this->profile->getUserData();
        } 
    }
    
    public function loadProfilePage() {
        $pageTitle = 'Profile';
        $activePage = 'profile';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
            $this->processProfileUpdate();
        }
        
        require_once 'Views/profile.phtml';
    }
    
    private function processProfileUpdate() {
        // Get form data
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        
        // Validate form data
        if (empty($firstName)) {
            $this->errorMessage = "First name is required";
            return;
        }
        
        if (empty($lastName)) {
            $this->errorMessage = "Last name is required";
            return;
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errorMessage = "Valid email is required";
            return;
        }
        
        if (empty($phoneNumber)) {
            $this->errorMessage = "Phone number is required";
            return;
        }
        
        // Update profile
        $result = $this->profile->updateUserProfile($firstName, $lastName, $email, $phoneNumber);
        
        if ($result) {
            $this->successMessage = "Profile updated successfully";
            // Refresh user data after update
            $this->userData = $this->profile->getUserData();
        } else {
            $this->errorMessage = "Failed to update profile";
        }
    }
}

// Initialize controller when this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'profile.php') {
    $controller = new ProfileController();
    $controller->loadProfilePage();
}