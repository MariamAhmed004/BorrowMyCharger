<?php
// Start the session if it's not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Fetch the user ID from the session
$userId = $_SESSION['user_id'] ?? null;

// Check if user is logged in
if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get the booking ID from the request
$bookingId = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;

// Check if booking ID is provided
if (!$bookingId) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking ID is required'
    ]);
    exit;
}

// Include database connection
require_once 'Models/Database.php';

try {
    // Get database connection
    $db = Database::getInstance()->getDbConnection();

    // First verify that this booking belongs to the logged-in user
    $checkSql = "SELECT COUNT(*) FROM Pro_Booking WHERE booking_id = ? AND user_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$bookingId, $userId]);
    
    if ($checkStmt->fetchColumn() == 0) {
        // If no matching booking was found for this user
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found or access denied'
        ]);
        exit;
    }
    
    // Fetch coordinates and address information
    $sql = "SELECT 
                cpa.latitude, 
                cpa.longitude,
                cpa.house_number,
                cpa.streetName,
                c.city_name,
                cp.charge_point_id
            FROM Pro_Booking b
            JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
            JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
            JOIN Pro_City c ON cpa.city_id = c.city_id
            WHERE b.booking_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$bookingId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'Charge point coordinates not found'
        ]);
        exit;
    }
    
    // Check if coordinates are valid
    if (empty($result['latitude']) || empty($result['longitude']) ||
        !is_numeric($result['latitude']) || !is_numeric($result['longitude'])) {
        
        // Log the error
        error_log("Invalid coordinates for booking ID: $bookingId - Lat: {$result['latitude']}, Lng: {$result['longitude']}");
        
        // If no valid coordinates, try to geocode the address
        // This is a fallback mechanism - in a real implementation, you might
        // want to use a geocoding service like Google Maps API
        
        // For now, we'll just provide a default location or error
        echo json_encode([
            'success' => false,
            'message' => 'Invalid coordinates for this charge point'
        ]);
        exit;
    }
    
    // Format the address
    $address = $result['house_number'] . ' ' . $result['streetName'] . ', ' . $result['city_name'];
    
    // Return successful response with coordinates and address
    echo json_encode([
        'success' => true,
        'data' => [
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'address' => $address,
            'charge_point_id' => $result['charge_point_id']
        ]
    ]);
    
} catch (PDOException $e) {
    // Log the error
    error_log('Database error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}