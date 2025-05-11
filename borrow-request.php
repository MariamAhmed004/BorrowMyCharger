<?php
//Borrow Request controller
//Handle requests for homeowners to view and manage booking requests

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a homeowner (role_id = 2)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or show error
    header('Location: login.php?error=unauthorized');
    exit();
}

// Set up view object
$view = new stdClass();
$view->pageTitle = 'Borrow Request';
$view->activePage = 'borrow-request';

// Include the model
require_once 'Models/BorrowRequestModel.php';
$borrowRequestModel = new BorrowRequestModel();

// Get the homeowner ID from session
$homeownerId = $_SESSION['user_id'];

// Handle approve/reject action
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
    $action = $_POST['action'];
    
    // Verify the booking belongs to this homeowner
    if ($borrowRequestModel->isBookingOwnedByHomeowner($bookingId, $homeownerId)) {
        if ($action === 'approve') {
            $success = $borrowRequestModel->updateBookingStatus($bookingId, 2); // 2 = Approved
            $view->actionMessage = $success ? 'Booking request approved successfully!' : 'Failed to approve booking request.';
        } elseif ($action === 'reject') {
            $success = $borrowRequestModel->updateBookingStatus($bookingId, 3); // 3 = Rejected
            $view->actionMessage = $success ? 'Booking request rejected successfully!' : 'Failed to reject booking request.';
        }
    } else {
        $view->actionMessage = 'Invalid booking request.';
    }
}

// Get all booking requests for this homeowner
$view->bookingRequests = $borrowRequestModel->getBookingRequestsByHomeownerId($homeownerId);

// Load the view
require_once 'Views/borrow-request.phtml';