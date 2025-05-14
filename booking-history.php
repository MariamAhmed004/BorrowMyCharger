<?php
require_once 'Models/Database.php';
require_once 'Models/BookingHistory.php';

session_start();
$view = new stdClass();
$view->pageTitle = 'booking-history';
$view->activePage = 'booking-history';
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    $bookingHistory = new BookingHistory($userId);

    // Handle AJAX request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $status = $_POST['status'] ?? null;
        $page = $_POST['page'] ?? 1;
        $limit = 8;

        $totalBookings = $bookingHistory->getBookingCount($status);
        $totalPages = ceil($totalBookings / $limit);
        $bookings = $bookingHistory->getUserBookingHistory($status, $page, $limit);

        echo json_encode([
            'bookings' => $bookings,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
        exit;
    }
} else {
    echo json_encode(['error' => 'User ID is missing.']);
    exit;
}

// Load the view
require_once 'Views/booking-history.phtml';

