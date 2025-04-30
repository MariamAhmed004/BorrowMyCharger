<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create a view object
$view = new stdClass();
$view->pageTitle = 'My Charger';
$view->activePage = 'my-charge-point';


// Load the view
require_once 'Views/my-charge-point.phtml';