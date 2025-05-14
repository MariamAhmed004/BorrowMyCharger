<?php
require_once 'Models/Database.php';

class Profile {
    private $dbHandle;
    private $userId;
    
    public function __construct($userId) {
        $this->userId = $userId;
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }
    
    public function getUserData() {
        $sql = "SELECT * FROM Pro_User WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateUserProfile($firstName, $lastName, $email, $phoneNumber) {
        $sql = "UPDATE Pro_User SET first_name = :first_name, last_name = :last_name, phone_number = :phone_number WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':phone_number', $phoneNumber);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function deleteUserAccount() {
        try {
            $this->dbHandle->beginTransaction();
            
            // Get user role first
            $userRole = $this->getUserRole();
            
            if ($userRole == 3) { // RentalUser
                // Delete all associated bookings
                $this->deleteUserBookings();
            } elseif ($userRole == 2) { // HomeOwner
                // Delete all related charge points and their associated data
                $this->deleteUserChargePoints();
            }
            
            // Finally delete the user
            $sql = "DELETE FROM Pro_User WHERE user_id = :user_id";
            $stmt = $this->dbHandle->prepare($sql);
            $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->dbHandle->commit();
            return true;
        } catch (PDOException $e) {
            $this->dbHandle->rollBack();
            error_log("Error deleting user account: " . $e->getMessage());
            return false;
        }
    }
    
    private function getUserRole() {
        $sql = "SELECT role_id FROM Pro_User WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['role_id'] : null;
    }
    
    private function deleteUserBookings() {
        $sql = "DELETE FROM Pro_Booking WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    private function deleteUserChargePoints() {
        // Get all charge points owned by this user
        $sql = "SELECT charge_point_id FROM Pro_ChargePoint WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        $chargePoints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($chargePoints as $chargePoint) {
            $chargePointId = $chargePoint['charge_point_id'];
            
            // Delete all availability times for this charge point
            $this->deleteAvailabilityTimes($chargePointId);
            
            // Delete all availability days for this charge point
            $this->deleteAvailabilityDays($chargePointId);
            
            // Delete all bookings for this charge point
            $this->deleteChargePointBookings($chargePointId);
        }
        
        // Delete the charge points
        $sql = "DELETE FROM Pro_ChargePoint WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    private function deleteAvailabilityTimes($chargePointId) {
        // First get all availability day IDs for this charge point
        $sql = "SELECT availability_day_id FROM Pro_AvailabilityDays WHERE charge_point_id = :charge_point_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
        $availabilityDays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete availability times for each day
        foreach ($availabilityDays as $day) {
            $dayId = $day['availability_day_id'];
            $sql = "DELETE FROM Pro_AvailabilityTimes WHERE availability_day_id = :availability_day_id";
            $stmt = $this->dbHandle->prepare($sql);
            $stmt->bindParam(':availability_day_id', $dayId, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    private function deleteAvailabilityDays($chargePointId) {
        $sql = "DELETE FROM Pro_AvailabilityDays WHERE charge_point_id = :charge_point_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    private function deleteChargePointBookings($chargePointId) {
        // Check if there are any reviews for the bookings
        $sql = "SELECT b.booking_id FROM Pro_Booking b 
                LEFT JOIN Pro_Review r ON b.booking_id = r.booking_id 
                WHERE b.charge_point_id = :charge_point_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete reviews first
        foreach ($bookings as $booking) {
            $bookingId = $booking['booking_id'];
            $sql = "DELETE FROM Pro_Review WHERE booking_id = :booking_id";
            $stmt = $this->dbHandle->prepare($sql);
            $stmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Then delete bookings
        $sql = "DELETE FROM Pro_Booking WHERE charge_point_id = :charge_point_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
    }
}