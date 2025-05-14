<?php
require_once 'Models/Database.php';
class BookingHistory {
    private $dbHandle;  
    private $userId;
    
    public function __construct($userId) {
        $this->userId = $userId;
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }
    //get user active reservations count
      public function getUserBookingHistory($status = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT b.*, cpa.streetName, cpa.house_number 
                FROM Pro_Booking b
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                WHERE b.user_id = :user_id";
        
        if ($status) {
            $sql .= " AND b.booking_status_id = :status";
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        
        if ($status) {
            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookingCount($status = null) {
        $sql = "SELECT COUNT(*) FROM Pro_Booking WHERE user_id = :user_id";
        
        if ($status) {
            $sql .= " AND booking_status_id = :status";
        }
        
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);

        if ($status) {
            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchColumn();
    }
    //fetch one booking details
    public function getBookingDetails($bookingId) {
        $sql = "SELECT 
                    b.*, 
                    cpa.streetName, 
                    cpa.house_number,
                    cpa.road,
                    cpa.block, 
                    c.city_name,
                    cpa.postcode,
                    cpa.latitude,
                    cpa.longitude, 
                    cp.price_per_kwh, 
                    cp.charge_point_picture_url,
                    ho.first_name AS ownerfirst_name, 
                    ho.last_name AS ownerlast_name, 
                    ho.phone_number AS homeowner_phone
                FROM Pro_Booking b
                JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                JOIN Pro_City c ON cpa.city_id = c.city_id
                JOIN Pro_User ho ON cp.user_id = ho.user_id
                WHERE b.booking_id = :booking_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}