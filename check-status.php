<?php
require_once 'Models/BrowseCharger.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if IDs parameter is set
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    echo json_encode(['error' => 'No charge point IDs provided']);
    exit;
}

// Parse the comma-separated IDs
$chargePointIds = explode(',', $_GET['ids']);

// Validate IDs
$validIds = [];
foreach ($chargePointIds as $id) {
    // Ensure the ID is a valid integer
    if (is_numeric($id) && intval($id) > 0) {
        $validIds[] = intval($id);
    }
}

// If no valid IDs, return error
if (empty($validIds)) {
    echo json_encode(['error' => 'No valid charge point IDs provided']);
    exit;
}

// Get status information for the provided charge points
$browseCharger = new BrowseCharger();
$statusData = $browseCharger->getChargePointStatuses($validIds);

// Return the status data as JSON
echo json_encode($statusData);
exit;