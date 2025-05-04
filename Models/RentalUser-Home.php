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
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    //get the total user borrowings
    public function getUserBorrowingCount() {
        $sql = "SELECT COUNT(*) FROM Pro_Booking WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    

}
