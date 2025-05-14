<?php
require_once 'Models/Database.php';
class RentalUserHome {
    private $dbHandle;  
    private $userId;
    
    public function __construct($userId) {
        $this->userId = $userId;
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }
    
    // Get user active reservations count
    public function getUserActiveReservationCount() {
        $sql = "SELECT COUNT(*) FROM Pro_Booking WHERE booking_status_id = :status_id AND user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindValue(':status_id', 1, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0; // Return 0 if no rows are found
    }
    
    // Get the total user borrowings
    public function getUserBorrowingCount() {
        $sql = "SELECT COUNT(*) FROM Pro_Booking WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0; // Return 0 if no rows are found
    }
    
    // Get the active reservations 
    public function getUserActiveReservations() {
        $sql = "SELECT b.*, bs.booking_status_title as status 
                FROM Pro_Booking b
                JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
                WHERE b.user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total number of charge points 
    public function getTotalChargePoints() {
        $sql = "SELECT COUNT(*) FROM Pro_ChargePoint";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn(); 
    }
    
    // Get reservation count by status
    public function getReservationsByStatus($statusId) {
        $sql = "SELECT COUNT(*) FROM Pro_Booking WHERE booking_status_id = :status_id AND user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindValue(':status_id', $statusId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0;
    }
    
    // Get approved upcoming reservations with pagination
    public function getApprovedUpcomingReservations($limit = 5, $offset = 0) {
        $today = date("Y-m-d");
        $sql = "SELECT b.*, bs.booking_status_title, cp.charge_point_id,
                u.first_name AS host_first_name, u.last_name AS host_last_name,
                cpa.streetName, cpa.house_number, c.city_name
                FROM Pro_Booking b
                JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                JOIN Pro_User u ON cp.user_id = u.user_id
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                JOIN Pro_City c ON cpa.city_id = c.city_id
                WHERE b.user_id = :user_id 
                AND b.booking_status_id = 2 -- Only approved reservations
                AND (b.booking_date > :today OR (b.booking_date = :today AND b.booking_time >= :current_time))
                ORDER BY b.booking_date ASC, b.booking_time ASC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->bindParam(':today', $today, PDO::PARAM_STR);
        $stmt->bindValue(':current_time', date("H:i:s"), PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for display
        foreach ($reservations as &$reservation) {
            $reservation['host_name'] = $reservation['host_first_name'] . ' ' . $reservation['host_last_name'];
            $reservation['address'] = $reservation['streetName'] . ', ' . $reservation['house_number'] . ', ' . $reservation['city_name'];
            $reservation['booking_id'] = $reservation['booking_id'];
        }
        
        return $reservations;
    }
    
    // Count total approved upcoming reservations
    public function countApprovedUpcomingReservations() {
        $today = date("Y-m-d");
        $sql = "SELECT COUNT(*) FROM Pro_Booking b
                WHERE b.user_id = :user_id 
                AND b.booking_status_id = 2 -- Only approved reservations
                AND (b.booking_date > :today OR (b.booking_date = :today AND b.booking_time >= :current_time))";
        
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->bindParam(':today', $today, PDO::PARAM_STR);
        $stmt->bindValue(':current_time', date("H:i:s"), PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
}