<?php

// Start session
session_start();

require_once('Models/chargePoint1.php');
// Check role and navigate accordingly
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'Admin':
            header('Location: dashboard_admin.php');
            exit;
        case 'HomeOwner':
            header('Location: home_homeowner.php');
            exit;
    }
}

$chargePointModel = new MyChargePointModel();
$featuredChargePoints = $chargePointModel->getFeaturedChargePoints();
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    $minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
    $maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;

    if ($filter === "available") {
        $chargePoints = $chargePointModel->getAvailableChargePoints($minPrice, $maxPrice);
    } elseif ($filter === "unavailable") {
        $chargePoints = $chargePointModel->getUnAvailableChargePoints($minPrice, $maxPrice);
    } else {
        $chargePoints = $chargePointModel->getChargePointDetails($minPrice, $maxPrice);
    }

    header('Content-Type: application/json');
    echo json_encode($chargePoints);
    exit;
}

// Include the view
require_once 'Views/index.phtml';