<?php
require_once('Models/chargePointManagement.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) ) {
    header('Location: login.php');
    exit;
}
$view = new stdClass();
$view->pageTitle = 'Charge Points Management';
$view->activePage = 'charge-point-management';
$view->message = null;
$view->messageType = null;
// Check if there are flash messages
if (isset($_SESSION['message'])) {
    $view->message = $_SESSION['message'];
    $view->messageType = $_SESSION['messageType'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}
$chargePointModel = new chargePointManagement();
$view->chargePoints = $chargePointModel->getAllChargePoints();

// Get homeowners without charge points for the modal
$view->homeownersWithoutChargePoints = $chargePointModel->getHomeOwnersWithoutChargePoints();

require('Views/charge-point-management.phtml');
?>