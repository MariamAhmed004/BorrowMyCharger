<?php
// Start session to access user data
session_start();
// Require the models
require_once 'Models/Cities.php';
require_once 'Models/BrowseCharger.php';
// Create view object
$view = new stdClass();
$view->pageTitle = 'Browse Charger';
$view->activePage = 'browse-charger';
// Fetch appropriate cities
$view->cities = Cities::getCities();

$browseCharger = new BrowseCharger();
$limit = 12; // Items per page

// Get the current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Check if the request is an AJAX request for filtering charge points
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    $location = $_GET['location'] ?? '';
    $priceRange = $_GET['priceRange'] ?? '';
    $availability = $_GET['availability'] ?? '';
    
    $chargePoints = $browseCharger->getFilteredChargers($location, $priceRange, $availability, $page, $limit);
    $totalChargers = $browseCharger->getTotalFilteredChargers($location, $priceRange, $availability);
    $totalPages = ceil($totalChargers / $limit);
    
    // Generate HTML for charge points
    $html = '';
    if (!empty($chargePoints)) {
        foreach ($chargePoints as $chargePoint) {
            $html .= '<div class="charge-point">';
            $html .= '<img src="' . htmlspecialchars($chargePoint['chargePointPictureUrl'] ?? '') . '" alt="Charge Point Image">';
            $html .= '<div>';
            $html .= '<h5><strong>Charge Point</strong></h5>';
            $html .= '<p>' . htmlspecialchars('Block ' . ($chargePoint['block'] ?? '') . ', Road ' . ($chargePoint['road'] ?? '') . ', House ' . ($chargePoint['houseNumber'] ?? '') . ', ' . ($chargePoint['streetName'] ?? '') . ', Postcode ' . ($chargePoint['postcode'] ?? '')) . '</p>';
            $html .= '<p>' . htmlspecialchars($chargePoint['cityName'] ?? '') . '</p>';
            $html .= '<p>' . htmlspecialchars(number_format((float)($chargePoint['pricePerKwh'] ?? 0), 3)) . ' BHD per kWh</p>';
            $html .= '<p>Availability: ' . htmlspecialchars($chargePoint['availabilityStatusTitle'] ?? '') . '</p>';
            $html .= '<a href="#" class="book-btn" data-role="' . ($_SESSION['role'] ?? 'Guest') . '" data-id="' . htmlspecialchars($chargePoint['chargePointId'] ?? '') . '">';
            $html .= '<button class="btn btn-success">Book</button>';
            $html .= '</a>';
            $html .= '</div>';
            $html .= '</div>';
        }
    } else {
        $html .= '<p>No charge points available.</p>';
    }
    
    // Add pagination
    $html .= '<div class="pagination-container">';
    if ($totalPages > 1) {
        $html .= '<div class="pagination">';
        
        // Get the query string for the pagination links
        $queryParams = $_GET;
        unset($queryParams['page']); // Remove page from the existing query parameters
        $queryString = http_build_query($queryParams);
        $queryString = $queryString ? $queryString . '&' : '';
        
        // Previous button
        if ($page > 1) {
            $html .= '<a href="?'. $queryString . 'page=' . ($page - 1) . '" class="page-link" data-page="' . ($page - 1) . '">Previous</a>';
        }
        
        // Page numbers
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $activeClass = ($i == $page) ? 'active' : '';
            $html .= '<a href="?' . $queryString . 'page=' . $i . '" class="page-link ' . $activeClass . '" data-page="' . $i . '">' . $i . '</a>';
        }
        
        // Next button
        if ($page < $totalPages) {
            $html .= '<a href="?' . $queryString . 'page=' . ($page + 1) . '" class="page-link" data-page="' . ($page + 1) . '">Next</a>';
        }
        
        $html .= '</div>';
    }
    $html .= '</div>';
    
    echo $html;
    exit; // Exit to prevent loading the full view
}

// Fetch charge points for the current page
$view->chargePoints = $browseCharger->getChargers($page, $limit);
$view->availabilityStatus = $browseCharger->getAvailabilityStatus();

// Get total number of charge points and calculate total pages
$totalChargers = $browseCharger->getTotalChargers();
$view->totalPages = ceil($totalChargers / $limit);
$view->currentPage = $page;

// Load the view
require_once 'Views/browse-charger.phtml';