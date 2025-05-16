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

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';


// Fetch appropriate cities
$view->cities = Cities::getCities();

$browseCharger = new BrowseCharger();
$limit = 8; // Items per page

// Get the current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Check if the request is an AJAX request for filtering charge points
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    // Get filter parameters
    $location = isset($_GET['location']) ? trim($_GET['location']) : '';
    $priceRange = isset($_GET['priceRange']) ? trim($_GET['priceRange']) : '';
    $availability = isset($_GET['availability']) ? trim($_GET['availability']) : '';
    $locationQuery = isset($_GET['locationQuery']) ? trim($_GET['locationQuery']) : '';
    $availabilityQuery = isset($_GET['availabilityQuery']) ? trim($_GET['availabilityQuery']) : '';
    
    // Get filtered charge points
    $chargePoints = $browseCharger->getFilteredChargers(
        $location, 
        $priceRange, 
        $availability, 
        $page, 
        $limit, 
        $locationQuery, 
        $availabilityQuery
    );
    
    // Get total count for pagination
    $totalChargers = $browseCharger->getTotalFilteredChargers(
        $location, 
        $priceRange, 
        $availability, 
        $locationQuery, 
        $availabilityQuery
    );
    
    $totalPages = ceil($totalChargers / $limit);
    
    // Generate HTML for charge points
    $html = '';
    if (!empty($chargePoints)) {
        foreach ($chargePoints as $chargePoint) {
            $html .= '<div class="charge-point">';
            $html .= '<div class="charge-point-image">';
            $html .= '<img src="' . htmlspecialchars($chargePoint['chargePointPictureUrl'] ?? '') . '" alt="Charge Point Image">';
   $html .= '<span class="status-badge ' . strtolower(str_replace(' ', '-', $chargePoint['availabilityStatusTitle'] ?? '')) . '" data-last-status="' . htmlspecialchars($chargePoint['availabilityStatusId'] ?? '') . '">';
$html .= htmlspecialchars($chargePoint['availabilityStatusTitle'] ?? '');
$html .= '</span>';
            $html .= '</div>';
            
            $html .= '<div class="charge-point-details">';
            $html .= '<h5 class="charge-point-title">Charging Station</h5>';
            $html .= '<div class="location">';
            $html .= '<i class="bi bi-geo-alt-fill"></i>';
            $html .= '<p>' . htmlspecialchars('Block ' . ($chargePoint['block'] ?? '') . ', Road ' . ($chargePoint['road'] ?? '') . ', House ' . ($chargePoint['houseNumber'] ?? '') . ', ' . ($chargePoint['streetName'] ?? '') . ', Postcode ' . ($chargePoint['postcode'] ?? '')) . '</p>';
            $html .= '</div>';
            $html .= '<p class="city"><i class="bi bi-building"></i> ' . htmlspecialchars($chargePoint['cityName'] ?? '') . '</p>';
            $html .= '<p class="price"><i class="bi bi-currency-exchange"></i> ' . htmlspecialchars(number_format((float)($chargePoint['pricePerKwh'] ?? 0), 3)) . ' BHD per kWh</p>';
            $html .= '<a href="#" class="book-btn" data-role="' . ($_SESSION['role'] ?? 'Guest') . '" data-id="' . htmlspecialchars($chargePoint['chargePointId'] ?? '') . '">';
            $html .= '<button class="btn btn-primary">Book Now</button>';
            $html .= '</a>';
            $html .= '</div>';
            $html .= '</div>';
        }
    } else {
        $html .= '<div class="no-results">';
        $html .= '<i class="bi bi-exclamation-circle"></i>';
        $html .= '<p>No charge points available matching your search criteria.</p>';
        $html .= '</div>';
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
            $html .= '<a href="?' . $queryString . 'page=' . ($page - 1) . '" class="page-link" data-page="' . ($page - 1) . '">';
            $html .= '<i class="bi bi-chevron-left"></i> Previous';
            $html .= '</a>';
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
            $html .= '<a href="?' . $queryString . 'page=' . ($page + 1) . '" class="page-link" data-page="' . ($page + 1) . '">';
            $html .= 'Next <i class="bi bi-chevron-right"></i>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
    }
    $html .= '</div>';
    
    echo $html;
    exit; // Exit to prevent loading the full view
}

// For the initial page load, check if any filter parameters are set
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$priceRange = isset($_GET['priceRange']) ? trim($_GET['priceRange']) : '';
$availability = isset($_GET['availability']) ? trim($_GET['availability']) : '';
$locationQuery = isset($_GET['locationQuery']) ? trim($_GET['locationQuery']) : '';
$availabilityQuery = isset($_GET['availabilityQuery']) ? trim($_GET['availabilityQuery']) : '';

// If any filters are set, use them to fetch charge points
if (!empty($location) || !empty($priceRange) || !empty($availability) || !empty($locationQuery) || !empty($availabilityQuery)) {
    $view->chargePoints = $browseCharger->getFilteredChargers(
        $location, 
        $priceRange, 
        $availability, 
        $page, 
        $limit, 
        $locationQuery, 
        $availabilityQuery
    );
    
    $totalChargers = $browseCharger->getTotalFilteredChargers(
        $location, 
        $priceRange, 
        $availability, 
        $locationQuery, 
        $availabilityQuery
    );
} else {
    // Fetch all charge points for the current page
    $view->chargePoints = $browseCharger->getChargers($page, $limit);
    $totalChargers = $browseCharger->getTotalChargers();
}

$view->availabilityStatus = $browseCharger->getAvailabilityStatus();

// Calculate total pages for pagination
$view->totalPages = ceil($totalChargers / $limit);
$view->currentPage = $page;

// Load the view
require_once 'Views/browse-charger.phtml';