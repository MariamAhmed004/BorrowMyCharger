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

$view = new stdClass();
$view->pageTitle = 'Add Charge Point';
$view->activePage = 'charge-point-management';
$error = null;
$success = null;
// Fetch appropriate cities
$view->cities = Cities::getCities();

// Check if user_id is provided in the query string
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    $_SESSION['message'] = 'No homeowner was selected.';
    $_SESSION['messageType'] = 'error';
    header('Location: adminManageChargePoint.php');
    exit;
}

// Get the homeowner ID from the query string
$homeownerId = intval($_GET['user_id']);
$view->homeownerId = $homeownerId;

$chargePointModel = new chargePointManagement();
$availabilityStatuses = $chargePointModel->getAvailabilityStatuses();
// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Process uploaded image if any
    $pictureUrl = 'images/chargePoint1.jpg'; // Default image
    
    if (isset($_FILES['charge_point_picture']) && $_FILES['charge_point_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/charge_points/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['charge_point_picture']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['charge_point_picture']['tmp_name'], $uploadFile)) {
            $pictureUrl = $uploadFile;
        }
    }
    
    // Prepare data for charge point
    $chargePointData = [
        'price_per_kwh' => $_POST['price_per_kwh'],
        'charge_point_picture_url' => $pictureUrl,
        'postcode' => $_POST['postcode'],
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude'],
        'streetName' => $_POST['streetName'],
        'city_id' => $_POST['city_id'],
        'house_number' => $_POST['house_number'],
        'road' => $_POST['road'],
        'block' => $_POST['block']
    ];
    
    try {
     // Add the charge point
        $chargePointId = $chargePointModel->addChargePoint($homeownerId, $chargePointData);
     // Process availability times
        if (isset($_POST['availability_times']) && is_array($_POST['availability_times'])) {
            foreach ($_POST['availability_times'] as $dayId => $timeString) {
                $times = explode(',', $timeString);
                $times = array_unique(array_filter(array_map('trim', $times))); // Remove duplicates and trim
                // Update availability times
                $chargePointModel->updateAvailabilityTimes($dayId, $times, $chargePointId);
            }
        }

        $_SESSION['message'] = 'Charge point added successfully.';
        $_SESSION['messageType'] = 'success';
        header('Location: charge-point-management.php');
        exit;
        
    } catch (Exception $e) {
        $view->message = 'Error: ' . $e->getMessage();
        $view->messageType = 'error';
    }
}

require('Views/adminAddChargePoint.phtml');
?>