<?php
// Start session to access user data
session_start();

// Set header for JSON response
header('Content-Type: application/json');

require_once 'Models/browseCharger.php';
require_once 'Models/BookCharger.php';

$browse = new BrowseCharger();
$bookingModel = new BookCharger();

// Get the charger ID from the request
$id = $_GET['id'] ?? null;

if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid charger ID'
    ]);
    exit;
}

try {
    // Get charger availability data
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
    
    // Return the availability data as JSON
    echo json_encode([
        'success' => true,
        'hasAvailability' => $hasAvailability,
        'availableDays' => $availableDays,
        'availableTimes' => $availableTimes,
        'weekDates' => array_intersect_key($weekDates, array_flip($availableDays))
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching availability: ' . $e->getMessage()
    ]);
}