<?php
require_once 'Models/chargePoint1.php';

class ChargePointController {
    private $_chargePointModel;

    public function __construct() {
        $this->_chargePointModel = new ChargePoint();
    }
    
        private function getUploadConfig() {
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
                'upload_dir' => __DIR__ . '/var/www/html/uploads/charge_points/',
                'web_path' => 'uploads/charge_points/',
                'permissions' => 0755
            ];
        }
    }

    public function indexAction() {
        // Check if user is logged in
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php');
            exit();
        }

        // Get user's charge points
        $chargePoints = $this->_chargePointModel->getUserChargePoints($_SESSION['user_id']);
        
        // Get cities for dropdown
        $cities = $this->_chargePointModel->getCities();

        // Require the view with extracted variables
        require 'Views/my-charge-point.phtml';
    }

    public function getCityDetailsAction() {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        // Validate charge point ID
        if (!isset($_GET['charge_point_id'])) {
            echo json_encode(['error' => 'Invalid charge point ID']);
            exit();
        }

        // Fetch user's charge points to ensure ownership
        $chargePoints = $this->_chargePointModel->getUserChargePoints($_SESSION['user_id']);
        
        // Check if the charge point belongs to the user
        $requestedChargePoint = null;
        foreach ($chargePoints as $point) {
            if ($point['charge_point_id'] == $_GET['charge_point_id']) {
                $requestedChargePoint = $point;
                break;
            }
        }

        if (!$requestedChargePoint) {
            echo json_encode(['error' => 'Charge point not found']);
            exit();
        }

        // Return full details of the charge point
        echo json_encode($requestedChargePoint);
        exit();
    }

    public function addChargePointAction() {
        header('Content-Type: application/json');
        
        // Validate input
        $errors = $this->validateChargePointInput($_POST);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit();
        }

        // Handle file upload for charge point picture
        $pictureUrl = $this->uploadChargePointPicture();
        if ($pictureUrl === false) {
            echo json_encode(['success' => false, 'errors' => ['picture' => 'Failed to upload picture']]);
            exit();
        }

        // Prepare data
        $data = [
            'postcode' => $_POST['postcode'],
            'latitude' => $_POST['latitude'],
            'longitude' => $_POST['longitude'],
            'streetName' => $_POST['streetName'],
            'city_id' => $_POST['city_id'],
            'house_number' => $_POST['house_number'],
            'road' => $_POST['road'],
            'block' => $_POST['block'],
            'price_per_kwh' => $_POST['price_per_kwh'],
            'charge_point_picture_url' => $pictureUrl
        ];

        // Add charge point
        session_start();
        $result = $this->_chargePointModel->addChargePoint($_SESSION['user_id'], $data);

        if ($result) {
            echo json_encode(['success' => true, 'charge_point_id' => $result]);
        } else {
            echo json_encode(['success' => false, 'errors' => ['general' => 'Failed to add charge point']]);
        }
        exit();
    }

    public function updateChargePointAction() {
        header('Content-Type: application/json');
        
        // Validate input
        $errors = $this->validateChargePointInput($_POST);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit();
        }

        // Handle file upload for charge point picture
        $pictureUrl = $this->uploadChargePointPicture();
        if ($pictureUrl === false) {
            echo json_encode(['success' => false, 'errors' => ['picture' => 'Failed to upload picture']]);
            exit();
        }

        // Prepare data
        $data = [
            'postcode' => $_POST['postcode'],
            'latitude' => $_POST['latitude'],
            'longitude' => $_POST['longitude'],
            'streetName' => $_POST['streetName'],
            'city_id' => $_POST['city_id'],
            'house_number' => $_POST['house_number'],
            'road' => $_POST['road'],
            'block' => $_POST['block'],
            'price_per_kwh' => $_POST['price_per_kwh'],
            'charge_point_picture_url' => $pictureUrl
        ];

        // Update charge point
        $result = $this->_chargePointModel->updateChargePoint($_POST['charge_point_id'], $data);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'errors' => ['general' => 'Failed to update charge point']]);
        }
        exit();
    }

    public function deleteChargePointAction() {
        header('Content-Type: application/json');
        
        if (!isset($_POST['charge_point_id'])) {
            echo json_encode(['success' => false, 'errors' => ['general' => 'Invalid charge point']]);
            exit();
        }

        $result = $this->_chargePointModel->deleteChargePoint($_POST['charge_point_id']);

        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'errors' => ['general' => 'Failed to delete charge point']]);
        }
        exit();
    }

    private function validateChargePointInput($data) {
        $errors = [];

        // Validate required fields
        $requiredFields = [
            'postcode', 'latitude', 'longitude', 'streetName', 'city_id', 
            'house_number', 'road', 'block', 'price_per_kwh'
        ];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        // Validate numeric fields
        $numericFields = ['latitude', 'longitude', 'house_number', 'road', 'block', 'price_per_kwh'];
        foreach ($numericFields as $field) {
            if (!empty($data[$field]) && !is_numeric($data[$field])) {
                $errors[$field] = ucfirst($field) . ' must be a number';
            }
        }

        // Validate price
        if (!empty($data['price_per_kwh']) && ($data['price_per_kwh'] <= 0 || $data['price_per_kwh'] > 1000)) {
            $errors['price_per_kwh'] = 'Price must be between 0 and 1000';
        }

        return $errors;
    }

      private function uploadChargePointPicture() {
        // Check if file was uploaded
        if (!isset($_FILES['charge_point_picture']) || $_FILES['charge_point_picture']['error'] !== UPLOAD_ERR_OK) {
            // If no new file, return the existing URL if it exists
            return isset($_POST['existing_picture_url']) ? $_POST['existing_picture_url'] : '';
        }

        $file = $_FILES['charge_point_picture'];
        
        // Enhanced file validation
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Check file type
        if (!in_array($file['type'], $allowedTypes)) {
            error_log('Invalid file type: ' . $file['type']);
            return false;
        }
        
        // Check file extension
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log('Invalid file extension: ' . $fileExtension);
            return false;
        }
        
        // Check file size (limit to 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            error_log('File too large: ' . $file['size'] . ' bytes');
            return false;
        }

        // Get configuration based on environment
        $config = $this->getUploadConfig();
        $uploadDir = $config['upload_dir'];
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, $config['permissions'], true)) {
                error_log('Failed to create upload directory: ' . $uploadDir);
                return false;
            }
            
            // Set ownership only in production/Docker environment
            if (function_exists('chown') && (file_exists('/.dockerenv') || getenv('RENDER'))) {
                @chown($uploadDir, 'www-data');
                @chgrp($uploadDir, 'www-data');
            }
        }

        // Generate unique filename
        $fileName = uniqid() . '_' . time() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $fileName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Set proper permissions on the uploaded file
            @chmod($uploadPath, 0644);
            
            // Return the web-accessible path (relative path for database storage)
            return $config['web_path'] . $fileName;
        } else {
            // Log the error for debugging
            $lastError = error_get_last();
            error_log('Failed to move uploaded file: ' . ($lastError['message'] ?? 'Unknown error'));
            error_log('Source: ' . $file['tmp_name'] . ', Destination: ' . $uploadPath);
            return false;
        }
    }
}