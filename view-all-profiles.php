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

// Prepare view data
$view = new stdClass();
$view->pageTitle = 'View All Profiles';
$view->activePage = 'view-all-profiles';

// Fetch data for the view
$view->profiles = $profilesModel->getAllProfiles();
$view->uniqueNames = $profilesModel->getUniqueNames();
$view->accountStatuses = $profilesModel->getAccountStatuses();
$view->roles = $profilesModel->getRoles();

// Load the view
require_once 'Views/view-all-profiles.phtml';
