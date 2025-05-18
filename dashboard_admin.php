<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if the session variable is not set or if the role is not 'Admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // Redirect to index.php
    header('Location: index.php');
    exit(); 
}
// Include the dashboard model
require_once 'Models/AdminDashboard.php';
$dashboardModel = new AdminDashboard();

// Prepare view instance
$view = new stdClass();
$view->pageTitle = 'dashboard';
$view->activePage = 'dashboard';

// Prepare view data
$view->userCount = $dashboardModel->getUserCount();
$view->homeOwnerCount = $dashboardModel->getHomeOwnerCount();
$view->chargePointCount = $dashboardModel->getChargePointCount();
$view->pendingApproval = $dashboardModel->getPendingApproval();

// Get more detailed stats
$view->chargePointStatusCounts = $dashboardModel->getChargePointStatusCounts();
$view->userStatusCounts = $dashboardModel->getUserStatusCounts();
$view->bookingStatusCounts = $dashboardModel->getBookingStatusCounts();
$view->userRoleCounts = $dashboardModel->getUserCountsByRole();
$view->bookingsLastSevenDays = $dashboardModel->getBookingsLastSevenDays();
$view->topChargePoints = $dashboardModel->getTopChargePoints();
$view->priceStats = $dashboardModel->getChargePointPriceStats();
$view->popularBookingDays = $dashboardModel->getPopularBookingDays();
$view->popularBookingTimes = $dashboardModel->getPopularBookingTimes();

require_once 'Views/dashboard_admin.phtml';