<?php
require_once 'Models/Database.php';

class HomeOwnerHome {
    private $dbHandle;  
    private $userId;
    
    public function __construct($userId) {
        $this->userId = $userId;
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }
    
    /**
     * Get the total number of charge points owned by the homeowner
     */
    public function getChargePointsCount() {
        $sql = "SELECT COUNT(*) FROM Pro_ChargePoint WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0;
    }
    
    /**
     * Get pending booking requests count
     */
    public function getPendingBookingRequestsCount() {
        $sql = "SELECT COUNT(*) FROM Pro_Booking b
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                WHERE cp.user_id = :user_id AND b.booking_status_id = 1";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0;
    }
    
    /**
     * Get total bookings count
     */
    public function getTotalBookingsCount() {
        $sql = "SELECT COUNT(*) FROM Pro_Booking b
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                WHERE cp.user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0;
    }
    
    /**
     * Get approved bookings count
     */
    public function getApprovedBookingsCount() {
        $sql = "SELECT COUNT(*) FROM Pro_Booking b
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                WHERE cp.user_id = :user_id AND b.booking_status_id = 2";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0;
    }
    
    /**
     * Get all bookings for the homeowner's charge points
     */
    public function getAllBookings() {
        $sql = "SELECT b.booking_id, b.booking_date, b.booking_time, b.booking_status_id, 
                       cp.charge_point_id, cp.price_per_kwh, bs.booking_status_title,
                       u.first_name, u.last_name, cpa.streetName, cpa.postcode
                FROM Pro_Booking b
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
                JOIN Pro_User u ON b.user_id = u.user_id
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                WHERE cp.user_id = :user_id
                ORDER BY b.booking_date DESC, b.booking_time DESC";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get upcoming bookings (approved bookings with future dates)
     */
    public function getUpcomingBookings() {
        $today = date('Y-m-d');
        $sql = "SELECT b.booking_id, b.booking_date, b.booking_time, 
                       u.first_name, u.last_name, bs.booking_status_title,
                       cp.charge_point_id, cpa.streetName, cpa.postcode
                FROM Pro_Booking b
                JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
                JOIN Pro_User u ON b.user_id = u.user_id
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                WHERE cp.user_id = :user_id 
                AND b.booking_status_id = 2
                AND (b.booking_date >= :today OR 
                    (b.booking_date = :today AND b.booking_time >= CURRENT_TIME))
                ORDER BY b.booking_date ASC, b.booking_time ASC
                LIMIT 5";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->bindParam(':today', $today, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}