<?php
// Start the session only if it is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require the necessary models
require_once 'Models/Cities.php';
require_once 'Models/ChargePoints.php';
require_once 'Models/Database.php';

// Create a view object
$view = new stdClass();
$view->pageTitle = 'Browse Charger';
$view->activePage = 'browse-charger';

// Check if the user is signed in
$countryId = null;
if (isset($_SESSION['user_id'])) {
    // Fetch the user's country_id from the database
    $userId = $_SESSION['user_id'];
    $connection = Database::getInstance()->getDbConnection();
    
    $query = "SELECT country_id FROM Pro_User WHERE user_id = :user_id";
    $statement = $connection->prepare($query);
    $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $statement->execute();

    $user = $statement->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $countryId = $user['country_id']; // Store the user's country_id
    }
}

// Fetch cities based on the user's country
$view->cities = Cities::getCities($countryId);

// Fetch charge points based on the selected city (if provided)
$cityId = isset($_POST['locationSelect']) ? $_POST['locationSelect'] : null; // Assuming the dropdown is submitted via POST
$view->chargePoints = ChargePoints::getChargePoints($cityId);

// Load the view
require_once 'Views/browse-charger.phtml';