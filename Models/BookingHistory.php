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
    public function getUserBookingHistory() {
        $sql = "SELECT b.*, cpa.streetName, cpa.house_number 
        FROM Pro_Booking b
        JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
        JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
        WHERE b.user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    

}


