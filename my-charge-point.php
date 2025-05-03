<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('Models/myChargePoint.php');

// Create a view object
$view = new stdClass();
$view->pageTitle = 'My Charger';
$view->activePage = 'my-charge-point';
$view->message = null;
$view->messageType = null;

// Make sure the user is logged in and has an ID
if (isset($_SESSION['user_id'])) {
  

    // Instantiate the model
    $chargePointModel = new myChargePoint(); 

    // Call the method
    $chargePointData = $chargePointModel->getUserChargePoint();

    if ($chargePointData && isset($chargePointData['charge_point_id'])) {
        $chargePointId = $chargePointData['charge_point_id'];
        // You can now use $chargePointId as needed
    } else {
        // Handle case where no charge point data is returned
        $chargePointId = null;
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Create model instance
$myChargePointModel = new MyChargePoint();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the AJAX request
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    // Get the action from POST data
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'add':
            $response = handleAddChargePoint($myChargePointModel);
            break;
            
        case 'update':
            $response = handleUpdateChargePoint($myChargePointModel);
            break;
            
        case 'delete':
            $response = handleDeleteChargePoint($myChargePointModel);
            break;
            
        case 'getCities':
            $cities = $myChargePointModel->getCities();
            $response = ['success' => true, 'cities' => $cities];
            break;
            
        case 'getChargePoint':
            $chargePoint = $myChargePointModel->getUserChargePoint();
            $response = [
                'success' => ($chargePoint !== false),
                'chargePoint' => $chargePoint
            ];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    // Send JSON response
    echo json_encode($response);
    exit();
}

// Fetch cities for the form
$view->cities = $myChargePointModel->getCities();

// Check if user has a charge point
$view->chargePoint = $myChargePointModel->getUserChargePoint();

// Load the view
require_once('Views/my-charge-point.phtml');

/**
 * Handle adding a new charge point
 * @param MyChargePoint $model
 * @return array Response data
 */
function handleAddChargePoint($model) {
    try {
        // Validate required inputs
        $requiredFields = ['home', 'road', 'block', 'city', 'cost', 'streetName', 'postcode'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                return ['success' => false, 'message' => 'All fields are required'];
            }
        }
        
        // Validate numeric inputs
        $road = intval($_POST['road']);
        $block = intval($_POST['block']);
        $cost = floatval($_POST['cost']);
        $cityId = intval($_POST['city']);
        
        if ($road <= 0 || $block <= 0 || $cost <= 0 || $cityId <= 0) {
            return ['success' => false, 'message' => 'Invalid numeric values'];
        }
        
        // Validate postcode format (alphanumeric with possible spaces or dashes)
        $postcode = trim($_POST['postcode']);
        if (!preg_match('/^[a-zA-Z0-9\s-]+$/', $postcode)) {
            return ['success' => false, 'message' => 'Invalid postcode format'];
        }
        
        // Validate street name (letters, numbers, spaces, commas, and apostrophes)
        $streetName = trim($_POST['streetName']);
        if (!preg_match('/^[a-zA-Z0-9\s,\'-]+$/', $streetName)) {
            return ['success' => false, 'message' => 'Invalid street name format'];
        }
        
        // Check if image was uploaded
        if (!isset($_FILES['imageUpload']) || $_FILES['imageUpload']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Image upload is required'];
        }
        
        // Process availability data
        $availabilityDays = [];
        if (isset($_POST['days']) && is_array($_POST['days'])) {
            foreach ($_POST['days'] as $day) {
                $times = isset($_POST['times'][$day]) && is_array($_POST['times'][$day]) 
                    ? $_POST['times'][$day] 
                    : [];
                
                $availabilityDays[$day] = $times;
            }
        }
        
        // Handle image upload
        $imagePath = $model->uploadImage($_FILES['imageUpload']);
        if ($imagePath === false) {
            return ['success' => false, 'message' => 'Image upload failed'];
        }
        
        // Process coordinates
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
        
        // Prepare data for model
        $data = [
            'house_number' => intval($_POST['home']),
            'road' => $road,
            'block' => $block,
            'city_id' => $cityId,
            'streetName' => $streetName,
            'postcode' => $postcode,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'price_per_kwh' => $cost,
            'availability_status_id' => 1, //available by default
            'availability_days' => $availabilityDays
        ];
        
        // Add charge point
        $chargePointId = $model->addChargePoint($data, $imagePath);
        
        if ($chargePointId !== false) {
            return [
                'success' => true, 
                'message' => 'Charge point added successfully',
                'chargePointId' => $chargePointId
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to add charge point'];
        }
        
    } catch (Exception $e) {
        error_log("Error in handleAddChargePoint: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred'];
    }
}

/**
 * Handle updating an existing charge point
 * @param MyChargePoint $model
 * @return array Response data
 */
function handleUpdateChargePoint($model) {
    try {
        // Validate charge point ID
        if (!isset($_POST['chargePointId']) || empty($_POST['chargePointId'])) {
            return ['success' => false, 'message' => 'Charge point ID is required'];
        }
        
        // Validate required inputs
        $requiredFields = ['home', 'road', 'block', 'city', 'cost', 'addressId', 'streetName', 'postcode'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                return ['success' => false, 'message' => 'All fields are required'];
            }
        }
        
        // Validate numeric inputs
        $road = intval($_POST['road']);
        $block = intval($_POST['block']);
        $cost = floatval($_POST['cost']);
        $cityId = intval($_POST['city']);
        $chargePointId = intval($_POST['chargePointId']);
        $addressId = intval($_POST['addressId']);
        
        if ($road <= 0 || $block <= 0 || $cost <= 0 || $cityId <= 0 || $chargePointId <= 0 || $addressId <= 0) {
            return ['success' => false, 'message' => 'Invalid numeric values'];
        }
        
        // Validate postcode format (alphanumeric with possible spaces or dashes)
        $postcode = trim($_POST['postcode']);
        if (!preg_match('/^[a-zA-Z0-9\s-]+$/', $postcode)) {
            return ['success' => false, 'message' => 'Invalid postcode format'];
        }
        
        // Validate street name (letters, numbers, spaces, commas, and apostrophes)
        $streetName = trim($_POST['streetName']);
        if (!preg_match('/^[a-zA-Z0-9\s,\'-]+$/', $streetName)) {
            return ['success' => false, 'message' => 'Invalid street name format'];
        }
        
        // Process availability data
        $availabilityDays = [];
        if (isset($_POST['days']) && is_array($_POST['days'])) {
            foreach ($_POST['days'] as $day) {
                $times = isset($_POST['times'][$day]) && is_array($_POST['times'][$day]) 
                    ? $_POST['times'][$day] 
                    : [];
                
                $availabilityDays[$day] = $times;
            }
        }
        
        // Process coordinates
        $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
        $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
        
        // Prepare data for model
        $data = [
            'charge_point_id' => $chargePointId,
            'charge_point_address_id' => $addressId,
            'house_number' => intval($_POST['home']),
            'road' => $road,
            'block' => $block,
            'city_id' => $cityId,
            'streetName' => $streetName,
            'postcode' => $postcode,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'price_per_kwh' => $cost,
            'availability_status_id' => 2, // Scheduled hours
            'availability_days' => $availabilityDays
        ];
        
        // Handle image upload if a new one was provided
        $imagePath = null;
        if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $model->uploadImage($_FILES['imageUpload']);
            if ($imagePath === false) {
                return ['success' => false, 'message' => 'Image upload failed'];
            }
        }
        
        // Update charge point
        $success = $model->updateChargePoint($data, $imagePath);
        
        if ($success) {
            return [
                'success' => true, 
                'message' => 'Charge point updated successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update charge point'];
        }
        
    } catch (Exception $e) {
        error_log("Error in handleUpdateChargePoint: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred'];
    }
}

/**
 * Handle deleting a charge point
 * @param MyChargePoint $model
 * @return array Response data
 */
function handleDeleteChargePoint($model) {
    try {
        // Validate charge point ID
        if (!isset($_POST['chargePointId']) || empty($_POST['chargePointId'])) {
            return ['success' => false, 'message' => 'Charge point ID is required'];
        }
        
        $chargePointId = intval($_POST['chargePointId']);
        
        if ($chargePointId <= 0) {
            return ['success' => false, 'message' => 'Invalid charge point ID'];
        }
        
        // Delete charge point
        $success = $model->deleteChargePoint($chargePointId);
        
        if ($success) {
            return [
                'success' => true, 
                'message' => 'Charge point deleted successfully'
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to delete charge point'];
        }
        
    } catch (Exception $e) {
        error_log("Error in handleDeleteChargePoint: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred'];
    }
}