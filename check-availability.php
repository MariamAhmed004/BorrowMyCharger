<?php
// Start session to maintain user state
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Required models
require_once 'Models/browseCharger.php';
require_once 'Models/BookCharger.php';

// Validate request
if (!isset($_GET['charge_point_id']) || !filter_var($_GET['charge_point_id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'Invalid charge point ID']);
    exit;
}

$chargePointId = $_GET['charge_point_id'];

// Initialize models
$browse = new BrowseCharger();
$bookingModel = new BookCharger();

// Get charger details and availability data
$chargerDetails = $browse->getChargerById($chargePointId);

if (!$chargerDetails) {
    echo json_encode(['success' => false, 'message' => 'Charger not found']);
    exit;
}

// Get detailed availability data with proper day-time mapping
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
    
    // Get available time slots for this day
    $dayTimeSlots = $timeSlotsByDay[$dayName];
    $availableTimesForDay = $bookingModel->getAvailableTimeSlots($chargePointId, $formattedDisplayDate, $dayTimeSlots);
    
    // Only add days that have at least one available time slot
    if (!empty($availableTimesForDay)) {
        $availableDays[] = $dayName;
        $availableTimes[$dayName] = $availableTimesForDay;
    }
}

// Check if we have any days with available times
$hasAvailability = !empty($availableDays);

// Prepare and send the response
$response = [
    'success' => true,
    'hasAvailability' => $hasAvailability,
    'availableDays' => $availableDays,
    'availableTimes' => $availableTimes,
    'weekDates' => array_intersect_key($weekDates, array_flip($availableDays))
];

echo json_encode($response);