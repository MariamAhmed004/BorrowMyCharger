<?php
require_once 'systemReportsController.php';

// Initialize the view object
$view = new stdClass();

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id']) ) {
    // Redirect to login page if not logged in or not an admin (assuming role_id 1 is admin)
    header('Location: login.php?redirect=system-reports.php');
    exit();
}

// Create controller
$systemReportsController = new SystemReportsController($view);

// Check if this is an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Process AJAX request
    $response = $systemReportsController->processAjaxRequest($_POST);
    
    // Send JSON response with proper headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode($response);
    exit();
}

// Load initial data for the page
$systemReportsController->loadInitialData();

// Load the view
require_once 'Views/system-reports.phtml';