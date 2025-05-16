<?php
// availability-checker.php - Handles Ajax requests for availability updates
// Start session to access user data
session_start();

// Include required models
require_once 'Models/browseCharger.php';
require_once 'Models/BookCharger.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is an AJAX request for availability checking
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'check_availability') {
    $chargePointId = $_POST['charge_point_id'] ?? null;
    
    if (!$chargePointId || !filter_var($chargePointId, FILTER_VALIDATE_INT)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid charge point ID'
        ]);
        exit;
    }
    
    // Initialize models
    $browse = new BrowseCharger();
    $bookingModel = new BookCharger();
    
    // Get charger availability data
    $availabilityData = $browse->getChargerAvailabilityDays($chargePointId);
    
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
        
        // Get available time slots for this day - make sure this returns the LATEST status from DB
        $dayTimeSlots = $timeSlotsByDay[$dayName];
        $availableTimesForDay = $bookingModel->getAvailableTimeSlots($chargePointId, $formattedDisplayDate, $dayTimeSlots);
        
        // Only add days that have at least one available time slot
        if (!empty($availableTimesForDay)) {
            $availableDays[] = $dayName;
            $availableTimes[$dayName] = $availableTimesForDay;
        }
    }
    
    // Send the fresh availability data as JSON
    echo json_encode([
        'success' => true, 
        'availableDays' => $availableDays,
        'availableTimes' => $availableTimes,
        'weekDates' => array_intersect_key($weekDates, array_flip($availableDays))
    ]);
    exit;
} 
// Special endpoint for concurrency checking
elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'check_slot_availability') {
    $chargePointId = $_POST['charge_point_id'] ?? null;
    $selectedDate = $_POST['selected_date'] ?? null;
    $selectedTime = $_POST['selected_time'] ?? null;
    
    if (!$chargePointId || !$selectedDate || !$selectedTime) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit;
    }
    
    // Initialize booking model
    $bookingModel = new BookCharger();
    
    // Convert date format to what the isAlreadyBooked method expects
    $dateParts = explode("-", $selectedDate);
    $formattedDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}"; // Convert to yyyy-mm-dd
    
    // Check if the slot is still available
    $isBooked = $bookingModel->isAlreadyBooked($chargePointId, $formattedDate, $selectedTime);
    
    echo json_encode([
        'success' => true,
        'available' => !$isBooked,
        'message' => $isBooked ? 'This slot is no longer available' : 'Slot is available'
    ]);
    exit;
} else {
    // If not an AJAX request or not the correct action, return an error
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}