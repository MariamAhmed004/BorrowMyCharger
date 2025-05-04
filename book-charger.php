<?php
// Start session to access user data
session_start();

// Handle navigation to the book charger page
$view = new stdClass();
$view->pageTitle = 'Book Charger';
$view->activePage = 'book-charger';

require_once 'Models/browseCharger.php';
require_once 'Models/BookCharger.php';


$browse = new BrowseCharger();

$id = $_GET['id'] ?? $_POST['charge_point_id'] ?? null;



if ($id) {
    
    // Debug: Check the ID being passed
    error_log("Charger ID: " . $id);
    
    // Ensure the ID is a valid integer
    if (!filter_var($id, FILTER_VALIDATE_INT)) {
        echo "Invalid charger ID.";
        exit; // Stop further processing if ID is invalid
    }
    
    // Use the method to get charger details by ID
    $chargerDetails = $browse->getChargerById($id);
    
    // Debug: Check if charger details are retrieved
    if ($chargerDetails) {
        // Pass the charger details to the view
        $view->chargerDetails = $chargerDetails;
        
        // Check if there are available days
        if (empty($chargerDetails['availableDays'])) {
            $view->hasAvailability = false;
            $view->availableDays = [];
            $view->availableTimes = [];
        } else {
            $view->hasAvailability = true;
            $view->availableDays = explode(',', $chargerDetails['availableDays']);
            
            // Get detailed availability data with proper day-time mapping
            $availabilityData = $browse->getChargerAvailability($id);
            
            // Initialize an associative array for times by day
            $view->availableTimes = [];
            
            // Populate the associative array with times for each day
            foreach ($availabilityData as $item) {
                $day = $item['day_of_week'];
                $time = $item['available_time'];
                
                if (!isset($view->availableTimes[$day])) {
                    $view->availableTimes[$day] = [];
                }
                
                $view->availableTimes[$day][] = $time;
            }

            // Filter out days with no available times
            $view->availableDays = array_filter($view->availableDays, function($day) use ($view) {
                return isset($view->availableTimes[$day]) && !empty($view->availableTimes[$day]);
            });
        }
        
        //set the id to a view property 
        $view->chargerPointId = $id;
        
    } else {
        error_log("Charger not found for ID: " . $id);
        echo "Charger not found.";
        exit; // Stop further processing if charger not found
    }
    
    //after adding the charger details poin to the page
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_POST['user_id'] ?? null;
    $chargePointId = $_POST['charge_point_id'] ?? null;
    $bookingDate = $_POST['selected_date'] ?? null;
    $bookingTime = $_POST['selected_time'] ?? null;

    if ($userId && $chargePointId && $bookingDate && $bookingTime) {
        $bookingModel = new BookCharger();
        $success = $bookingModel->addBooking($userId, $chargePointId, $bookingDate, $bookingTime);

        if ($success) {
            echo "<script>alert('Booking successfully created!'); window.location.href='user-dashboard.php';</script>";
        } else {
            echo "<script>alert('Booking failed. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('All fields are required.');</script>";
    }
}
    
    
} else {
    echo "No charger selected.";
    exit; // Stop further processing if no ID provided
}


// Load the view
require_once 'Views/book-charger.phtml';