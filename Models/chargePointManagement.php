<?php
require_once 'Database.php';

class chargePointManagement {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getDbConnection();
    }
    
    /**
     * Get all charge points with their related information
     * @return array
     */
    public function getAllChargePoints() {
        $query = "SELECT cp.charge_point_id, CONCAT(u.first_name, ' ', u.last_name) as owner, 
                cp.price_per_kwh, avs.availability_status_title as availability, 
                cpa.streetName, cpa.latitude, cpa.longitude, cp.charge_point_picture_url
                FROM Pro_ChargePoint cp
                JOIN Pro_User u ON cp.user_id = u.user_id
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                LEFT JOIN Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
                ORDER BY cp.charge_point_id DESC";
        
        $statement = $this->db->prepare($query);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get a specific charge point by ID
     * @param int $id Charge point ID
     * @return array|false
     */
    public function getChargePointById($id) {
        $query = "SELECT cp.charge_point_id, cp.user_id, CONCAT(u.first_name, ' ', u.last_name) as owner, 
                cp.price_per_kwh, cp.availability_status_id, avs.availability_status_title as availability, 
                cpa.charge_point_address_id, cpa.streetName, cpa.latitude, cpa.longitude, 
                cpa.postcode, cpa.city_id, cpa.house_number, cpa.road, cpa.block,
                cp.charge_point_picture_url
                FROM Pro_ChargePoint cp
                JOIN Pro_User u ON cp.user_id = u.user_id
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                LEFT JOIN Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
                WHERE cp.charge_point_id = :id";
        
        $statement = $this->db->prepare($query);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get availability days for a charge point
     * @param int $chargePointId Charge point ID
     * @return array
     */
    public function getAvailabilityDays($chargePointId) {
        $query = "SELECT availability_day_id, charge_point_id, day_of_week
                FROM Pro_AvailabilityDays
                WHERE charge_point_id = :charge_point_id
                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
        
        $statement = $this->db->prepare($query);
        $statement->bindValue(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get availability times for specific days
     * @param array $dayIds Array of day IDs
     * @return array
     */
    public function getAvailabilityTimes($dayIds) {
        if (empty($dayIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($dayIds), '?'));
        $query = "SELECT availability_day_id, available_time
                FROM Pro_AvailabilityTimes
                WHERE availability_day_id IN ($placeholders)
                ORDER BY available_time";
        
        $statement = $this->db->prepare($query);
        foreach ($dayIds as $index => $dayId) {
            $statement->bindValue($index + 1, $dayId, PDO::PARAM_INT);
        }
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update charge point basic information
     * @param int $id Charge point ID
     * @param float $price Price per kWh
     * @param int $availabilityStatusId Availability status ID
     * @param string|null $picturePath New picture path if uploaded
     * @return bool Success status
     */
    public function updateChargePoint($id, $price, $availabilityStatusId, $picturePath = null) {
        $query = "UPDATE Pro_ChargePoint SET 
                price_per_kwh = :price,
                availability_status_id = :availability_status_id";
        
        $params = [
            ':price' => $price,
            ':availability_status_id' => $availabilityStatusId,
            ':id' => $id
        ];
        
        if ($picturePath) {
            $query .= ", charge_point_picture_url = :picture_url";
            $params[':picture_url'] = $picturePath;
        }
        
        $query .= " WHERE charge_point_id = :id";
        
        $statement = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        
        return $statement->execute();
    }
    
  /**
 * Update charge point address
 * @param int $addressId Address ID
 * @param string $streetName Street name
 * @param float $latitude Latitude
 * @param float $longitude Longitude
 * @param string $postcode Postcode
 * @param int $cityId City ID
 * @param int $houseNumber House number
 * @param int $road Road
 * @param int $block Block
 * @return bool Success status
 */
public function updateChargePointAddress($addressId, $streetName, $latitude, $longitude, $postcode, $cityId, $houseNumber, $road, $block) {
    $query = "UPDATE Pro_ChargePointAddress SET 
            streetName = :streetName,
            latitude = :latitude,
            longitude = :longitude,
            postcode = :postcode,
            city_id = :city_id,
            house_number = :house_number,
            road = :road,
            block = :block
            WHERE charge_point_address_id = :address_id";
    
    $statement = $this->db->prepare($query);
    $statement->bindValue(':streetName', $streetName);
    $statement->bindValue(':latitude', $latitude);
    $statement->bindValue(':longitude', $longitude);
    $statement->bindValue(':postcode', $postcode);
    $statement->bindValue(':city_id', $cityId, PDO::PARAM_INT);
    $statement->bindValue(':house_number', $houseNumber, PDO::PARAM_INT);
    $statement->bindValue(':road', $road, PDO::PARAM_INT);
    $statement->bindValue(':block', $block, PDO::PARAM_INT);
    $statement->bindValue(':address_id', $addressId, PDO::PARAM_INT);
    
    return $statement->execute();
}
    /**
     * Convert numeric time index to hh:mm format
     * @param mixed $time Time value (numeric index 1-24)
     * @return string Formatted time in hh:mm format
     */
    private function formatTimeToHHMM($time) {
        // Convert numeric index to hour
        $timeValue = (int)$time;
        
        // Ensure valid range
        if ($timeValue < 1 || $timeValue > 24) {
            throw new Exception("Invalid time value: $time. Please use a value between 1 and 24.");
        }
        
        // Convert to hour format (e.g., 1 -> 01:00, 13 -> 13:00)
        $hour = $timeValue;
        if ($hour > 24) {
            $hour = 24;
        }
        
        return sprintf("%02d:00", $hour);
    }
    
    /**
     * Update availability times for a day
     * @param int|string $dayId Day ID or day of week name if new
     * @param array $times Array of time strings
     * @param int $chargePointId Charge point ID (needed for new days)
     * @return bool Success status
     * @throws Exception If time format is invalid
     */
    public function updateAvailabilityTimes($dayId, $times, $chargePointId = null) {
        // Begin transaction to ensure data integrity
        $this->db->beginTransaction();
        
        try {
            $times = array_filter(array_map('trim', $times)); // Clean up and remove empty values
            
            // Format all times to HH:MM format
            $formattedTimes = [];
            foreach ($times as $time) {
                $formattedTimes[] = $this->formatTimeToHHMM($time);
            }
            
            // Check if this is a new day or existing day
            $isNewDay = !is_numeric($dayId);
            
            // Case 1: Existing day with no times - delete the day
            if (!$isNewDay && empty($formattedTimes)) {
                // First, delete existing times for this day
                $deleteTimesQuery = "DELETE FROM Pro_AvailabilityTimes WHERE availability_day_id = :day_id";
                $deleteTimesStatement = $this->db->prepare($deleteTimesQuery);
                $deleteTimesStatement->bindValue(':day_id', $dayId, PDO::PARAM_INT);
                $deleteTimesStatement->execute();
                
                // Then delete the day record
                $deleteDayQuery = "DELETE FROM Pro_AvailabilityDays WHERE availability_day_id = :day_id";
                $deleteDayStatement = $this->db->prepare($deleteDayQuery);
                $deleteDayStatement->bindValue(':day_id', $dayId, PDO::PARAM_INT);
                $deleteDayStatement->execute();
            }
            // Case 2: New day with times - insert the day first, then times
            else if ($isNewDay && !empty($formattedTimes) && $chargePointId) {
                // Insert new day
                $insertDayQuery = "INSERT INTO Pro_AvailabilityDays (charge_point_id, day_of_week) VALUES (:charge_point_id, :day_of_week)";
                $insertDayStatement = $this->db->prepare($insertDayQuery);
                $insertDayStatement->bindValue(':charge_point_id', $chargePointId, PDO::PARAM_INT);
                $insertDayStatement->bindValue(':day_of_week', $dayId); // Here $dayId is the day name
                $insertDayStatement->execute();
                
                // Get the new day ID
                $newDayId = $this->db->lastInsertId();
                
                // Insert times for the new day
                $insertTimeQuery = "INSERT INTO Pro_AvailabilityTimes (availability_day_id, available_time) VALUES (:day_id, :time)";
                $insertTimeStatement = $this->db->prepare($insertTimeQuery);
                
                foreach ($formattedTimes as $time) {
                    $insertTimeStatement->bindValue(':day_id', $newDayId, PDO::PARAM_INT);
                    $insertTimeStatement->bindValue(':time', $time);
                    $insertTimeStatement->execute();
                }
            }
            // Case 3: Existing day with times - update times
            else if (!$isNewDay && !empty($formattedTimes)) {
                // First, delete existing times for this day
                $deleteTimesQuery = "DELETE FROM Pro_AvailabilityTimes WHERE availability_day_id = :day_id";
                $deleteTimesStatement = $this->db->prepare($deleteTimesQuery);
                $deleteTimesStatement->bindValue(':day_id', $dayId, PDO::PARAM_INT);
                $deleteTimesStatement->execute();
                
                // Then insert new times
                $insertTimeQuery = "INSERT INTO Pro_AvailabilityTimes (availability_day_id, available_time) VALUES (:day_id, :time)";
                $insertTimeStatement = $this->db->prepare($insertTimeQuery);
                
                foreach ($formattedTimes as $time) {
                    $insertTimeStatement->bindValue(':day_id', $dayId, PDO::PARAM_INT);
                    $insertTimeStatement->bindValue(':time', $time);
                    $insertTimeStatement->execute();
                }
            }
            
            // Commit transaction
            $this->db->commit();
            return true;
        } 
        catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            throw $e; // Re-throw to be caught by caller
        }
    }
    
    /**
     * Delete a charge point
     * @param int $id Charge point ID
     * @return bool Success status
     */
    public function deleteChargePoint($id) {
        // First get availability days to delete related times
        $days = $this->getAvailabilityDays($id);
        $dayIds = array_column($days, 'availability_day_id');
        
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            // Delete availability times
            if (!empty($dayIds)) {
                $placeholders = implode(',', array_fill(0, count($dayIds), '?'));
                $deleteTimesQuery = "DELETE FROM Pro_AvailabilityTimes WHERE availability_day_id IN ($placeholders)";
                $deleteTimesStmt = $this->db->prepare($deleteTimesQuery);
                foreach ($dayIds as $index => $dayId) {
                    $deleteTimesStmt->bindValue($index + 1, $dayId, PDO::PARAM_INT);
                }
                $deleteTimesStmt->execute();
            }
            
            // Delete availability days
            $deleteDaysQuery = "DELETE FROM Pro_AvailabilityDays WHERE charge_point_id = ?";
            $deleteDaysStmt = $this->db->prepare($deleteDaysQuery);
            $deleteDaysStmt->bindValue(1, $id, PDO::PARAM_INT);
            $deleteDaysStmt->execute();
            
            // Delete bookings
            $deleteBookingsQuery = "DELETE FROM Pro_Booking WHERE charge_point_id = ?";
            $deleteBookingsStmt = $this->db->prepare($deleteBookingsQuery);
            $deleteBookingsStmt->bindValue(1, $id, PDO::PARAM_INT);
            $deleteBookingsStmt->execute();
            
            // Get address ID before deleting charge point
            $getAddressQuery = "SELECT charge_point_address_id FROM Pro_ChargePoint WHERE charge_point_id = ?";
            $getAddressStmt = $this->db->prepare($getAddressQuery);
            $getAddressStmt->bindValue(1, $id, PDO::PARAM_INT);
            $getAddressStmt->execute();
            $addressId = $getAddressStmt->fetchColumn();
            
            // Delete charge point
            $deleteChargePointQuery = "DELETE FROM Pro_ChargePoint WHERE charge_point_id = ?";
            $deleteChargePointStmt = $this->db->prepare($deleteChargePointQuery);
            $deleteChargePointStmt->bindValue(1, $id, PDO::PARAM_INT);
            $deleteChargePointStmt->execute();
            
            // Delete address
            if ($addressId) {
                $deleteAddressQuery = "DELETE FROM Pro_ChargePointAddress WHERE charge_point_address_id = ?";
                $deleteAddressStmt = $this->db->prepare($deleteAddressQuery);
                $deleteAddressStmt->bindValue(1, $addressId, PDO::PARAM_INT);
                $deleteAddressStmt->execute();
                $deleteAddressStmt->execute();
            }
            
            // Commit transaction
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Get all availability statuses
     * @return array
     */
    public function getAvailabilityStatuses() {
        $query = "SELECT availability_status_id, availability_status_title FROM Pro_AvailabilityStatus";
        $statement = $this->db->prepare($query);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
     // Save charge point availability day and times
    public function saveChargePointAvailability($chargePointId, $dayOfWeek, $times) {
        try {
            // Insert the day first
            $sqlDay = "INSERT INTO Pro_AvailabilityDays (charge_point_id, day_of_week) VALUES (:charge_point_id, :day_of_week)";
            $stmtDay = $this->dbConnection->prepare($sqlDay);
            $stmtDay->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $stmtDay->bindParam(':day_of_week', $dayOfWeek, PDO::PARAM_STR);
            $stmtDay->execute();
            
            $dayId = $this->dbConnection->lastInsertId();
            
            // Then insert each time for this day
            $sqlTime = "INSERT INTO Pro_AvailabilityTimes (availability_day_id, available_time) VALUES (:availability_day_id, :available_time)";
            $stmtTime = $this->dbConnection->prepare($sqlTime);
            
            foreach ($times as $time) {
                $stmtTime->bindParam(':availability_day_id', $dayId, PDO::PARAM_INT);
                $stmtTime->bindParam(':available_time', $time, PDO::PARAM_STR);
                $stmtTime->execute();
            }
            
            // Update availability status based on time slots
            $this->updateAvailabilityStatus($chargePointId);
            
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
     // Add a new charge point
    public function addChargePoint($userId, $data) {
        try {
            $this->db->beginTransaction();
            
            // First, insert the address
            $addressSql = "INSERT INTO Pro_ChargePointAddress (postcode, latitude, longitude, streetName, city_id, house_number, road, block) 
                          VALUES (:postcode, :latitude, :longitude, :streetName, :city_id, :house_number, :road, :block)";
            
            $addressStatement = $this->db->prepare($addressSql);
            $addressStatement->bindParam(':postcode', $data['postcode'], PDO::PARAM_STR);
            $addressStatement->bindParam(':latitude', $data['latitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':longitude', $data['longitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':streetName', $data['streetName'], PDO::PARAM_STR);
            $addressStatement->bindParam(':city_id', $data['city_id'], PDO::PARAM_INT);
            $addressStatement->bindParam(':house_number', $data['house_number'], PDO::PARAM_INT);
            $addressStatement->bindParam(':road', $data['road'], PDO::PARAM_INT);
            $addressStatement->bindParam(':block', $data['block'], PDO::PARAM_INT);
            $addressStatement->execute();
            
            $addressId = $this->db->lastInsertId();
            
            // Default to Unavailable (2) 
            $availabilityStatusId = 2;
            
            // Then insert the charge point
            $chargePointSql = "INSERT INTO Pro_ChargePoint (price_per_kwh, charge_point_picture_url, user_id, charge_point_address_id, availability_status_id) 
                              VALUES (:price_per_kwh, :charge_point_picture_url, :user_id, :charge_point_address_id, :availability_status_id)";
            
            $chargePointStatement = $this->db->prepare($chargePointSql);
            $chargePointStatement->bindParam(':price_per_kwh', $data['price_per_kwh'], PDO::PARAM_STR);
            $chargePointStatement->bindParam(':charge_point_picture_url', $data['charge_point_picture_url'], PDO::PARAM_STR);
            $chargePointStatement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $chargePointStatement->bindParam(':charge_point_address_id', $addressId, PDO::PARAM_INT);
            $chargePointStatement->bindParam(':availability_status_id', $availabilityStatusId, PDO::PARAM_INT);
            
            $chargePointStatement->execute();
            
            $chargePointId = $this->db->lastInsertId();
            
            $this->db->commit();
            
            return $chargePointId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
  
     public function getHomeOwnersWithoutChargePoints() {
    $query = "SELECT u.* FROM Pro_User u
              LEFT JOIN Pro_ChargePoint cp ON u.user_id = cp.user_id
              WHERE u.role_id = 2 AND cp.charge_point_id IS NULL
              AND u.user_account_status_id = 1";
              
    $statement = $this->db->prepare($query);
    
    try {
        $statement->execute();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
}
?>