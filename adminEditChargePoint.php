<?php
require_once('Models/chargePointManagement.php');
require_once('Models/Cities.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$view = new stdClass(); // Initialize view object
// Initialize variables for view
$error = null;
$success = null;
$chargePoint = null;
$availabilityDays = [];
$availabilityTimes = [];
$availabilityStatuses = [];
// Fetch appropriate cities
$view->cities = Cities::getCities();
$chargePointModel = new chargePointManagement();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Invalid charge point ID';
    $_SESSION['messageType'] = 'error';
    header('Location: charge-point-management.php');
    exit;
}

$chargePointId = (int)$_GET['id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Validate form data
    $price = filter_input(INPUT_POST, 'price_per_kwh', FILTER_VALIDATE_FLOAT);
    $availabilityStatus = filter_input(INPUT_POST, 'availability_status_id', FILTER_VALIDATE_INT);
    $streetName = filter_input(INPUT_POST, 'streetName', FILTER_SANITIZE_STRING);
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
    $postcode = filter_input(INPUT_POST, 'postcode', FILTER_SANITIZE_STRING);
    $cityId = filter_input(INPUT_POST, 'city_id', FILTER_VALIDATE_INT);
    $houseNumber = filter_input(INPUT_POST, 'house_number', FILTER_VALIDATE_INT);
    $road = filter_input(INPUT_POST, 'road', FILTER_VALIDATE_INT);
    $block = filter_input(INPUT_POST, 'block', FILTER_VALIDATE_INT);
    $chargePointAddressId = filter_input(INPUT_POST, 'charge_point_address_id', FILTER_VALIDATE_INT);
    
    // Validate required fields
    if (!$price || !$availabilityStatus || !$streetName || !$latitude || !$longitude || !$postcode || !$cityId || !$houseNumber || !$road || !$block || !$chargePointAddressId) {
        $error = 'Please fill in all required fields correctly';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    } else {
        // Handle file upload if a new image was provided
        $picturePath = null;
        if (!empty($_FILES['charge_point_picture']['name'])) {
            $uploadDir = 'uploads/charge_points/';
            $fileName = uniqid() . '_' . time() . '_' . basename($_FILES['charge_point_picture']['name']);
            $targetFilePath = $uploadDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow only certain file formats
            $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
            if (in_array(strtolower($fileType), $allowedTypes)) {
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['charge_point_picture']['tmp_name'], $targetFilePath)) {
                    $picturePath = $targetFilePath;
                } else {
                    $error = 'Failed to upload image';
                    
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $error]);
                        exit;
                    }
                }
            } else {
                $error = 'Only JPG, JPEG, PNG, and GIF files are allowed';
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error]);
                    exit;
                }
            }
        }
        
        // Begin database operations
        try {
            // Update charge point
            $chargePointModel->updateChargePoint($chargePointId, $price, $availabilityStatus, $picturePath);
            
            // Update address
            $chargePointModel->updateChargePointAddress(
                $chargePointAddressId,
                $streetName,
                $latitude,
                $longitude,
                $postcode,
                $cityId,
                $houseNumber,
                $road,
                $block
            );
            
            // Update availability times
            if (isset($_POST['availability_times']) && is_array($_POST['availability_times'])) {
                foreach ($_POST['availability_times'] as $dayId => $timeString) {
                    $times = explode(',', $timeString);
                    $times = array_unique(array_filter(array_map('trim', $times))); // Remove duplicates and trim
                    // Pass the charge point ID as the third parameter for new days
                    $chargePointModel->updateAvailabilityTimes($dayId, $times, $chargePointId);
                }
            }
            
            $success = 'Charge point updated successfully';
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $success]);
                exit;
            } else {
                $_SESSION['message'] = $success;
                $_SESSION['messageType'] = 'success';
                header('Location: charge-point-management.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Error updating charge point: ' . $e->getMessage();
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error]);
                exit;
            }
        }
    }
}

// Get charge point data
$chargePoint = $chargePointModel->getChargePointById($chargePointId);

if (!$chargePoint) {
    $_SESSION['message'] = 'Charge point not found';
    $_SESSION['messageType'] = 'error';
    header('Location: charge-point-management.php');
    exit;
}

// Get availability days, times, and statuses
$availabilityDays = $chargePointModel->getAvailabilityDays($chargePointId);
$dayIds = array_column($availabilityDays, 'availability_day_id');
$availabilityTimes = empty($dayIds) ? [] : $chargePointModel->getAvailabilityTimes($dayIds);
$availabilityStatuses = $chargePointModel->getAvailabilityStatuses();

require('Views/adminEditChargePoint.phtml');