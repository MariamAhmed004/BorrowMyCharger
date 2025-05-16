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

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Invalid charge point ID';
    $_SESSION['messageType'] = 'error';
    header('Location: adminChargePointsController.php');
    exit;
}

$chargePointId = (int)$_GET['id'];
$chargePointModel = new chargePointManagement();

// Handle AJAX deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $result = $chargePointModel->deleteChargePoint($chargePointId);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete charge point']);
    }
    exit;
}

// Non-AJAX fallback - should normally not reach this code
$result = $chargePointModel->deleteChargePoint($chargePointId);

if ($result) {
    $_SESSION['message'] = 'Charge point deleted successfully';
    $_SESSION['messageType'] = 'success';
} else {
    $_SESSION['message'] = 'Failed to delete charge point';
    $_SESSION['messageType'] = 'error';
}

header('Location: charge-point-management.php');
exit;
?>