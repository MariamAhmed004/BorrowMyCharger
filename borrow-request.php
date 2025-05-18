<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if the session variable is not set or if the role is not 'HomeOwner'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HomeOwner') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
}
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or return error
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // AJAX request
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    } else {
        // Regular request
        header('Location: login.php');
        exit;
    }
}

// Include the model
require_once 'Models/BorrowRequestModel.php';
$borrowRequestModel = new BorrowRequestModel();

// Get the homeowner ID from session
$homeownerId = $_SESSION['user_id'];

// Default pagination values
$limit = 10; // Number of requests per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Determine the type of request and handle accordingly
$requestType = 'view'; // Default to view

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        // Check if this is an AJAX request
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $requestType = 'update_status';
        }
    }
} else if (isset($_GET['action']) && $_GET['action'] === 'get_requests') {
    $requestType = 'get_requests';
}

// Handle the appropriate request type
switch ($requestType) {
    case 'update_status':
        // This handles the AJAX approve/reject requests
        header('Content-Type: application/json');
        
        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
        $action = $_POST['action'];
        
        // Verify the booking belongs to this homeowner
        if ($borrowRequestModel->isBookingOwnedByHomeowner($bookingId, $homeownerId)) {
            if ($action === 'approve') {
                $success = $borrowRequestModel->updateBookingStatus($bookingId, 2); // 2 = Approved
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Booking request approved successfully!' : 'Failed to approve booking request.'
                ]);
            } elseif ($action === 'reject') {
                $success = $borrowRequestModel->updateBookingStatus($bookingId, 3); // 3 = Rejected
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Booking request rejected successfully!' : 'Failed to reject booking request.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action specified.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid booking request.'
            ]);
        }
        exit;
        
    case 'get_requests':
        // This handles the AJAX polling for new booking requests
        header('Content-Type: application/json');
        
        try {
            // Get all booking requests for this homeowner
            $bookingRequests = $borrowRequestModel->getBookingRequestsByHomeownerId($homeownerId, $limit, $offset);
            $totalRequests = count($borrowRequestModel->getBookingRequestsByHomeownerId($homeownerId, PHP_INT_MAX, 0));
            $totalPages = ceil($totalRequests / $limit);
            
            // Return the data as JSON
            echo json_encode([
                'success' => true,
                'bookingRequests' => $bookingRequests,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching booking requests: ' . $e->getMessage()
            ]);
        }
        exit;
        
    default:
        // This handles the regular page view (default case)
        $view = new stdClass();
        $view->pageTitle = 'Booking Requests';
        $view->activePage = 'borrow-request';
        
        // Handle approve/reject action if form submitted through regular POST
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
        
        // Get all booking requests for this homeowner for initial page load
        $view->bookingRequests = $borrowRequestModel->getBookingRequestsByHomeownerId($homeownerId, $limit, $offset);
        $totalRequests = count($borrowRequestModel->getBookingRequestsByHomeownerId($homeownerId, PHP_INT_MAX, 0));
        $view->totalPages = ceil($totalRequests / $limit);
        $view->currentPage = $page;
        
        // Load the view
        require_once 'Views/borrow-request.phtml';
        break;
}