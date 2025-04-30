
<?php
require_once 'Models/Database.php';
class MyChargePoint {
    protected $_dbHandle;
    protected $userId;

    public function __construct() {
        $database = Database::getInstance();
        $this->_dbHandle = $database->getDbConnection();
        
        // Get the logged-in user's ID from session
        if (isset($_SESSION['user_id'])) {
            $this->userId = $_SESSION['user_id'];
        }
    }

    /**
     * Get cities from the database
     * @return array|false Returns an array of cities or false if none found
     */
    public function getCities() {
        try {
            $statement = $this->_dbHandle->prepare("SELECT city_id, city_name FROM Pro_City ORDER BY city_name ASC");
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getCities: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the user's charge point details
     * @return array|false Returns charge point data or false if none found
     */
    public function getUserChargePoint() {
        try {
            $statement = $this->_dbHandle->prepare(
                "SELECT cp.charge_point_id, cp.price_per_kwh, cp.charge_point_picture_url, 
                        cpa.charge_point_address_id, cpa.house_number, cpa.road, cpa.block, 
                        cpa.latitude, cpa.longitude, c.city_id, c.city_name, 
                        a.availability_status_id, a.availability_status_title
                 FROM Pro_ChargePoint cp
                 JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                 JOIN Pro_City c ON cpa.city_id = c.city_id
                 JOIN Pro_AvailabilityStatus a ON cp.availability_status_id = a.availability_status_id
                 WHERE cp.user_id = :userId"
            );
            $statement->bindParam(':userId', $this->userId, PDO::PARAM_INT);
            $statement->execute();
            
            $chargePoint = $statement->fetch(PDO::FETCH_ASSOC);
            
            if ($chargePoint) {
                // Get availability days and times
                $chargePoint['availabilityDays'] = $this->getAvailabilityDays($chargePoint['charge_point_id']);
            }
            
            return $chargePoint;
        } catch (PDOException $e) {
            error_log("Database error in getUserChargePoint: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get availability days and times for a charge point
     * @param int $chargePointId
     * @return array
     */
    private function getAvailabilityDays($chargePointId) {
        try {
            $statement = $this->_dbHandle->prepare(
                "SELECT ad.availability_day_id, ad.day_of_week, at.available_time
                 FROM Pro_AvailabilityDays ad
                 LEFT JOIN Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
                 WHERE ad.charge_point_id = :chargePointId
                 ORDER BY ad.day_of_week, at.available_time"
            );
            $statement->bindParam(':chargePointId', $chargePointId, PDO::PARAM_INT);
            $statement->execute();
            
            $days = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $dayOfWeek = $row['day_of_week'];
                
                if (!isset($days[$dayOfWeek])) {
                    $days[$dayOfWeek] = [
                        'availability_day_id' => $row['availability_day_id'],
                        'day_of_week' => $dayOfWeek,
                        'times' => []
                    ];
                }
                
                if ($row['available_time']) {
                    $days[$dayOfWeek]['times'][] = $row['available_time'];
                }
            }
            
            return array_values($days);
        } catch (PDOException $e) {
            error_log("Database error in getAvailabilityDays: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a new charge point
     * @param array $data Charge point data
     * @param string $imageFile Path to the uploaded image
     * @return int|false The charge point ID or false on failure
     */
    public function addChargePoint($data, $imageFile) {
        try {
            $this->_dbHandle->beginTransaction();
            
            // 1. Add charge point address
            $addressStatement = $this->_dbHandle->prepare(
                "INSERT INTO Pro_ChargePointAddress (house_number, road, block, city_id, latitude, longitude)
                 VALUES (:house_number, :road, :block, :city_id, :latitude, :longitude)"
            );
            
            $addressStatement->bindParam(':house_number', $data['house_number'], PDO::PARAM_INT);
            $addressStatement->bindParam(':road', $data['road'], PDO::PARAM_INT);
            $addressStatement->bindParam(':block', $data['block'], PDO::PARAM_INT);
            $addressStatement->bindParam(':city_id', $data['city_id'], PDO::PARAM_INT);
            $addressStatement->bindParam(':latitude', $data['latitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':longitude', $data['longitude'], PDO::PARAM_STR);
            
            $addressStatement->execute();
            $addressId = $this->_dbHandle->lastInsertId();
            
            // 2. Add the charge point
            $chargePointStatement = $this->_dbHandle->prepare(
                "INSERT INTO Pro_ChargePoint (price_per_kwh, charge_point_picture_url, user_id, charge_point_address_id, availability_status_id)
                 VALUES (:price_per_kwh, :picture_url, :user_id, :address_id, :availability_status)"
            );
            
            $chargePointStatement->bindParam(':price_per_kwh', $data['price_per_kwh'], PDO::PARAM_STR);
            $chargePointStatement->bindParam(':picture_url', $imageFile, PDO::PARAM_STR);
            $chargePointStatement->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
            $chargePointStatement->bindParam(':address_id', $addressId, PDO::PARAM_INT);
            $chargePointStatement->bindParam(':availability_status', $data['availability_status_id'], PDO::PARAM_INT);
            
            $chargePointStatement->execute();
            $chargePointId = $this->_dbHandle->lastInsertId();
            
            // 3. Add availability days and times if provided
            if (isset($data['availability_days']) && !empty($data['availability_days'])) {
                foreach ($data['availability_days'] as $day => $times) {
                    // Add day
                    $dayStatement = $this->_dbHandle->prepare(
                        "INSERT INTO Pro_AvailabilityDays (charge_point_id, day_of_week)
                         VALUES (:charge_point_id, :day_of_week)"
                    );
                    
                    $dayStatement->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
                    $dayStatement->bindParam(':day_of_week', $day, PDO::PARAM_STR);
                    $dayStatement->execute();
                    
                    $dayId = $this->_dbHandle->lastInsertId();
                    
                    // Add times for this day
                    if (!empty($times)) {
                        foreach ($times as $time) {
                            $timeStatement = $this->_dbHandle->prepare(
                                "INSERT INTO Pro_AvailabilityTimes (availability_day_id, available_time)
                                 VALUES (:day_id, :time)"
                            );
                            
                            $timeStatement->bindParam(':day_id', $dayId, PDO::PARAM_INT);
                            $timeStatement->bindParam(':time', $time, PDO::PARAM_STR);
                            $timeStatement->execute();
                        }
                    }
                }
            }
            
            $this->_dbHandle->commit();
            return $chargePointId;
            
        } catch (PDOException $e) {
            $this->_dbHandle->rollBack();
            error_log("Database error in addChargePoint: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing charge point
     * @param array $data Charge point data
     * @param string|null $imageFile Path to the new image, or null if unchanged
     * @return bool Success or failure
     */
    public function updateChargePoint($data, $imageFile = null) {
        try {
            $this->_dbHandle->beginTransaction();
            
            // 1. Update charge point address
            $addressStatement = $this->_dbHandle->prepare(
                "UPDATE Pro_ChargePointAddress 
                 SET house_number = :house_number,
                     road = :road,
                     block = :block,
                     city_id = :city_id,
                     latitude = :latitude,
                     longitude = :longitude
                 WHERE charge_point_address_id = :address_id"
            );
            
            $addressStatement->bindParam(':house_number', $data['house_number'], PDO::PARAM_INT);
            $addressStatement->bindParam(':road', $data['road'], PDO::PARAM_INT);
            $addressStatement->bindParam(':block', $data['block'], PDO::PARAM_INT);
            $addressStatement->bindParam(':city_id', $data['city_id'], PDO::PARAM_INT);
            $addressStatement->bindParam(':latitude', $data['latitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':longitude', $data['longitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':address_id', $data['charge_point_address_id'], PDO::PARAM_INT);
            
            $addressStatement->execute();
            
            // 2. Update the charge point
            $chargePointSql = "UPDATE Pro_ChargePoint 
                              SET price_per_kwh = :price_per_kwh,
                                  availability_status_id = :availability_status";
            
            // Add image update only if a new one is provided
            if ($imageFile !== null) {
                $chargePointSql .= ", charge_point_picture_url = :picture_url";
            }
            
            $chargePointSql .= " WHERE charge_point_id = :charge_point_id AND user_id = :user_id";
            
            $chargePointStatement = $this->_dbHandle->prepare($chargePointSql);
            
            $chargePointStatement->bindParam(':price_per_kwh', $data['price_per_kwh'], PDO::PARAM_STR);
            $chargePointStatement->bindParam(':availability_status', $data['availability_status_id'], PDO::PARAM_INT);
            $chargePointStatement->bindParam(':charge_point_id', $data['charge_point_id'], PDO::PARAM_INT);
            $chargePointStatement->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
            
            if ($imageFile !== null) {
                $chargePointStatement->bindParam(':picture_url', $imageFile, PDO::PARAM_STR);
            }
            
            $chargePointStatement->execute();
            
            // 3. Delete existing availability days and times
            $deleteTimesStatement = $this->_dbHandle->prepare(
                "DELETE at FROM Pro_AvailabilityTimes at
                 JOIN Pro_AvailabilityDays ad ON at.availability_day_id = ad.availability_day_id
                 WHERE ad.charge_point_id = :charge_point_id"
            );
            $deleteTimesStatement->bindParam(':charge_point_id', $data['charge_point_id'], PDO::PARAM_INT);
            $deleteTimesStatement->execute();
            
            $deleteDaysStatement = $this->_dbHandle->prepare(
                "DELETE FROM Pro_AvailabilityDays WHERE charge_point_id = :charge_point_id"
            );
            $deleteDaysStatement->bindParam(':charge_point_id', $data['charge_point_id'], PDO::PARAM_INT);
            $deleteDaysStatement->execute();
            
            // 4. Add new availability days and times
            if (isset($data['availability_days']) && !empty($data['availability_days'])) {
                foreach ($data['availability_days'] as $day => $times) {
                    // Add day
                    $dayStatement = $this->_dbHandle->prepare(
                        "INSERT INTO Pro_AvailabilityDays (charge_point_id, day_of_week)
                         VALUES (:charge_point_id, :day_of_week)"
                    );
                    
                    $dayStatement->bindParam(':charge_point_id', $data['charge_point_id'], PDO::PARAM_INT);
                    $dayStatement->bindParam(':day_of_week', $day, PDO::PARAM_STR);
                    $dayStatement->execute();
                    
                    $dayId = $this->_dbHandle->lastInsertId();
                    
                    // Add times for this day
                    if (!empty($times)) {
                        foreach ($times as $time) {
                            $timeStatement = $this->_dbHandle->prepare(
                                "INSERT INTO Pro_AvailabilityTimes (availability_day_id, available_time)
                                 VALUES (:day_id, :time)"
                            );
                            
                            $timeStatement->bindParam(':day_id', $dayId, PDO::PARAM_INT);
                            $timeStatement->bindParam(':time', $time, PDO::PARAM_STR);
                            $timeStatement->execute();
                        }
                    }
                }
            }
            
            $this->_dbHandle->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->_dbHandle->rollBack();
            error_log("Database error in updateChargePoint: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a charge point
     * @param int $chargePointId
     * @return bool Success or failure
     */
    public function deleteChargePoint($chargePointId) {
        try {
            $this->_dbHandle->beginTransaction();
            
            // 1. Get the address ID
            $getAddressStatement = $this->_dbHandle->prepare(
                "SELECT charge_point_address_id FROM Pro_ChargePoint 
                 WHERE charge_point_id = :charge_point_id AND user_id = :user_id"
            );
            $getAddressStatement->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $getAddressStatement->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
            $getAddressStatement->execute();
            
            $addressId = $getAddressStatement->fetchColumn();
            
            if (!$addressId) {
                throw new Exception("Charge point not found or not owned by this user.");
            }
            
            // 2. Delete times
            $deleteTimesStatement = $this->_dbHandle->prepare(
                "DELETE at FROM Pro_AvailabilityTimes at
                 JOIN Pro_AvailabilityDays ad ON at.availability_day_id = ad.availability_day_id
                 WHERE ad.charge_point_id = :charge_point_id"
            );
            $deleteTimesStatement->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $deleteTimesStatement->execute();
            
            // 3. Delete days
            $deleteDaysStatement = $this->_dbHandle->prepare(
                "DELETE FROM Pro_AvailabilityDays WHERE charge_point_id = :charge_point_id"
            );
            $deleteDaysStatement->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $deleteDaysStatement->execute();
            
            // 4. Delete charge point
            $deleteChargePointStatement = $this->_dbHandle->prepare(
                "DELETE FROM Pro_ChargePoint 
                 WHERE charge_point_id = :charge_point_id AND user_id = :user_id"
            );
            $deleteChargePointStatement->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $deleteChargePointStatement->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
            $deleteChargePointStatement->execute();
            
            // 5. Delete address
            $deleteAddressStatement = $this->_dbHandle->prepare(
                "DELETE FROM Pro_ChargePointAddress WHERE charge_point_address_id = :address_id"
            );
            $deleteAddressStatement->bindParam(':address_id', $addressId, PDO::PARAM_INT);
            $deleteAddressStatement->execute();
            
            $this->_dbHandle->commit();
            return true;
            
        } catch (Exception $e) {
            $this->_dbHandle->rollBack();
            error_log("Error in deleteChargePoint: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload and save an image file
     * @param array $file The uploaded file data ($_FILES)
     * @return string|false The image URL or false on failure
     */
    public function uploadImage($file) {
        try {
            // Check for errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload error: " . $file['error']);
            }
            
            // Check file size (5MB limit)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                throw new Exception("File size exceeds limit of 5MB");
            }
            
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fileInfo, $file['tmp_name']);
            finfo_close($fileInfo);
            
            if (!in_array($mime, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPG and PNG are allowed.");
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = 'uploads/charge_points/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new Exception("Failed to create upload directory");
            }
            
            // Generate unique filename
            $filename = uniqid() . '_' . time() . '_' . basename($file['name']);
            $destination = $uploadDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception("Failed to move uploaded file");
            }
            
            return $destination;
            
        } catch (Exception $e) {
            error_log("Image upload error: " . $e->getMessage());
            return false;
        }
    }
}

