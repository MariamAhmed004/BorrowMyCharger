<?php
// Start the session if it's not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the session variable is not set or if the role is not 'HomeOwner'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HomeOwner') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
}

// Fetch the user ID from the session
$userId = $_SESSION['user_id'] ?? null;

// Redirect to login if not logged in
if (!$userId) {
    header('Location: login.php');
    exit;
}

// Initialize view object
$view = new stdClass();
$view->pageTitle = 'Booking Request Details';
$view->activePage = 'bookingRequests';

// Require the model
require_once 'Models/BorrowRequestModel.php';

// Get the booking ID from the URL
$bookingId = $_GET['booking_id'] ?? null;

// If the booking ID is not set, redirect to the booking requests page
if (!$bookingId) {
    $view->actionMessage = "Booking ID is missing.";
    header('Location: borrow-request.php');
    exit;
}

// Create an instance of the model
$borrowRequestModel = new BorrowRequestModel();

// Verify that this booking belongs to the homeowner
if (!$borrowRequestModel->isBookingOwnedByHomeowner($bookingId, $userId)) {
    $view->actionMessage = "You don't have permission to view this booking request.";
    header('Location: booking-requests.php');
    exit;
}

// Handle form submissions for approving or rejecting requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        $action = $_POST['action'];
        $bookingIdPost = $_POST['booking_id'];
        
        // Verify booking ID from POST matches URL parameter for security
        if ($bookingIdPost == $bookingId) {
            $newStatusId = ($action === 'approve') ? 2 : 3; // 2 for approved, 3 for rejected
            $actionText = ($action === 'approve') ? 'approved' : 'rejected';
            
            if ($borrowRequestModel->updateBookingStatus($bookingId, $newStatusId)) {
                $view->actionMessage = "Booking request successfully $actionText.";
            } else {
                $view->actionMessage = "Failed to $action booking request. Please try again.";
            }
        } else {
            $view->actionMessage = "Invalid booking ID. Please try again.";
        }
    }
}

// Fetch booking details
$view->bookingDetails = $borrowRequestModel->getBookingRequestDetails($bookingId, $userId);

// If no booking details found, redirect with error message
if (!$view->bookingDetails) {
    $view->actionMessage = "Booking request not found or you don't have permission to view it.";
    header('Location: borrow-request.php');
    exit;
}

// Load the view
require_once 'Views/borrow-request-details.phtml';