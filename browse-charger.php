<?php
// Start session to access user data
session_start();

// Require the models
require_once 'Models/Cities.php';
require_once 'Models/BrowseCharger.php'; // <-- Add this line!

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

 

// Check if the request is an AJAX request for filtering charge points
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    $location = $_GET['location'] ?? '';
    $priceRange = $_GET['priceRange'] ?? '';
    $availability = $_GET['availability'] ?? '';

    $browseCharger = new BrowseCharger();

    $chargePoints = $browseCharger->getFilteredChargers($location, $priceRange, $availability);

    if (!empty($chargePoints)) {
        foreach ($chargePoints as $chargePoint) {
            echo '<div class="charge-point">';
            echo '<img src="' . htmlspecialchars($chargePoint['chargePointPictureUrl']) . '" alt="Charge Point Image">';
            echo '<div>';
            echo '<h5><strong>Charge Point</strong></h5>';
            echo '<p>' . htmlspecialchars('Block ' . $chargePoint['block'] . ', Road ' . $chargePoint['road'] . ', House ' . $chargePoint['houseNumber'] . ', ' . $chargePoint['streetName'] . ', Postcode ' . $chargePoint['postcode']) . '</p>';
            echo '<p>' . htmlspecialchars($chargePoint['cityName'] . ', ' . $chargePoint['countryName']) . '</p>';
            echo '<p>' . htmlspecialchars(number_format($chargePoint['pricePerKwh'], 3)) . ' BHD per kWh</p>';
            echo '<p>Availability: ' . htmlspecialchars($chargePoint['availabilityStatusTitle']) . '</p>';
            echo '<button class="btn btn-success">View</button>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No charge points available.</p>';
    }
    exit; // Exit to prevent loading the full view
}

// Fetch all charge points
$browseCharger = new BrowseCharger();
$view->chargePoints = $browseCharger->getChargers();
$view->availabilityStatus = $browseCharger->getAvailabilityStatus();
// Load the view
require_once 'Views/browse-charger.phtml';
