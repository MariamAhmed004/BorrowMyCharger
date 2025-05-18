<?php
require_once('Models/chargePointManagement.php');
require_once('Models/Cities.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if the session variable is not set or if the role is not 'Admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
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
function getUploadConfig() {
    // Check if we're in Docker (production)
    $isDocker = file_exists('/.dockerenv');
    
    // Check if we're on Render hosting
    $isRender = !empty(getenv('RENDER'));
    
    // Check if this is a production environment
    $isProduction = $isDocker || $isRender;
    
    if ($isProduction) {
        // We're in production (Docker/Render) - removed the echo statement
        return [
            'upload_dir' => '/var/www/html/uploads/charge_points/',
            'web_path' => 'uploads/charge_points/',
            'permissions' => 0775,
            'mode' => 'PRODUCTION'
        ];
    } else {
        // We're in local development - removed the echo statement
        return [
            'upload_dir' => __DIR__ . '/uploads/charge_points/',
            'web_path' => 'uploads/charge_points/',
            'permissions' => 0755,
            'mode' => 'LOCAL DEVELOPMENT'
        ];
    }
}
// Get the homeowner ID from the query string
$homeownerId = intval($_GET['user_id']);
$view->homeownerId = $homeownerId;

$chargePointModel = new chargePointManagement();
$availabilityStatuses = $chargePointModel->getAvailabilityStatuses();
$config = getUploadConfig();
// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   // Process uploaded image if any
    $pictureUrl = 'images/chargePoint1.jpg'; // Default image
    
    if (isset($_FILES['charge_point_picture']) && $_FILES['charge_point_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $config['upload_dir'];
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, $config['permissions'], true)) {
                $view->message = 'Failed to create upload directory';
                $view->messageType = 'error';
                require('Views/adminAddChargePoint.phtml');
                exit;
            }
            
            // Set ownership only in production/Docker environment
            if (function_exists('chown') && (file_exists('/.dockerenv') || getenv('RENDER'))) {
                @chown($uploadDir, 'www-data');
                @chgrp($uploadDir, 'www-data');
            }
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['charge_point_picture']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        // Additional validation
        $fileType = pathinfo($uploadFile, PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
        
        if (in_array(strtolower($fileType), $allowedTypes)) {
            // Check file size (limit to 5MB)
            if ($_FILES['charge_point_picture']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['charge_point_picture']['tmp_name'], $uploadFile)) {
                    $pictureUrl = $config['web_path'] . $fileName;
                    @chmod($uploadFile, 0644);
                } else {
                    error_log('Upload failed: ' . (error_get_last()['message'] ?? 'Unknown error'));
                    $view->message = 'Failed to upload image file';
                    $view->messageType = 'error';
                }
            } else {
                $view->message = 'File size too large. Maximum allowed size is 5MB.';
                $view->messageType = 'error';
            }
        } else {
            $view->message = 'Invalid file type. Only JPG, PNG, JPEG, and GIF files are allowed.';
            $view->messageType = 'error';
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