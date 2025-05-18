<?php
require_once('Models/chargePoint1.php');

// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the session variable is not set or if the role is not 'HomeOwner'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HomeOwner') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
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
            case 'add':
                $response = handleAddChargePoint($chargePointModel, $userId);
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
$view->chargePoints = $chargePointModel->getUserChargePoint($userId);
$view->availabilityStatuses = $chargePointModel->getAvailabilityStatuses();

// Render the view
require_once('Views/my-charge-point.phtml');


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

/**
 * Handle add charge point action
 */
function handleAddChargePoint($model, $userId) {
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
        if ($key !== 'streetName' && $key !== 'postcode' && $key !== 'price_per_kwh') {
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

    // Enhanced image upload logic
    $config = getUploadConfig();
    $pictureUrl = 'images/chargePoint1.jpg'; // Default image

    if (isset($_FILES['charge_point_picture']) && $_FILES['charge_point_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $config['upload_dir'];
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, $config['permissions'], true)) {
                return [
                    'success' => false,
                    'message' => 'Failed to create upload directory'
                ];
            }
            
            // Set ownership only in production/Docker environment
            if (function_exists('chown') && (file_exists('/.dockerenv') || getenv('RENDER'))) {
                @chown($uploadDir, 'www-data');
                @chgrp($uploadDir, 'www-data');
            }
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['charge_point_picture']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        // Additional validation
        $fileType = pathinfo($uploadPath, PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
        
        if (in_array(strtolower($fileType), $allowedTypes)) {
            // Check file size (limit to 5MB)
            if ($_FILES['charge_point_picture']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['charge_point_picture']['tmp_name'], $uploadPath)) {
                    $pictureUrl = $config['web_path'] . $fileName;
                    @chmod($uploadPath, 0644);
                } else {
                    error_log('Upload failed: ' . (error_get_last()['message'] ?? 'Unknown error'));
                    return [
                        'success' => false,
                        'message' => 'Failed to upload image file'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'File size too large. Maximum allowed size is 5MB.'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, JPEG, and GIF files are allowed.'
            ];
        }
    } elseif (isset($_FILES['charge_point_picture']) && $_FILES['charge_point_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $errorCode = $_FILES['charge_point_picture']['error'];
        $errorMessage = $uploadErrors[$errorCode] ?? 'Unknown upload error';
        
        return [
            'success' => false,
            'message' => "Upload error: {$errorMessage}"
        ];
    }

    // Add charge point picture URL to data
    $data['charge_point_picture_url'] = $pictureUrl;

    try {
        // Add charge point
        $chargePointId = $model->addChargePoint($userId, $data);

        if ($chargePointId) {
            // Save available days and times
            foreach ($availabilityData as $day => $times) {
                $model->saveChargePointAvailability($chargePointId, $day, $times);
            }
            
            return [
                'success' => true, 
                'message' => 'Charge point added successfully',
                'chargePointId' => $chargePointId
            ];
        } else {
            // Remove uploaded image if charge point creation fails
            if ($pictureUrl !== 'images/chargePoint1.jpg' && file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            return [
                'success' => false, 
                'message' => 'Failed to add charge point'
            ];
        }
    } catch (Exception $e) {
        // Remove uploaded image on error
        if ($pictureUrl !== 'images/chargePoint1.jpg' && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
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

    // Enhanced image upload logic (optional for updates)
    $config = getUploadConfig();
    $uploadPath = $_POST['existing_picture_url'] ?? '';
    
    if (isset($_FILES['charge_point_picture']) && $_FILES['charge_point_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = $config['upload_dir'];
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, $config['permissions'], true)) {
                return [
                    'success' => false,
                    'message' => 'Failed to create upload directory'
                ];
            }
            
            // Set ownership only in production/Docker environment
            if (function_exists('chown') && (file_exists('/.dockerenv') || getenv('RENDER'))) {
                @chown($uploadDir, 'www-data');
                @chgrp($uploadDir, 'www-data');
            }
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['charge_point_picture']['name']);
        $newUploadPath = $uploadDir . $fileName;
        
        // Additional validation
        $fileType = pathinfo($newUploadPath, PATHINFO_EXTENSION);
        $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
        
        if (in_array(strtolower($fileType), $allowedTypes)) {
            // Check file size (limit to 5MB)
            if ($_FILES['charge_point_picture']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['charge_point_picture']['tmp_name'], $newUploadPath)) {
                    $uploadPath = $config['web_path'] . $fileName;
                    @chmod($newUploadPath, 0644);
                } else {
                    error_log('Upload failed: ' . (error_get_last()['message'] ?? 'Unknown error'));
                    return [
                        'success' => false,
                        'message' => 'Failed to upload image file'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'File size too large. Maximum allowed size is 5MB.'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, JPEG, and GIF files are allowed.'
            ];
        }
    } elseif (isset($_FILES['charge_point_picture']) && $_FILES['charge_point_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $errorCode = $_FILES['charge_point_picture']['error'];
        $errorMessage = $uploadErrors[$errorCode] ?? 'Unknown upload error';
        
        return [
            'success' => false,
            'message' => "Upload error: {$errorMessage}"
        ];
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
        if (isset($newUploadPath) && $uploadPath !== $_POST['existing_picture_url'] && file_exists($newUploadPath)) {
            unlink($newUploadPath);
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