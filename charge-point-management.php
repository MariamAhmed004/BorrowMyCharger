<?php
require_once('Models/chargePoint1.php');
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$view = new stdClass();
$view->pageTitle = 'My Charge Points';
$view->activePage = 'my-charge-point';
$view->message = null;
$view->messageType = null;

$chargePointModel = new MyChargePointModel();
$userId = $_SESSION['user_id'];

// Handle different actions via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid request'];
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'addChargePointForUser':

                if (isset($_POST['user_ID'])) {
                    $targetUserId = intval($_POST['user_ID']);
                    error_log("charge-point-management.php: Using passed user ID: " . $targetUserId);
                    $response = handleAddChargePoint($chargePointModel, $targetUserId);
                } else {
                    error_log("charge-point-management.php: No user_ID found in POST data");
                    $response = ['success' => false, 'message' => 'No user ID provided'];
                }
                break;
            case 'update':
                $response = handleUpdateChargePoint($chargePointModel, $userId);
                break;
            case 'delete':
                $response = handleDeleteChargePoint($chargePointModel, $userId);
                break;
            default:
                $response = ['success' => false, 'message' => 'Invalid action'];
        }
    } catch (Exception $e) {
        $response = [
            'success' => false, 
            'message' => 'An error occurred: ' . $e->getMessage()
        ];
    }

    echo json_encode($response);
    exit();
}

// Fetch cities and user's charge points for the view
$view->cities = $chargePointModel->getAllCities();
$view->chargePoints = $chargePointModel->getAllChargePoint();
$view->availabilityStatuses = $chargePointModel->getAvailabilityStatuses();

// Render the view
require_once('Views/charge-point-management.phtml');

/**
 * Process available days and times data from the form
 * @return array Processed availability data in format suitable for database operations
 */
function processAvailableDaysTimes() {
    $availabilityData = [];
    
    // Check if selected_days array exists in the POST data
    if(isset($_POST['selected_days']) && is_array($_POST['selected_days'])) {
        // For each selected day
        foreach($_POST['selected_days'] as $day) {
            // Check if there are times selected for this day
            if(isset($_POST['day_times'][$day]) && is_array($_POST['day_times'][$day])) {
                // Add this day and its times to the result array
                $availabilityData[$day] = $_POST['day_times'][$day];
            }
        }
    }
    
    return $availabilityData;
}

function handleAddChargePoint($model, $userID) {
    // Debug logging to verify the user ID being received
    error_log('handleAddChargePoint received user ID: ' . $userID);
    
    $errors = [];
    $requiredFields = [
        'streetName' => 'Street Name',
        'city_id' => 'City',
        'postcode' => 'Postcode',
        'house_number' => 'House Number',
        'road' => 'Road',
        'block' => 'Block',
        'price_per_kwh' => 'Price per kWh',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude'
    ];
    
    // Validate required fields
    foreach ($requiredFields as $field => $label) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[$field] = "$label is required";
        }
    }

    // Validate days and times
    if (!isset($_POST['selected_days']) || !is_array($_POST['selected_days']) || empty($_POST['selected_days'])) {
        $errors['availableDays'] = "At least one day must be selected";
    } else {
        // Check if each day has at least one time slot selected
        foreach ($_POST['selected_days'] as $day) {
            if (!isset($_POST['day_times'][$day]) || !is_array($_POST['day_times'][$day]) || empty($_POST['day_times'][$day])) {
                $errors['availableDays'] = "Each selected day must have at least one time slot";
                break;
            }
        }
    }

    // Image upload validation
    if (!isset($_FILES['charge_point_picture']) || $_FILES['charge_point_picture']['error'] !== UPLOAD_ERR_OK) {
        $errors['charge_point_picture'] = 'Charge point picture is required';
    }

    // Return errors if any
    if (!empty($errors)) {
        return [
            'success' => false, 
            'errors' => $errors
        ];
    }

    // Validate and sanitize inputs
    $data = [
        'streetName' => trim($_POST['streetName']),
        'postcode' => trim($_POST['postcode']),
        'latitude' => floatval($_POST['latitude']),
        'longitude' => floatval($_POST['longitude']),
        'city_id' => intval($_POST['city_id']),
        'house_number' => intval($_POST['house_number']),
        'road' => intval($_POST['road']),
        'block' => intval($_POST['block']),
        'price_per_kwh' => floatval($_POST['price_per_kwh'])
    ];

    // Process available days and times
    $availabilityData = processAvailableDaysTimes();
    $data['availability'] = $availabilityData;
    
    // Additional validation
    foreach ($data as $key => $value) {
        if ($key !== 'streetName' && $key !== 'postcode' && $key !== 'price_per_kwh' && $key !== 'availability') {
            if ($value <= 0) {
                $errors[$key] = "Invalid $key value";
            }
        }
    }

    if (!empty($errors)) {
        return [
            'success' => false, 
            'errors' => $errors
        ];
    }

    // Image upload logic
    $uploadDir = 'uploads/charge-points/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $fileName = uniqid() . '_' . basename($_FILES['charge_point_picture']['name']);
    $uploadPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['charge_point_picture']['tmp_name'], $uploadPath)) {
        return [
            'success' => false, 
            'message' => 'Failed to upload image'
        ];
    }

    // Add charge point picture URL to data
    $data['charge_point_picture_url'] = $uploadPath;

    try {
        // Verify we're using the passed user ID and not the session user ID
        error_log('Using user ID for charge point: ' . $userID);
        
        // Add charge point - IMPORTANT: make sure to use the passed $userID parameter
        $chargePointId = $model->addChargePoint($userID, $data);

        error_log('Charge point added with ID: ' . $chargePointId . ' for user ID: ' . $userID);

        if ($chargePointId) {
            // Save available days and times
            foreach ($availabilityData as $day => $times) {
                $model->saveChargePointAvailability($chargePointId, $day, $times);
            }
            
            return [
                'success' => true, 
                'message' => 'Charge point added successfully',
                'chargePointId' => $chargePointId,
                'userID' => $userID // Return the user ID in the response for debugging
            ];
        } else {
            // Remove uploaded image if charge point creation fails
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            return [
                'success' => false, 
                'message' => 'Failed to add charge point'
            ];
        }
    } catch (Exception $e) {
        // Remove uploaded image on error
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        error_log('Exception in handleAddChargePoint: ' . $e->getMessage());
        throw $e;
    }
}
/**
 * Handle update charge point action
 */
function handleUpdateChargePoint($model, $userId) {
    $errors = [];
    $requiredFields = [
        'charge_point_id' => 'Charge Point ID',
        'streetName' => 'Street Name',
        'city_id' => 'City',
        'postcode' => 'Postcode',
        'house_number' => 'House Number',
        'road' => 'Road',
        'block' => 'Block',
        'price_per_kwh' => 'Price per kWh',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude'
    ];
    
    // Validate required fields
    foreach ($requiredFields as $field => $label) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[$field] = "$label is required";
        }
    }

    // Validate days and times
    if (!isset($_POST['selected_days']) || !is_array($_POST['selected_days']) || empty($_POST['selected_days'])) {
        $errors['availableDays'] = "At least one day must be selected";
    } else {
        // Check if each day has at least one time slot selected
        foreach ($_POST['selected_days'] as $day) {
            if (!isset($_POST['day_times'][$day]) || !is_array($_POST['day_times'][$day]) || empty($_POST['day_times'][$day])) {
                $errors['availableDays'] = "Each selected day must have at least one time slot";
                break;
            }
        }
    }

    // Return errors if any
    if (!empty($errors)) {
        return [
            'success' => false, 
            'errors' => $errors
        ];
    }

    $chargePointId = intval($_POST['charge_point_id']);

    // Validate and sanitize inputs
    $data = [
        'charge_point_id' => $chargePointId,
        'streetName' => trim($_POST['streetName']),
        'postcode' => trim($_POST['postcode']),
        'latitude' => floatval($_POST['latitude']),
        'longitude' => floatval($_POST['longitude']),
        'city_id' => intval($_POST['city_id']),
        'house_number' => intval($_POST['house_number']),
        'road' => intval($_POST['road']),
        'block' => intval($_POST['block']),
        'price_per_kwh' => floatval($_POST['price_per_kwh'])
    ];

    // Process available days and times
    $availabilityData = processAvailableDaysTimes();
$data['availability'] = $availabilityData;
    // Additional validation
    foreach ($data as $key => $value) {
        if ($key !== 'charge_point_id' && $key !== 'streetName' && $key !== 'postcode' && $key !== 'price_per_kwh') {
            if ($value <= 0) {
                $errors[$key] = "Invalid $key value";
            }
        }
    }

    if (!empty($errors)) {
        return [
            'success' => false, 
            'errors' => $errors
        ];
    }

    // Handle image upload (optional)
    $uploadPath = $_POST['existing_picture_url'] ?? '';
    if (isset($_FILES['charge_point_picture']) && $_FILES['charge_point_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/charge-points/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid() . '_' . basename($_FILES['charge_point_picture']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($_FILES['charge_point_picture']['tmp_name'], $uploadPath)) {
            return [
                'success' => false, 
                'message' => 'Failed to upload image'
            ];
        }
    }

    // Add charge point picture URL to data
    if (!empty($uploadPath)) {
        $data['charge_point_picture_url'] = $uploadPath;
    }

    try {
        // Update charge point
        $success = $model->updateChargePoint($chargePointId, $data);

        if ($success) {
            // First, delete all existing availability for this charge point
            $model->deleteChargePointAvailability($chargePointId);
            
            // Then save the new availability
            foreach ($availabilityData as $day => $times) {
                $model->saveChargePointAvailability($chargePointId, $day, $times);
            }
            
            return [
                'success' => true, 
                'message' => 'Charge point updated successfully'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to update charge point'
            ];
        }
    } catch (Exception $e) {
        // If this is a new image that failed, delete it
        if ($uploadPath !== $_POST['existing_picture_url'] && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        throw $e;
    }
}

/**
 * Handle delete charge point action
 */
function handleDeleteChargePoint($model, $userId) {
    if (!isset($_POST['charge_point_id'])) {
        return [
            'success' => false,
            'message' => 'Charge point ID is required'
        ];
    }
    $chargePointId = intval($_POST['charge_point_id']);

    try {
        // First delete availability data
        $model->deleteChargePointAvailability($chargePointId);
        
        // Then delete the charge point
        $success = $model->deleteChargePoint($chargePointId);

        return [
            'success' => $success, 
            'message' => $success 
                ? 'Charge point deleted successfully' 
                : 'Failed to delete charge point'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}