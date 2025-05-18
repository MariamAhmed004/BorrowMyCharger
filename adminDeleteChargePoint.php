
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to avoid breaking JSON
ini_set('log_errors', 1);

session_start();
// Check if the session variable is not set or if the role is not 'Admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
}
require_once 'Models/chargePointManagement.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Get charge point ID from request
    $chargePointId = null;
    if (isset($_GET['id'])) {
        $chargePointId = (int)$_GET['id'];
    } elseif (isset($_POST['id'])) {
        $chargePointId = (int)$_POST['id'];
    }

    // Validate charge point ID
    if (!$chargePointId || $chargePointId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid charge point ID']);
        exit;
    }

    // Create instance of charge point management
    $chargePointModel = new chargePointManagement();

    // Handle AJAX request (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $result = $chargePointModel->deleteChargePoint($chargePointId);
            
            if ($result) {
                http_response_code(200);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Charge point deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to delete charge point'
                ]);
            }
        } catch (Exception $e) {
            error_log("Error deleting charge point: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Non-AJAX fallback (GET request)
    try {
        $result = $chargePointModel->deleteChargePoint($chargePointId);
        
        if ($result) {
            $_SESSION['message'] = 'Charge point deleted successfully';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to delete charge point';
            $_SESSION['messageType'] = 'error';
        }
    } catch (Exception $e) {
        error_log("Error deleting charge point: " . $e->getMessage());
        $_SESSION['message'] = 'An error occurred while deleting the charge point';
        $_SESSION['messageType'] = 'error';
    }

    header('Location: charge-point-management.php');
    exit;

} catch (Exception $e) {
    error_log("Fatal error in deleteChargePoint.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'A fatal error occurred'
    ]);
    exit;
}