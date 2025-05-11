<?php

require_once 'Models/Database.php';

class RentalUserHome {
    private $dbHandle;  
    private $userId;
    
    public function __construct($userId) {

        $this->userId = $userId;
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }

    //get user active reservations count
    public function getUserActiveReservationCount() {
        $sql = "SELECT COUNT(*) FROM Pro_Booking WHERE booking_status_id = :status_id AND user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindValue(':status_id', 1, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0; // Return 0 if no rows are found
    }
    
    //get the total user borrowings
    public function getUserBorrowingCount() {
        $sql = "SELECT COUNT(*) FROM Pro_Booking WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count !== false ? $count : 0; // Return 0 if no rows are found
    }
    
    //get the active reservations 
    public function getUserActiveReservations() {
        $sql = "SELECT * FROM Pro_Booking WHERE booking_status_id = :status_id AND user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindValue(':status_id', 2, PDO::PARAM_INT);
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
}
