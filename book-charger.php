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
$bookingModel = new BookCharger();

$id = $_GET['id'] ?? $_POST['charge_point_id'] ?? null;

if ($id) {
    // Ensure the ID is a valid integer
    if (!filter_var($id, FILTER_VALIDATE_INT)) {
        echo "Invalid charger ID.";
        exit; // Stop further processing if ID is invalid
    }

    // Use the method to get charger details by ID
    $chargerDetails = $browse->getChargerById($id);

    if ($chargerDetails) {
        // Pass the charger details to the view
        $view->chargerDetails = $chargerDetails;
        
        // Get detailed availability data with proper day-time mapping
        $availabilityData = $browse->getChargerAvailabilityDays($id);
        
        // Current date for calculating week dates
        $currentDate = new DateTime();
        $weekDates = [];
        $availableTimes = [];
        
        // Generate dates for the upcoming week
        for ($i = 0; $i < 7; $i++) {
            $date = clone $currentDate;
            $date->modify("+$i day");
            $dayName = $date->format('l'); // e.g., "Monday"
            $formattedDate = $date->format('d-m-Y'); // e.g., "12-05-2025"
            $dbFormattedDate = $date->format('Y-m-d'); // e.g., "2025-05-12" for DB comparison
            
            // Store the date in the array with the day of the week as the key
            $weekDates[$dayName] = [
                'display' => $formattedDate,
                'db_format' => $dbFormattedDate
            ];
        }
        
        // Process the availability data and get available time slots by day
        $availableDays = [];
        
        // First, organize all possible time slots by day
        $timeSlotsByDay = [];
        foreach ($availabilityData as $item) {
            $day = $item['day_of_week'];
            $time = $item['available_time'];
            
            if (!isset($timeSlotsByDay[$day])) {
                $timeSlotsByDay[$day] = [];
            }
            
            $timeSlotsByDay[$day][] = $time;
        }
        
        // Now check each time slot against bookings for the upcoming week
        foreach ($weekDates as $dayName => $dateInfo) {
            // Skip days that don't have any defined time slots
            if (!isset($timeSlotsByDay[$dayName])) {
                continue;
            }
            
            $dbDate = $dateInfo['db_format'];
            $formattedDisplayDate = $dateInfo['display'];
            
            // Get available time slots for this day
            $dayTimeSlots = $timeSlotsByDay[$dayName];
            $availableTimesForDay = $bookingModel->getAvailableTimeSlots($id, $formattedDisplayDate, $dayTimeSlots);
            
            // Only add days that have at least one available time slot
            if (!empty($availableTimesForDay)) {
                $availableDays[] = $dayName;
                $availableTimes[$dayName] = $availableTimesForDay;
            }
        }
        
        // Check if we have any days with available times
        $hasAvailability = !empty($availableDays);
        
        // Set view properties
        $view->hasAvailability = $hasAvailability;
        $view->availableDays = $availableDays;
        $view->availableTimes = $availableTimes;
        $view->weekDates = array_intersect_key($weekDates, array_flip($availableDays));
        $view->chargerPointId = $id;
        
        // Handle form submission
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $userId = $_POST['user_id'] ?? null;
            $chargePointId = $_POST['charge_point_id'] ?? null;
            $bookingDate = $_POST['selected_date'] ?? null;
            $bookingTime = $_POST['selected_time'] ?? null;

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

            if ($userId && $chargePointId && $bookingDate && $bookingTime) {
                // Add the booking using the method from BookCharger model
                $result = $bookingModel->addBooking($userId, $chargePointId, $bookingDate, $bookingTime);
                
                if ($isAjax) {
                    // Return JSON response for AJAX requests
                    header('Content-Type: application/json');
                    echo json_encode($result);
                    exit;
                } else {
                    // Handle form submission for non-AJAX requests
                    if ($result['success']) {
                        // Redirect with a success message
                        echo "<script>alert('{$result['message']}'); window.location.href='index.php';</script>";
                        exit;
                    } else {
                        // Set error message to display in the view
                        $view->errorMessage = $result['message'];
                    }
                }
            } else {
                $errorMessage = 'All fields are required.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage
                    ]);
                    exit;
                } else {
                    $view->errorMessage = $errorMessage;
                }
            }
        }
    } else {
        echo "Charger not found.";
        exit; // Stop further processing if charger not found
    }
} else {
    echo "No charger selected.";
    exit; // Stop further processing if no ID provided
}

// Load the view
require_once 'Views/book-charger.phtml';