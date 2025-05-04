<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include the dashboard model
require_once 'Models/AdminDashboard.php';

//require a model -> for the statistics - new instance of the model
$dashboardModel = new AdminDashboard();


// view instance for the header 
$view = new stdClass();
$view->pageTitle = 'Dashboard';
$view->activePage = 'dashboard';

//Prepare view data sending the statistics after excuting the sql statements from the model
$view->userCount = $dashboardModel->getUserCount();
$view->homeOwnerCount = $dashboardModel->getHomeOwnerCount();
$view->chargePointCount = $dashboardModel->getChargePointCount();
$view->pendingApproval = $dashboardModel->getPendingApproval();

require_once 'Views/dashboard_admin.phtml';


