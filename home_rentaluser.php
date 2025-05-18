<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'RentalUser') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
}

require_once 'Models/RentalUser-Home.php';
$userId = $_SESSION['user_id'];
//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Home';
$view->activePage = 'dashboard';

//create an instance of the model and pass the user id
$rentalUserHome = new RentalUserHome($userId);
$view->bookings = $rentalUserHome->getUserActiveReservations();
$view->activeReservation = $rentalUserHome->getUserActiveReservationCount();
$view->borrowingCount = $rentalUserHome->getUserBorrowingCount();
$view->availableChargingPoints = $rentalUserHome->getTotalChargePoints();

// Get reservation statistics by status
$view->pendingReservations = $rentalUserHome->getReservationsByStatus(1); // Pending
$view->approvedReservations = $rentalUserHome->getReservationsByStatus(2); // Approved
$view->rejectedReservations = $rentalUserHome->getReservationsByStatus(3); // Rejected

// Pagination for approved upcoming reservations
$reservationsPerPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $reservationsPerPage;

// Get only approved upcoming reservations with pagination
$view->upcomingReservations = $rentalUserHome->getApprovedUpcomingReservations($reservationsPerPage, $offset);
$view->totalApprovedReservations = $rentalUserHome->countApprovedUpcomingReservations();
$view->totalPages = ceil($view->totalApprovedReservations / $reservationsPerPage);
$view->currentPage = $page;

$errorMessage = '';
require_once 'Views/home_rentaluser.phtml';