<?php
require_once 'Models/Database.php';
class BorrowRequestModel {
    protected $_dbHandle;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->_dbHandle = $database->getDbConnection();
    }
    
    /**
     * Get all booking requests for a specific homeowner
     * 
     * @param int $homeownerId The ID of the homeowner
     * @return array Array of booking requests
     */
   public function getBookingRequestsByHomeownerId($homeownerId, $limit, $offset) {
    $sql = "SELECT b.booking_id, b.booking_date, b.booking_time, b.booking_status_id, 
                   bs.booking_status_title, u.username, u.first_name, u.last_name, 
                   cp.charge_point_id, cp.price_per_kwh, cpa.postcode, cpa.streetName
            FROM Pro_Booking b
            JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
            JOIN Pro_User u ON b.user_id = u.user_id
            JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
            JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
            WHERE cp.user_id = :homeownerId
            ORDER BY b.booking_date DESC, b.booking_time DESC
            LIMIT :limit OFFSET :offset";
    
    $statement = $this->_dbHandle->prepare($sql);
    $statement->bindParam(':homeownerId', $homeownerId, PDO::PARAM_INT);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Update the booking status (approve or reject)
     * 
     * @param int $bookingId The ID of the booking
     * @param int $statusId The new status ID (2 = Approved, 3 = Rejected)
     * @return bool True if update successful, false otherwise
     */
public function updateBookingStatus($bookingId, $statusId) {
    try {
        // Start a transaction for atomicity
        $this->_dbHandle->beginTransaction();
        
        // First, get the charge_point_id from the booking
        $getBookingSql = "SELECT charge_point_id FROM Pro_Booking WHERE booking_id = :bookingId";
        $getBookingStmt = $this->_dbHandle->prepare($getBookingSql);
        $getBookingStmt->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $getBookingStmt->execute();
        
        $booking = $getBookingStmt->fetch(PDO::FETCH_ASSOC);
        $chargePointId = $booking['charge_point_id'];
        
        // Update the booking status
        $updateBookingSql = "UPDATE Pro_Booking SET booking_status_id = :statusId WHERE booking_id = :bookingId";
        $updateBookingStmt = $this->_dbHandle->prepare($updateBookingSql);
        $updateBookingStmt->bindParam(':statusId', $statusId, PDO::PARAM_INT);
        $updateBookingStmt->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $bookingUpdateSuccess = $updateBookingStmt->execute();
        
        // If status is rejected (3), update charge point status to available (1)
        if ($bookingUpdateSuccess && $statusId == 3 && $chargePointId) {
            $updateChargePointSql = "UPDATE Pro_ChargePoint SET availability_status_id = 1 WHERE charge_point_id = :chargePointId";
            $updateChargePointStmt = $this->_dbHandle->prepare($updateChargePointSql);
            $updateChargePointStmt->bindParam(':chargePointId', $chargePointId, PDO::PARAM_INT);
            $chargePointUpdateSuccess = $updateChargePointStmt->execute();
            
            if (!$chargePointUpdateSuccess) {
                // If charge point update fails, roll back transaction
                $this->_dbHandle->rollBack();
                return false;
            }
        }
        
        // Commit the transaction
        $this->_dbHandle->commit();
        return true;
        
    } catch (PDOException $e) {
        // Roll back the transaction if any error occurs
        $this->_dbHandle->rollBack();
        // Handle or log the error as needed
        // error_log("Database error: " . $e->getMessage());
        return false;
    }
}
    
    /**
     * Check if a booking belongs to the homeowner
     * 
     * @param int $bookingId The ID of the booking
     * @param int $homeownerId The ID of the homeowner
     * @return bool True if booking belongs to homeowner, false otherwise
     */
    public function isBookingOwnedByHomeowner($bookingId, $homeownerId) {
        $sql = "SELECT COUNT(*) FROM Pro_Booking b
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                WHERE b.booking_id = :bookingId AND cp.user_id = :homeownerId";
                
        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $statement->bindParam(':homeownerId', $homeownerId, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetchColumn() > 0;
    }
    
    /**
     * Get detailed information about a specific booking request
     * 
     * @param int $bookingId The ID of the booking
     * @param int $homeownerId The ID of the homeowner
     * @return array|false Booking details or false if not found
     */
    public function getBookingRequestDetails($bookingId, $homeownerId) {
        $sql = "SELECT 
                    b.*, 
                    bs.booking_status_title,
                    u.first_name, 
                    u.last_name, 
                    u.username,
                    u.phone_number AS customer_phone,
                    u.email AS customer_email,
                    cpa.streetName, 
                    cpa.house_number,
                    cpa.road,
                    cpa.block, 
                    c.city_name,
                    cpa.postcode, 
                    cp.price_per_kwh, 
                    cp.charge_point_picture_url
                FROM Pro_Booking b
                JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
                JOIN Pro_User u ON b.user_id = u.user_id
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                JOIN Pro_City c ON cpa.city_id = c.city_id
                WHERE b.booking_id = :bookingId
                AND cp.user_id = :homeownerId";
        
        $statement = $this->_dbHandle->prepare($sql);
        $statement->bindParam(':bookingId', $bookingId, PDO::PARAM_INT);
        $statement->bindParam(':homeownerId', $homeownerId, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
}