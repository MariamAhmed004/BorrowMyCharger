<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a homeowner (role_id = 2)
if (!isset($_SESSION['user_id']) ) {
    // Redirect to login page or show error
    header('Location: login.php?error=unauthorized');
    exit();
}

require_once 'Models/HomeOwnerHome.php';

// Get the homeowner ID from session
$userId = $_SESSION['user_id'];

// Set up the view
$view = new stdClass();
$view->pageTitle = 'Home';
$view->activePage = 'dashboard';

// Create an instance of the model and pass the user id
$homeOwnerHome = new HomeOwnerHome($userId);
// Get bookings for the calendar
$view->bookings = $homeOwnerHome->getAllBookings();
// Get statistics for the dashboard
$view->chargePointsCount = $homeOwnerHome->getChargePointsCount();
$view->pendingRequestsCount = $homeOwnerHome->getPendingBookingRequestsCount();
$view->totalBookingsCount = $homeOwnerHome->getTotalBookingsCount();
$view->approvedBookingsCount = $homeOwnerHome->getApprovedBookingsCount();



// Get upcoming bookings
$view->upcomingBookings = $homeOwnerHome->getUpcomingBookings();

// Load the view
require_once 'Views/home_homeowner.phtml';