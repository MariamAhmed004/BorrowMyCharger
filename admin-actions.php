<?php
// Enable error reporting for debugging
ini_set('display_errors', 1); // Set to 1 for debugging, 0 for production
error_reporting(E_ALL);
require_once('Models/chargePoint1.php');

// Add additional logging for debugging
function debug_log($message) {
    error_log('[AJAX DEBUG] ' . $message);
}

debug_log('Request received: ' . $_SERVER['REQUEST_METHOD']);
debug_log('GET data: ' . print_r($_GET, true));
debug_log('POST data: ' . print_r($_POST, true));

// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $chargePointModel = new MyChargePointModel();
    
    // Handle different actions
    $action = $_GET['action'] ?? ($_POST['action'] ?? '');
    debug_log('Action: ' . $action);
    
    // Set proper content type for JSON response
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'getHomeOwnersWithoutChargePoints':
            debug_log('Fetching homeowners without charge points');
            $homeowners = $chargePointModel->getHomeOwnersWithoutChargePoints();
            debug_log('Found ' . count($homeowners) . ' homeowners');
            echo json_encode([
                'success' => true,
                'homeowners' => $homeowners ? $homeowners : []
            ]);
            break;
case 'addChargePointForUser':
    // Retrieve user ID from POST data
    $userId = isset($_POST['user_ID']) ? intval($_POST['user_ID']) : null;
    debug_log('Adding charge point for user ID: ' . $userId);
    
    if (!$userId || $userId <= 0) {
        debug_log('Error: User ID is missing or invalid: ' . $userId);
        echo json_encode([
            'success' => false, 
            'message' => 'Valid User ID is required',
            'debug' => ['received_post' => $_POST]
        ]);
        exit();
    }
    
    // Check if the controller file exists
    $controllerPath = 'charge-point-management.php';
    if (!file_exists($controllerPath)) {
        debug_log('Controller file not found at: ' . $controllerPath);
        $controllerPath = 'charge-point-management.php'; // Fallback to current directory
    }
    
    // Include the controller file
    debug_log('Including controller file: ' . $controllerPath);
    require_once($controllerPath);
    
    // Check if the function exists
    if (!function_exists('handleAddChargePoint')) {
        debug_log('Error: handleAddChargePoint function not found');
        echo json_encode([
            'success' => false, 
            'message' => 'Controller function not found. Check server configuration.'
        ]);
        exit();
    }
    
    // Cast user ID to integer to ensure it's treated as a number
    $userId = intval($userId);
    debug_log('Calling handleAddChargePoint with user ID (int): ' . $userId);
    $response = handleAddChargePoint($chargePointModel, $userId);
    debug_log('Response: ' . print_r($response, true));
    echo json_encode($response);
    break;
            
        case 'getChargePointDetails':
            $chargePointId = $_GET['id'] ?? null;
            debug_log('Fetching charge point details for ID: ' . $chargePointId);
            
            if (!$chargePointId) {
                debug_log('Error: Charge point ID is missing');
                echo json_encode(['success' => false, 'message' => 'Charge point ID is required']);
                exit();
            }
            
            $chargePoint = $chargePointModel->getChargePointById($chargePointId);
            if ($chargePoint) {
                debug_log('Charge point found: ' . print_r($chargePoint, true));
                echo json_encode(['success' => true, 'chargePoint' => $chargePoint]);
            } else {
                debug_log('Error: Charge point not found');
                echo json_encode(['success' => false, 'message' => 'Charge point not found']);
            }
            break;
            
        case 'update':
            $chargePointId = $_POST['charge_point_id'] ?? null;
            debug_log('Updating charge point with ID: ' . $chargePointId);
            
            if (!$chargePointId) {
                debug_log('Error: Charge point ID is missing for update');
                echo json_encode(['success' => false, 'message' => 'Charge point ID is required for updates']);
                exit();
            }
            
            // Check if the controller file exists
            $controllerPath = 'charge-point-management.php';
            if (!file_exists($controllerPath)) {
                debug_log('Controller file not found at: ' . $controllerPath);
                $controllerPath = 'charge-point-management.php'; // Fallback to current directory
            }
            
            // Include the controller file
            debug_log('Including controller file: ' . $controllerPath);
            require_once($controllerPath);
            
            // Check if the function exists
            if (!function_exists('handleUpdateChargePoint')) {
                debug_log('Error: handleUpdateChargePoint function not found');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Controller function not found. Check server configuration.'
                ]);
                exit();
            }
            
            debug_log('Calling handleUpdateChargePoint with ID: ' . $chargePointId);
            $response = handleUpdateChargePoint($chargePointModel, $chargePointId);
            debug_log('Response: ' . print_r($response, true));
            echo json_encode($response);
            break;
            
        default:
            debug_log('Error: Invalid action: ' . $action);
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'received_data' => [
                    'get' => $_GET,
                    'post' => $_POST
                ]
            ]);
    }
} catch (Exception $e) {
    // Log the error
    debug_log('Error in admin-actions.php: ' . $e->getMessage());
    debug_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return a proper JSON error response
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'trace' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getTraceAsString() : null
    ]);
}
?>