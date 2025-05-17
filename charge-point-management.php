<?php
require_once('Models/chargePointManagement.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
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

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = isset($_GET['itemsPerPage']) ? intval($_GET['itemsPerPage']) : 10;

// Validate items per page
$allowedItemsPerPage = [10, 25, 50, 100];
if (!in_array($itemsPerPage, $allowedItemsPerPage)) {
    $itemsPerPage = 10;
}

$chargePointModel = new chargePointManagement();

// Get total count for pagination
$totalChargePoints = $chargePointModel->getChargePointCount();

// Calculate pagination values
$totalPages = ceil($totalChargePoints / $itemsPerPage);
$offset = ($page - 1) * $itemsPerPage;

// Ensure we don't go beyond available pages
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $itemsPerPage;
}

// Get paginated charge points
$view->chargePoints = $chargePointModel->getPaginatedChargePoints($offset, $itemsPerPage);

// Set pagination view variables
$view->currentPage = $page;
$view->totalPages = $totalPages;
$view->itemsPerPage = $itemsPerPage;
$view->totalRecords = $totalChargePoints;
$view->startRecord = $totalChargePoints > 0 ? $offset + 1 : 0;
$view->endRecord = min($offset + $itemsPerPage, $totalChargePoints);

// Get homeowners without charge points for the modal
$view->homeownersWithoutChargePoints = $chargePointModel->getHomeOwnersWithoutChargePoints();

require('Views/charge-point-management.phtml');
?>