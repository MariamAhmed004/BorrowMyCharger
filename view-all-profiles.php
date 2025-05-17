<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the Profiles model
require_once 'Models/allProfiles.php';

// Create instance of Profiles model
$profilesModel = new Profiles();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get request data
    $data = $_POST;
    $action = $data['action'] ?? '';
    $userId = $data['userId'] ?? null;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    $result = false;
    $message = '';
    
    switch ($action) {
        case 'suspend':
            $result = $profilesModel->suspendUser($userId);
            $message = 'User suspended successfully';
            break;
            
        case 'unsuspend':
            $result = $profilesModel->unsuspendUser($userId);
            $message = 'User unsuspended successfully';
            break;
            
        case 'approve':
            $result = $profilesModel->approveUser($userId);
            $message = 'User approved successfully';
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    if ($result) {
        // Get the updated status to return to client
        $newStatus = $profilesModel->getUserStatus($userId);
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'status' => $newStatus
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
    exit;
}

// Handle DELETE request for user deletion
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    header('Content-Type: application/json');
    parse_str(file_get_contents("php://input"), $_DELETE);
    $userId = $_DELETE['id'] ?? null;
    
    if ($userId) {
        $result = $profilesModel->deleteUser($userId);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
    exit;
}

// Pagination settings
$recordsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure page is at least 1

// Get filter parameters and log them for debugging
$nameFilter = isset($_GET['name']) ? trim($_GET['name']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Debug logging (can be removed in production)
error_log("Filter values: name='$nameFilter', role='$roleFilter', status='$statusFilter'");

// Prepare view data
$view = new stdClass();
$view->pageTitle = 'View All Profiles';
$view->activePage = 'view-all-profiles';

// Fetch all profiles to get total count with filters
$allProfiles = $profilesModel->getAllProfiles($nameFilter, $roleFilter, $statusFilter);
$totalRecords = count($allProfiles);
$totalPages = max(1, ceil($totalRecords / $recordsPerPage));

// Ensure current page is valid
$currentPage = min($currentPage, $totalPages); 

// Calculate offset
$offset = ($currentPage - 1) * $recordsPerPage;

// Get profiles for current page
$view->profiles = array_slice($allProfiles, $offset, $recordsPerPage);

// Pagination data
$view->currentPage = $currentPage;
$view->totalPages = $totalPages;
$view->totalRecords = $totalRecords;
$view->recordsPerPage = $recordsPerPage;
$view->startRecord = $totalRecords > 0 ? $offset + 1 : 0;
$view->endRecord = min($offset + $recordsPerPage, $totalRecords);

// Also add the filter values to the view for displaying and maintaining state
$view->nameFilter = $nameFilter;
$view->roleFilter = $roleFilter; 
$view->statusFilter = $statusFilter;

// Fetch other data for the view
$view->uniqueNames = $profilesModel->getUniqueNames();
$view->accountStatuses = $profilesModel->getAccountStatuses();
$view->roles = $profilesModel->getRoles();

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// For AJAX requests, only load the table content without layout
if ($isAjax) {
    require_once 'Views/view-all-profiles.phtml';
    exit;
}

// For regular requests, load the full page
require_once 'Views/view-all-profiles.phtml';
?>