<?php
require_once 'Models/Database.php';
class BookCharger {
    private $dbHandle;

    public function __construct() {
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }

    /**
     * Check if the charger is already booked for a specific date and time
     * 
     * @param int $chargePointId The charge point ID
     * @param string $bookingDate The booking date in yyyy-mm-dd format
     * @param string $bookingTime The booking time
     * @return bool True if already booked, false otherwise
     */
    public function isAlreadyBooked($chargePointId, $bookingDate, $bookingTime) {
        $sql = "SELECT COUNT(*) FROM Pro_Booking 
                WHERE charge_point_id = :charge_point_id 
                AND DATE(booking_date) = :booking_date 
                AND booking_time = :booking_time
                AND (booking_status_id = 1 OR booking_status_id = 2)"; // Pending or Approved
        
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->bindParam(':booking_date', $bookingDate, PDO::PARAM_STR);
        $stmt->bindParam(':booking_time', $bookingTime, PDO::PARAM_STR);
        $stmt->execute();
        
        return ($stmt->fetchColumn() > 0);
    }

    /**
     * Check if this is the last available slot for the charge point
     * If it is, update the charge point's availability status to "Unavailable"
     * 
     * @param int $chargePointId The charge point ID
     * @return bool True if update was successful, false otherwise
     */
    private function checkAndUpdateAvailability($chargePointId) {
        // Get all available days and times for this charge point
        $sql = "SELECT 
                    at.available_time,
                    ad.day_of_week
                FROM Pro_AvailabilityTimes at
                JOIN Pro_AvailabilityDays ad ON at.availability_day_id = ad.availability_day_id
                WHERE ad.charge_point_id = :charge_point_id";
        
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
        
        $availableSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalAvailableSlots = count($availableSlots);
        
        // If no available slots defined, no need to update
        if ($totalAvailableSlots === 0) {
            return false;
        }
        
        // Count how many slots are already booked
        $bookedSlotsCount = 0;
        $today = new DateTime();
        $maxDaysToCheck = 14; // Check bookings for the next 14 days
        
        foreach ($availableSlots as $slot) {
            // For each available day of the week, check the next occurrences
            $dayOfWeek = $slot['day_of_week'];
            $slotTime = $slot['available_time'];
            
            $checkDate = clone $today;
            $daysChecked = 0;
            
            // Check for the next few occurrences of this day of week
            while ($daysChecked < $maxDaysToCheck) {
                if (strtolower($checkDate->format('l')) === strtolower($dayOfWeek)) {
                    $dateStr = $checkDate->format('Y-m-d');
                    
                    if ($this->isAlreadyBooked($chargePointId, $dateStr, $slotTime)) {
                        $bookedSlotsCount++;
                    }
                }
                
                $checkDate->modify('+1 day');
                $daysChecked++;
            }
        }
        
        // If all slots are booked, update the availability status
        if ($bookedSlotsCount >= $totalAvailableSlots) {
            $sql = "UPDATE Pro_ChargePoint 
                    SET availability_status_id = 2 
                    WHERE charge_point_id = :charge_point_id";
            
            $stmt = $this->dbHandle->prepare($sql);
            $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            return $stmt->execute();
        }
        
        return false;
    }
 
    /**
     * Add a new booking
     * 
     * @param int $userId The user ID
     * @param int $chargePointId The charge point ID
     * @param string $bookingDate The booking date in dd-mm-yyyy format
     * @param string $bookingTime The booking time
     * @return array Result with success status and message
     */
    public function addBooking($userId, $chargePointId, $bookingDate, $bookingTime) {
        // Convert 'dd-mm-yyyy' to 'yyyy-mm-dd' for database
        $dateParts = explode("-", $bookingDate);
        $formattedDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}"; // Converts '12-05-2025' to '2025-05-12'
        
        // Double check if the time slot is already booked
        if ($this->isAlreadyBooked($chargePointId, $formattedDate, $bookingTime)) {
            return [
                'success' => false,
                'message' => 'This time slot is already booked. Please select another time.'
            ];
        }
        
        $sql = "INSERT INTO Pro_Booking (user_id, charge_point_id, booking_date, booking_time, booking_status_id)
                VALUES (:user_id, :charge_point_id, :booking_date, :booking_time, 1)"; // Status ID set to Pending
                
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->bindParam(':booking_date', $formattedDate, PDO::PARAM_STR);
        $stmt->bindParam(':booking_time', $bookingTime, PDO::PARAM_STR);

        $result = $stmt->execute();
        
        if ($result) {
            // After successful booking, check if this was the last available slot
            $this->checkAndUpdateAvailability($chargePointId);
            
            return [
                'success' => true,
                'message' => 'Booking successfully created!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Booking failed. Please try again.'
            ];
        }
    }
    
    /**
     * Get available days for a charge point within the specified number of days
     * 
     * @param int $chargePointId The charge point ID
     * @param array $timeSlots Available time slots to check
     * @param int $daysToCheck Number of days to check
     * @return array List of available days in dd-mm-yyyy format
     */
    public function getAvailableDays($chargePointId, $timeSlots, $daysToCheck = 7) {
        $availableDays = [];
        $today = new DateTime();

        for ($i = 0; $i < $daysToCheck; $i++) {
            $date = clone $today;
            $date->modify("+$i day");
            $dateStr = $date->format('Y-m-d');

            // Check if at least one time slot is available for this day
            $hasAvailableSlot = false;
            foreach ($timeSlots as $slot) {
                if (!$this->isAlreadyBooked($chargePointId, $dateStr, $slot)) {
                    $hasAvailableSlot = true;
                    break;
                }
            }

            // Only add the day if at least one time slot is available
            if ($hasAvailableSlot) {
                $availableDays[] = $date->format('d-m-Y'); // Return in display format
            }
        }

        return $availableDays;
    }

    /**
     * Get available time slots for a specific day
     * 
     * @param int $chargePointId The charge point ID
     * @param string $bookingDate The booking date in dd-mm-yyyy format
     * @param array $timeSlots Available time slots to check
     * @return array List of available time slots
     */
    public function getAvailableTimeSlots($chargePointId, $bookingDate, $timeSlots) {
        $availableSlots = [];
        $dateParts = explode("-", $bookingDate);
        $formattedDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}"; // dd-mm-yyyy to yyyy-mm-dd

        foreach ($timeSlots as $slot) {
            if (!$this->isAlreadyBooked($chargePointId, $formattedDate, $slot)) {
                $availableSlots[] = $slot;
            }
        }

        return $availableSlots;
    }
}