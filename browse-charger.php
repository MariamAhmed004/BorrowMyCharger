<?php
// Start session to access user data
session_start();

// Require the model
require_once 'Models/Cities.php';

// Create view object
$view = new stdClass();
$view->pageTitle = 'Browse Charger';
$view->activePage = 'browse-charger';

// Check if user is signed in and fetch their country ID if available
$countryId = null;
if (isset($_SESSION['user_id'])) {
    // Assuming country_id is stored in the session when the user registers
    $countryId = $_SESSION['country_id']; 
}

// Fetch appropriate cities
$view->cities = Cities::getCities($countryId);

// Load the view
require_once 'Views/browse-charger.phtml';