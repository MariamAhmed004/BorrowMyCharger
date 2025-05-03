<?php
session_start();
require_once('Models/myChargePoint.php');

$view = new stdClass();
$view->pageTitle = 'My Charger';
$view->activePage = 'my-charge-point';
$view->message = null;
$view->messageType = null;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$myChargePointModel = new MyChargePoint();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    $action = $_POST['action'] ?? '';
    
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
    
    echo json_encode($response);
    exit();
}

$view->cities = $myChargePointModel->getCities();
$view->chargePoint = $myChargePointModel->getUserChargePoint();
require_once('Views/my-charge-point.phtml');

function handleAddChargePoint($model) {
    try {
        $requiredFields = ['home', 'road', 'block', 'city', 'cost', 'streetName', 'postcode', 'latitude', 'longitude'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                return ['success' => false, 'message' => 'All fields are required'];
            }
        }

        $road = intval($_POST['road']);
        $block = intval($_POST['block']);
        $cost = floatval($_POST['cost']);
        $cityId = intval($_POST['city']);
        
        // Validate latitude and longitude
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return ['success' => false, 'message' => 'Invalid latitude or longitude values'];
        }

        if ($road <= 0 || $block <= 0 || $cost <= 0 || $cityId <= 0) {
            return ['success' => false, 'message' => 'Invalid numeric values'];
        }
        
        $postcode = trim($_POST['postcode']);
        if (!preg_match('/^[a-zA-Z0-9\s-]+$/', $postcode)) {
            return ['success' => false, 'message' => 'Invalid postcode format'];
        }
        
        $streetName = trim($_POST['streetName']);
        if (!preg_match('/^[a-zA-Z0-9\s,\'-]+$/', $streetName)) {
            return ['success' => false, 'message' => 'Invalid street name format'];
        }
        
        if (!isset($_FILES['imageUpload']) || $_FILES['imageUpload']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Image upload is required'];
        }

        $availabilityDays = [];
        if (isset($_POST['days']) && is_array($_POST['days'])) {
            foreach ($_POST['days'] as $day) {
                $times = isset($_POST['times'][$day]) && is_array($_POST['times'][$day]) 
                    ? $_POST['times'][$day] 
                    : [];
                
                $availabilityDays[$day] = $times;
            }
        }

        $imagePath = $model->uploadImage($_FILES['imageUpload']);
        if ($imagePath === false) {
            return ['success' => false, 'message' => 'Image upload failed'];
        }

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
            'availability_status_id' => 1,
            'availability_days' => $availabilityDays
        ];

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

function handleUpdateChargePoint($model) {
    try {
        if (!isset($_POST['chargePointId']) || empty($_POST['chargePointId'])) {
            return ['success' => false, 'message' => 'Charge point ID is required'];
        }

        $requiredFields = ['home', 'road', 'block', 'city', 'cost', 'addressId', 'streetName', 'postcode', 'latitude', 'longitude'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                return ['success' => false, 'message' => 'All fields are required'];
            }
        }

        $road = intval($_POST['road']);
        $block = intval($_POST['block']);
        $cost = floatval($_POST['cost']);
        $cityId = intval($_POST['city']);
        $chargePointId = intval($_POST['chargePointId']);
        $addressId = intval($_POST['addressId']);
        
        // Validate latitude and longitude
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return ['success' => false, 'message' => 'Invalid latitude or longitude values'];
        }

        if ($road <= 0 || $block <= 0 || $cost <= 0 || $cityId <= 0 || $chargePointId <= 0 || $addressId <= 0) {
            return ['success' => false, 'message' => 'Invalid numeric values'];
        }
        
        $postcode = trim($_POST['postcode']);
        if (!preg_match('/^[a-zA-Z0-9\s-]+$/', $postcode)) {
            return ['success' => false, 'message' => 'Invalid postcode format'];
        }
        
        $streetName = trim($_POST['streetName']);
        if (!preg_match('/^[a-zA-Z0-9\s,\'-]+$/', $streetName)) {
            return ['success' => false, 'message' => 'Invalid street name format'];
        }

        $availabilityDays = [];
        if (isset($_POST['days']) && is_array($_POST['days'])) {
            foreach ($_POST['days'] as $day) {
                $times = isset($_POST['times'][$day]) && is_array($_POST['times'][$day]) 
                    ? $_POST['times'][$day] 
                    : [];
                
                $availabilityDays[$day] = $times;
            }
        }

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
            'availability_status_id' => 2,
            'availability_days' => $availabilityDays
        ];

        $imagePath = null;
        if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $model->uploadImage($_FILES['imageUpload']);
            if ($imagePath === false) {
                return ['success' => false, 'message' => 'Image upload failed'];
            }
        }

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

function handleDeleteChargePoint($model) {
    try {
        // Detailed logging for debugging
        error_log("=== DELETE CHARGE POINT REQUEST ===");
        error_log("Raw POST data: " . print_r($_POST, true));
        error_log("User ID from session: " . $_SESSION['user_id']);
        
        // Check if chargePointId exists in request
        if (!isset($_POST['chargePointId']) || empty($_POST['chargePointId'])) {
            error_log("Missing chargePointId in request");
            return ['success' => false, 'message' => 'Charge point ID is required'];
        }

        $chargePointId = $_POST['chargePointId'];
        error_log("Processing deletion for charge point ID: " . $chargePointId);
        
        // Get all user charge points for debugging
        $userChargePoint = $model->getUserChargePoint();
        error_log("User charge point data: " . print_r($userChargePoint, true));
        
        // If user has no charge points
        if (!$userChargePoint) {
            error_log("User doesn't have any charge points");
            return ['success' => false, 'message' => 'You don\'t have any charge points to delete'];
        }
        
        // Compare IDs (convert both to strings for safe comparison)
        $userChargePointId = (string)($userChargePoint['charge_point_id'] ?? '');
        $requestedId = (string)$chargePointId;
        
        error_log("Comparing user's charge point ID: '$userChargePointId' with requested ID: '$requestedId'");
        error_log("Types - user's ID: " . gettype($userChargePointId) . ", requested ID: " . gettype($requestedId));
        
        if ($userChargePointId !== $requestedId) {
            error_log("Authorization failed - IDs don't match: '{$userChargePointId}' !== '{$requestedId}'");
            return ['success' => false, 'message' => 'You are not authorized to delete this charge point'];
        }
        
        error_log("Authorization check passed - proceeding with deletion");

        // Attempt to delete the charge point
        $success = $model->deleteChargePoint($chargePointId);
        error_log("Delete operation result: " . ($success ? 'success' : 'failure'));

        if ($success) {
            return [
                'success' => true, 
                'message' => 'Charge point deleted successfully'
            ];
        } else {
            error_log('Database operation failed for charge point deletion');
            return ['success' => false, 'message' => 'Database operation failed. Please try again later.'];
        }
        
    } catch (Exception $e) {
        error_log("Exception in handleDeleteChargePoint: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
    }
}