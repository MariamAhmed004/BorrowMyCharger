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
        function getUploadConfig() {
            // Detect if we're in a Docker/production environment
            $isDocker = file_exists('/.dockerenv') || getenv('DOCKER') === 'true';
            $isProduction = getenv('APP_ENV') === 'production' || !empty(getenv('RENDER'));
            
            if ($isDocker || $isProduction) {
                // Production/Docker environment
                return [
                    'upload_dir' => '/var/www/html/uploads/charge_points/',
                    'web_path' => 'uploads/charge_points/',
                    'permissions' => 0775
                ];
            } else {
                // Local development environment
                return [
                    'upload_dir' => __DIR__ . '/uploads/charge_points/',
                    'web_path' => 'uploads/charge_points/',
                    'permissions' => 0755
                ];
            }
        }
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
  $price = htmlspecialchars($_POST['price_per_kwh'], ENT_QUOTES, 'UTF-8');
$availabilityStatus = htmlspecialchars($_POST['availability_status_id'], ENT_QUOTES, 'UTF-8');
$streetName = htmlspecialchars($_POST['streetName'], ENT_QUOTES, 'UTF-8');
$latitude = htmlspecialchars($_POST['latitude'], ENT_QUOTES, 'UTF-8');
$longitude = htmlspecialchars($_POST['longitude'], ENT_QUOTES, 'UTF-8');
$postcode = htmlspecialchars($_POST['postcode'], ENT_QUOTES, 'UTF-8');
$cityId = htmlspecialchars($_POST['city_id'], ENT_QUOTES, 'UTF-8');
$houseNumber = htmlspecialchars($_POST['house_number'], ENT_QUOTES, 'UTF-8');
$road = htmlspecialchars($_POST['road'], ENT_QUOTES, 'UTF-8');
$block = htmlspecialchars($_POST['block'], ENT_QUOTES, 'UTF-8');
$chargePointAddressId = htmlspecialchars($_POST['charge_point_address_id'], ENT_QUOTES, 'UTF-8');
    // Validate required fields
    if (!$price || !$availabilityStatus || !$streetName || !$latitude || !$longitude || !$postcode || !$cityId || !$houseNumber || !$road || !$block || !$chargePointAddressId) {
        $error = 'Please fill in all required fields correctly';
        //
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    } else {
      // Handle file upload if a new image was provided
        $picturePath = null;
        if (!empty($_FILES['charge_point_picture']['name'])) {
            $config = getUploadConfig();
            $uploadDir = $config['upload_dir'];
            $fileName = uniqid() . '_' . time() . '_' . basename($_FILES['charge_point_picture']['name']);
            $targetFilePath = $uploadDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow only certain file formats
            $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
            if (in_array(strtolower($fileType), $allowedTypes)) {
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    if (!mkdir($uploadDir, $config['permissions'], true)) {
                        $error = 'Failed to create upload directory';
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $error]);
                            exit;
                        }
                    }
                    
                    // Set ownership only in production/Docker environment
                    if (function_exists('chown') && (file_exists('/.dockerenv') || getenv('RENDER'))) {
                        @chown($uploadDir, 'www-data');
                        @chgrp($uploadDir, 'www-data');
                    }
                }
                
                // Check file size (limit to 5MB)
                if ($_FILES['charge_point_picture']['size'] > 5 * 1024 * 1024) {
                    $error = 'File size must be less than 5MB';
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $error]);
                        exit;
                    }
                } else {
                    // Check if the temp file exists and is readable
                    if (is_uploaded_file($_FILES['charge_point_picture']['tmp_name'])) {
                        if (move_uploaded_file($_FILES['charge_point_picture']['tmp_name'], $targetFilePath)) {
                            // Store relative path for database (works both locally and in production)
                            $picturePath = $config['web_path'] . $fileName;
                            
                            // Set proper permissions on the uploaded file
                            @chmod($targetFilePath, 0644);
                        } else {
                            $error = 'Failed to upload image. Error: ' . (error_get_last()['message'] ?? 'Unknown error');
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => $error]);
                                exit;
                            }
                        }
                    } else {
                        $error = 'Invalid file upload or file too large';
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $error]);
                            exit;
                        }
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