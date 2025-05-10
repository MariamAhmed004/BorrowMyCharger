<?php
require_once 'Models/Database.php';
class BookCharger {
    private $dbHandle;

    public function __construct() {
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }
 
    public function addBooking($userId, $chargePointId, $bookingDate, $bookingTime) {
        $sql = "INSERT INTO Pro_Booking (user_id, charge_point_id, booking_date, booking_time, booking_status_id)
                VALUES (:user_id, :charge_point_id, :booking_date, :booking_time, 1)"; // Status ID set to Pending

        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        
        // Convert 'dd-mm-yyyy' to 'yyyy-mm-dd' before inserting into the database
        $dateParts = explode("-", $bookingDate);
        $formattedDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}"; // Converts '12-05-2025' to '2025-05-12'

        $stmt->bindParam(':booking_date', $formattedDate, type: PDO::PARAM_STR);

        $stmt->bindParam(':booking_time', $bookingTime, PDO::PARAM_STR);

        return $stmt->execute();
    }
}
