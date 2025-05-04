<?php
// Start the session if it's not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'Models/BookingHistory.php';

//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Booking History';
$view->activePage = 'booking-history';

// Get user ID from the session
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    // Create an instance of the model
    $bookingHistory = new BookingHistory($userId);

    // Fetch booking details
    $view->bookings = $bookingHistory->getUserBookingHistory();
} else {
    $view->bookings = [];
    $errorMessage = "User ID is missing.";
}

// Load the view
require_once 'Views/booking-history.phtml';

