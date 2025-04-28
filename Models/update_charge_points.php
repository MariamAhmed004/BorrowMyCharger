<?php
require_once 'Models/BrowseCharger.php';

$location = isset($_GET['location']) ? $_GET['location'] : '';
$priceRange = isset($_GET['priceRange']) ? $_GET['priceRange'] : '';
$availability = isset($_GET['availability']) ? $_GET['availability'] : '';

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
?>
