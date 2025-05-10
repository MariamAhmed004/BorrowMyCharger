<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Models/RentalUser-Home.php';
$userId = $_SESSION['user_id'];


//handle from the header navigation to the html page
$view = new stdClass();
$view->pageTitle = 'Home';
$view->activePage = 'home';

//create an instance of the model and pass the user id

$rentalUserHome = new RentalUserHome($userId);
$view->bookings = $rentalUserHome->getUserActiveReservations();
$view->activeReservation= $rentalUserHome->getUserActiveReservationCount();
$view->borrowingCount = $rentalUserHome->getUserBorrowingCount();

$errorMessage = '';



require_once 'Views/home_rentaluser.phtml';
