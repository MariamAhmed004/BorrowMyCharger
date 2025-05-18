<?php
require_once 'Models/Database.php';
require_once 'Models/BookingHistory.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'RentalUser') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
}
$view = new stdClass();
$view->pageTitle = 'booking-history';
$view->activePage = 'booking-history';

$userId = $_SESSION['user_id'] ?? null;



// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!$userId) {
        echo json_encode(['error' => 'User not logged in. Please log in to view bookings.']);
        exit;
    }
    
    $bookingHistory = new BookingHistory($userId);
    $status = isset($_POST['status']) && $_POST['status'] !== '' ? $_POST['status'] : null;
    $page = $_POST['page'] ?? 1;
    $limit = 8;
    $checkUpdates = isset($_POST['check_updates']) && $_POST['check_updates'] === 'true';
    $lastCheckTime = $_POST['last_check_time'] ?? null;
    
    try {
        // Check if this is just an update check
        if ($checkUpdates && $lastCheckTime) {
            $updatedBookings = $bookingHistory->getRecentlyUpdatedBookings($lastCheckTime, $status);
            echo json_encode([
                'updatedBookings' => $updatedBookings,
                'hasUpdates' => count($updatedBookings) > 0,
                'currentTime' => date('Y-m-d H:i:s')
            ]);
            exit;
        }
        
        // Regular booking list fetch
        $totalBookings = $bookingHistory->getBookingCount($status);
        $totalPages = ceil($totalBookings / $limit);
        $bookings = $bookingHistory->getUserBookingHistory($status, $page, $limit);
        
        echo json_encode([
            'bookings' => $bookings,
            'totalPages' => $totalPages,
            'currentPage' => (int)$page,
            'currentTime' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// If not AJAX but direct visit
if (!$userId) {
    header('Location: login.php');
    exit;
}

// Load the view
$bookingHistory = new BookingHistory($userId);
require_once 'Views/booking-history.phtml';