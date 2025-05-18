<?php
// Start the session if it's not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'RentalUser') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
}
//fetch the user ID from the session
$userId = $_SESSION['user_id'] ?? null;

//login controller
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Booking Details';
$view->activePage = 'booknigDetails';

//require the model
require_once 'Models/BookingHistory.php';


//get the booking ID from the URL
$bookingId = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;

//if the booking ID is not set, redirect to the booking history page
if (!$bookingId) {
    //tell the user that the booking ID is missing
    $errorMessage = "Booking ID is missing.";

    //alert the user
    echo "<script>alert('$errorMessage');</script>";

    //redirect to the booking history page
    header('Location: booking-history.php');
    exit;
}else{
    // Create an instance of the model
    $bookingHistory = new BookingHistory($userId);

    // Fetch booking details
    $view->bookingDetails = $bookingHistory->getBookingDetails($bookingId);
}




require_once 'Views/booking-details.phtml';
