<?php
require_once('Database.php');

class MyChargePointModel {
    private $dbConnection;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->dbConnection = $database->getDbConnection();
    }
    
public function getFeaturedChargePoints($limit = 3) {
    $sql = "
        SELECT 
            cp.charge_point_id,
            cp.price_per_kwh,
            cp.charge_point_picture_url,
            u.first_name,
            u.last_name,
            u.phone_number,
            cpa.streetName,
            cpa.house_number,
            c.city_name,
            avs.availability_status_title
        FROM 
            Pro_ChargePoint cp 
        JOIN 
            Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
        JOIN 
            Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
        JOIN 
            Pro_User u ON cp.user_id = u.user_id
        JOIN 
            Pro_City c ON cpa.city_id = c.city_id
        ORDER BY 
            cp.charge_point_id DESC
        LIMIT :limit";

    $statement = $this->dbConnection->prepare($sql);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
    

    public function getChargePointDetails($minPrice = null, $maxPrice = null) {
        $sql = "
            SELECT 
                cp.charge_point_id,
                cpa.latitude,
                cpa.longitude,
                cp.price_per_kwh,
                cp.charge_point_picture_url,
                avs.availability_status_title,
                cpa.streetName,
                cpa.house_number,
                cpa.road,
                cpa.block,
                GROUP_CONCAT(DISTINCT ad.day_of_week) AS available_days,
                GROUP_CONCAT(DISTINCT at.available_time ORDER BY at.available_time) AS available_times
            FROM 
                Pro_ChargePoint cp 
            JOIN 
                Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
            JOIN 
                Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
            LEFT JOIN 
                Pro_AvailabilityDays ad ON cp.charge_point_id = ad.charge_point_id
            LEFT JOIN 
                Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
            WHERE 
                (:minPrice IS NULL OR cp.price_per_kwh >= :minPrice) AND
                (:maxPrice IS NULL OR cp.price_per_kwh <= :maxPrice)
            GROUP BY 
                cp.charge_point_id, cpa.latitude, cpa.longitude, cp.price_per_kwh, cp.charge_point_picture_url, 
                avs.availability_status_title, cpa.streetName, cpa.house_number, cpa.road, cpa.block";

        $statement = $this->dbConnection->prepare($sql);
        $statement->bindValue(':minPrice', $minPrice, PDO::PARAM_INT);
        $statement->bindValue(':maxPrice', $maxPrice, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
public function getAvailableChargePoints($minPrice = null, $maxPrice = null) {
    $sql = "
        SELECT 
            cp.charge_point_id,
            cpa.latitude,
            cpa.longitude,
            cp.price_per_kwh,
            cp.charge_point_picture_url,
            avs.availability_status_title,
            cpa.streetName,
            cpa.house_number,
            cpa.road,
            cpa.block,
            GROUP_CONCAT(DISTINCT ad.day_of_week) AS available_days,
            GROUP_CONCAT(DISTINCT at.available_time ORDER BY at.available_time) AS available_times
        FROM 
            Pro_ChargePoint cp 
        JOIN 
            Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
        JOIN 
            Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
        LEFT JOIN 
            Pro_AvailabilityDays ad ON cp.charge_point_id = ad.charge_point_id
        LEFT JOIN 
            Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
        WHERE 
            avs.availability_status_title = 'Available' AND
            (:minPrice IS NULL OR cp.price_per_kwh >= :minPrice) AND
            (:maxPrice IS NULL OR cp.price_per_kwh <= :maxPrice)
        GROUP BY 
            cp.charge_point_id, cpa.latitude, cpa.longitude, cp.price_per_kwh, cp.charge_point_picture_url, 
            avs.availability_status_title, cpa.streetName, cpa.house_number, cpa.road, cpa.block";

    $statement = $this->dbConnection->prepare($sql);
    $statement->bindValue(':minPrice', $minPrice, PDO::PARAM_INT);
    $statement->bindValue(':maxPrice', $maxPrice, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
public function getUnAvailableChargePoints($minPrice = null, $maxPrice = null) {
    $sql = "
        SELECT 
            cp.charge_point_id,
            cpa.latitude,
            cpa.longitude,
            cp.price_per_kwh,
            cp.charge_point_picture_url,
            avs.availability_status_title,
            cpa.streetName,
            cpa.house_number,
            cpa.road,
            cpa.block,
            GROUP_CONCAT(DISTINCT ad.day_of_week) AS available_days,
            GROUP_CONCAT(DISTINCT at.available_time ORDER BY at.available_time) AS available_times
        FROM 
            Pro_ChargePoint cp 
        JOIN 
            Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
        JOIN 
            Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
        LEFT JOIN 
            Pro_AvailabilityDays ad ON cp.charge_point_id = ad.charge_point_id
        LEFT JOIN 
            Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
        WHERE 
            avs.availability_status_title = 'UnAvailable' AND
            (:minPrice IS NULL OR cp.price_per_kwh >= :minPrice) AND
            (:maxPrice IS NULL OR cp.price_per_kwh <= :maxPrice)
        GROUP BY 
            cp.charge_point_id, cpa.latitude, cpa.longitude, cp.price_per_kwh, cp.charge_point_picture_url, 
            avs.availability_status_title, cpa.streetName, cpa.house_number, cpa.road, cpa.block";

    $statement = $this->dbConnection->prepare($sql);
    $statement->bindValue(':minPrice', $minPrice, PDO::PARAM_INT);
    $statement->bindValue(':maxPrice', $maxPrice, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

  public function getUserChargePoint($userId) {
    $sql = "SELECT cp.*, cpa.*, c.city_name, avs.availability_status_title, 
                   ad.day_of_week, at.available_time
            FROM Pro_ChargePoint cp 
            JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id 
            JOIN Pro_City c ON cpa.city_id = c.city_id
            JOIN Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
            LEFT JOIN Pro_AvailabilityDays ad ON cp.charge_point_id = ad.charge_point_id
            LEFT JOIN Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
            WHERE cp.user_id = :user_id";
                
    $statement = $this->dbConnection->prepare($sql);
    $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $statement->execute();

    $chargePoints = [];
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $chargePointId = $row['charge_point_id'];

        if (!isset($chargePoints[$chargePointId])) {
            $chargePoints[$chargePointId] = $row;
            $chargePoints[$chargePointId]['availability_days'] = []; // Initialize
        }

        if ($row['day_of_week']) {
            $chargePoints[$chargePointId]['availability_days'][] = [
                'day_of_week' => $row['day_of_week'],
                'times' => [] // Initialize times array
            ];
        }
        
        if ($row['available_time']) {
            $lastIndex = count($chargePoints[$chargePointId]['availability_days']) - 1;
            if ($lastIndex >= 0) {
                $chargePoints[$chargePointId]['availability_days'][$lastIndex]['times'][] = [
                    'available_time' => $row['available_time']
                ];
            }
        }
    }

    return array_values($chargePoints); // Reset array keys
}
    // Get all cities from the database
    public function getAllCities() {
        $sql = "SELECT * FROM Pro_City ORDER BY city_name";
        $statement = $this->dbConnection->prepare($sql);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get availability status options
    public function getAvailabilityStatuses() {
        $sql = "SELECT * FROM Pro_AvailabilityStatus";
        $statement = $this->dbConnection->prepare($sql);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get charge point availability
    public function getChargePointAvailability($chargePointId) {
        $sqlDays = "SELECT ad.availability_day_id, ad.day_of_week, 
                     GROUP_CONCAT(at.available_time ORDER BY at.available_time ASC) as times
                     FROM Pro_AvailabilityDays ad
                     LEFT JOIN Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
                     WHERE ad.charge_point_id = :charge_point_id
                     GROUP BY ad.availability_day_id, ad.day_of_week
                     ORDER BY FIELD(ad.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
        
        $stmtDays = $this->dbConnection->prepare($sqlDays);
        $stmtDays->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmtDays->execute();
        
        $availability = [];
        while ($day = $stmtDays->fetch(PDO::FETCH_ASSOC)) {
            $availability[$day['day_of_week']] = explode(',', $day['times']);
        }
        
        return $availability;
    }
    
    // Check if charge point has available time slots
    private function hasAvailableTimeSlots($chargePointId) {
        $sql = "SELECT COUNT(*) as slot_count 
                FROM Pro_AvailabilityDays ad
                JOIN Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
                WHERE ad.charge_point_id = :charge_point_id";
        
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['slot_count'] > 0);
    }
    
    // Update charge point availability status based on time slots
    private function updateAvailabilityStatus($chargePointId) {
        $hasSlots = $this->hasAvailableTimeSlots($chargePointId);
        $availabilityStatusId = $hasSlots ? 1 : 2; // 1 = Available, 2 = Unavailable
        
        $sql = "UPDATE Pro_ChargePoint 
                SET availability_status_id = :availability_status_id 
                WHERE charge_point_id = :charge_point_id";
        
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->bindParam(':availability_status_id', $availabilityStatusId, PDO::PARAM_INT);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $availabilityStatusId;
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
    
    // Delete all availability for a charge point
    public function deleteChargePointAvailability($chargePointId) {
        try {
            // First, delete all times for this charge point's days
            $sqlDeleteTimes = "DELETE FROM Pro_AvailabilityTimes 
                              WHERE availability_day_id IN (
                                  SELECT availability_day_id FROM Pro_AvailabilityDays 
                                  WHERE charge_point_id = :charge_point_id
                              )";
            $stmtDeleteTimes = $this->dbConnection->prepare($sqlDeleteTimes);
            $stmtDeleteTimes->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $stmtDeleteTimes->execute();
            
            // Then delete the days
            $sqlDeleteDays = "DELETE FROM Pro_AvailabilityDays WHERE charge_point_id = :charge_point_id";
            $stmtDeleteDays = $this->dbConnection->prepare($sqlDeleteDays);
            $stmtDeleteDays->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $stmtDeleteDays->execute();
            
            // Update availability status to Unavailable (2)
            $sql = "UPDATE Pro_ChargePoint SET availability_status_id = 2 WHERE charge_point_id = :charge_point_id";
            $stmt = $this->dbConnection->prepare($sql);
            $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    // Add a new charge point
    public function addChargePoint($userId, $data) {
        try {
            $this->dbConnection->beginTransaction();
            
            // First, insert the address
            $addressSql = "INSERT INTO Pro_ChargePointAddress (postcode, latitude, longitude, streetName, city_id, house_number, road, block) 
                          VALUES (:postcode, :latitude, :longitude, :streetName, :city_id, :house_number, :road, :block)";
            
            $addressStatement = $this->dbConnection->prepare($addressSql);
            $addressStatement->bindParam(':postcode', $data['postcode'], PDO::PARAM_STR);
            $addressStatement->bindParam(':latitude', $data['latitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':longitude', $data['longitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':streetName', $data['streetName'], PDO::PARAM_STR);
            $addressStatement->bindParam(':city_id', $data['city_id'], PDO::PARAM_INT);
            $addressStatement->bindParam(':house_number', $data['house_number'], PDO::PARAM_INT);
            $addressStatement->bindParam(':road', $data['road'], PDO::PARAM_INT);
            $addressStatement->bindParam(':block', $data['block'], PDO::PARAM_INT);
            $addressStatement->execute();
            
            $addressId = $this->dbConnection->lastInsertId();
            
            // Default to Unavailable (2) since no time slots exist yet
            $availabilityStatusId = 2;
            
            // Then insert the charge point
            $chargePointSql = "INSERT INTO Pro_ChargePoint (price_per_kwh, charge_point_picture_url, user_id, charge_point_address_id, availability_status_id) 
                              VALUES (:price_per_kwh, :charge_point_picture_url, :user_id, :charge_point_address_id, :availability_status_id)";
            
            $chargePointStatement = $this->dbConnection->prepare($chargePointSql);
            $chargePointStatement->bindParam(':price_per_kwh', $data['price_per_kwh'], PDO::PARAM_STR);
            $chargePointStatement->bindParam(':charge_point_picture_url', $data['charge_point_picture_url'], PDO::PARAM_STR);
            $chargePointStatement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $chargePointStatement->bindParam(':charge_point_address_id', $addressId, PDO::PARAM_INT);
            $chargePointStatement->bindParam(':availability_status_id', $availabilityStatusId, PDO::PARAM_INT);
            
            $chargePointStatement->execute();
            
            $chargePointId = $this->dbConnection->lastInsertId();
            
            $this->dbConnection->commit();
            
            return $chargePointId;
        } catch (PDOException $e) {
            $this->dbConnection->rollBack();
            throw $e;
        }
    }
  
    // Update existing charge point
    public function updateChargePoint($chargePointId, $data) {
        try {
            $this->dbConnection->beginTransaction();
            
            // Get current charge point data
            $sql = "SELECT charge_point_address_id FROM Pro_ChargePoint WHERE charge_point_id = :charge_point_id";
            $statement = $this->dbConnection->prepare($sql);
            $statement->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
            $statement->execute();
            $chargePoint = $statement->fetch(PDO::FETCH_ASSOC);
            
            if (!$chargePoint) {
                throw new Exception('Charge point not found');
            }
            
            $addressId = $chargePoint['charge_point_address_id'];
            
            // Update address
            $addressSql = "UPDATE Pro_ChargePointAddress 
                          SET postcode = :postcode, 
                              latitude = :latitude, 
                              longitude = :longitude, 
                              streetName = :streetName, 
                              city_id = :city_id, 
                              house_number = :house_number, 
                              road = :road, 
                              block = :block 
                          WHERE charge_point_address_id = :address_id";
            
            $addressStatement = $this->dbConnection->prepare($addressSql);
            $addressStatement->bindParam(':postcode', $data['postcode'], PDO::PARAM_STR);
            $addressStatement->bindParam(':latitude', $data['latitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':longitude', $data['longitude'], PDO::PARAM_STR);
            $addressStatement->bindParam(':streetName', $data['streetName'], PDO::PARAM_STR);
            $addressStatement->bindParam(':city_id', $data['city_id'], PDO::PARAM_INT);
            $addressStatement->bindParam(':house_number', $data['house_number'], PDO::PARAM_INT);
            $addressStatement->bindParam(':road', $data['road'], PDO::PARAM_INT);
            $addressStatement->bindParam(':block', $data['block'], PDO::PARAM_INT);
            $addressStatement->bindParam(':address_id', $addressId, PDO::PARAM_INT);
            $addressStatement->execute();
            
            // Update charge point
            $chargePointSql = "UPDATE Pro_ChargePoint SET price_per_kwh = :price_per_kwh";
            $params = [
                ':price_per_kwh' => $data['price_per_kwh'],
                ':charge_point_id' => $chargePointId
            ];
            
            // Check if image URL was provided
            if (!empty($data['charge_point_picture_url'])) {
                $chargePointSql .= ", charge_point_picture_url = :charge_point_picture_url";
                $params[':charge_point_picture_url'] = $data['charge_point_picture_url'];
            }
            
            $chargePointSql .= " WHERE charge_point_id = :charge_point_id";
            
            $chargePointStatement = $this->dbConnection->prepare($chargePointSql);
            foreach ($params as $key => $value) {
                $chargePointStatement->bindValue($key, $value);
            }
            $chargePointStatement->execute();
            
            // Update availability status based on time slots
            $this->updateAvailabilityStatus($chargePointId);
            
            $this->dbConnection->commit();
            
            return true;
        } catch (Exception $e) {
            $this->dbConnection->rollBack();
            throw $e;
        }
    }
 public function getHomeOwnerChargePointById($chargePointIdParam) {
    $sql = "SELECT cp.*, cpa.*, c.city_name, avs.availability_status_title, 
                   ad.day_of_week, at.available_time
            FROM Pro_ChargePoint cp 
            JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id 
            JOIN Pro_City c ON cpa.city_id = c.city_id
            JOIN Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
            LEFT JOIN Pro_AvailabilityDays ad ON cp.charge_point_id = ad.charge_point_id
            LEFT JOIN Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
            WHERE cp.charge_point_id = :charge_point_id";

    $statement = $this->dbConnection->prepare($sql);
    $statement->bindParam(':charge_point_id', $chargePointIdParam, PDO::PARAM_INT);
    $statement->execute();

    $chargePoint = [];

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        if (empty($chargePoint)) {
            $chargePoint = [
                'charge_point_id' => $row['charge_point_id'],
                'price_per_kwh' => $row['price_per_kwh'],
                'charge_point_picture_url' => $row['charge_point_picture_url'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'streetName' => $row['streetName'],
                'postcode' => $row['postcode'],
                'house_number' => $row['house_number'],
                'block' => $row['block'],
                'road' => $row['road'],
                'city_name' => $row['city_name'],
                'availability_status_title' => $row['availability_status_title'],
                'availability_days' => []
            ];
        }

        if (!empty($row['day_of_week'])) {
            $dayKey = $row['day_of_week'];
            if (!isset($chargePoint['availability_days'][$dayKey])) {
                $chargePoint['availability_days'][$dayKey] = [
                    'day_of_week' => $row['day_of_week'],
                    'times' => []
                ];
            }

            if (!empty($row['available_time'])) {
                $chargePoint['availability_days'][$dayKey]['times'][] = [
                    'available_time' => $row['available_time']
                ];
            }
        }
    }

    // Reset keys of availability_days
    if (isset($chargePoint['availability_days'])) {
        $chargePoint['availability_days'] = array_values($chargePoint['availability_days']);
    }

    return $chargePoint;
}

public function adminGetAllChargePoints() {
    try {
        $stmt = $this->dbConnection->prepare("
            SELECT 
                cp.charge_point_id, 
                cp.price_per_kwh, 
                cp.charge_point_picture_url, 
                u.username AS owner, 
                avs.availability_status_title AS availability, 
                ca.streetName 
            FROM 
                Pro_ChargePoint cp
            JOIN 
                Pro_User u ON cp.user_id = u.user_id
            JOIN 
                Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
            JOIN 
                Pro_ChargePointAddress ca ON cp.charge_point_address_id = ca.charge_point_address_id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle the error, e.g., logging
        echo "Database error: " . $e->getMessage();
        return [];
    }
}

   public function deleteChargePoint($chargePointId) {
    try {
        $this->dbConnection->beginTransaction();

        // Get address ID
        $sql = "SELECT charge_point_address_id FROM Pro_ChargePoint WHERE charge_point_id = :charge_point_id";
        $statement = $this->dbConnection->prepare($sql);
        $statement->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $statement->execute();
        
        $chargePoint = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$chargePoint) {
            throw new Exception('Charge point not found');
        }
        
        $addressId = $chargePoint['charge_point_address_id'];
        
        // Delete bookings related to this charge point
        $sqlDeleteBookings = "DELETE FROM Pro_Booking WHERE charge_point_id = :charge_point_id";
        $stmtDeleteBookings = $this->dbConnection->prepare($sqlDeleteBookings);
        $stmtDeleteBookings->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmtDeleteBookings->execute();
        
        // Delete availability times and days
        $this->deleteChargePointAvailability($chargePointId);
        
        // Delete charge point
        $sqlDeleteCP = "DELETE FROM Pro_ChargePoint WHERE charge_point_id = :charge_point_id";
        $stmtDeleteCP = $this->dbConnection->prepare($sqlDeleteCP);
        $stmtDeleteCP->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmtDeleteCP->execute();
        
        // Delete address
        $sqlDeleteAddr = "DELETE FROM Pro_ChargePointAddress WHERE charge_point_address_id = :address_id";
        $stmtDeleteAddr = $this->dbConnection->prepare($sqlDeleteAddr);
        $stmtDeleteAddr->bindParam(':address_id', $addressId, PDO::PARAM_INT);
        $stmtDeleteAddr->execute();
        
        $this->dbConnection->commit();
        return true;
    } catch (Exception $e) {
        $this->dbConnection->rollBack();
        throw $e;
    }
}
     public function getAllChargePoint() {
    $sql = "SELECT cp.*, cpa.*, c.city_name, avs.availability_status_title, 
                   ad.day_of_week, at.available_time
            FROM Pro_ChargePoint cp 
            JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id 
            JOIN Pro_City c ON cpa.city_id = c.city_id
            JOIN Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
            LEFT JOIN Pro_AvailabilityDays ad ON cp.charge_point_id = ad.charge_point_id
            LEFT JOIN Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id";
                
    $statement = $this->dbConnection->prepare($sql);
    $statement->execute();

    $chargePoints = [];
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $chargePointId = $row['charge_point_id'];

        if (!isset($chargePoints[$chargePointId])) {
            $chargePoints[$chargePointId] = $row;
            $chargePoints[$chargePointId]['availability_days'] = []; // Initialize
        }

        if ($row['day_of_week']) {
            $chargePoints[$chargePointId]['availability_days'][] = [
                'day_of_week' => $row['day_of_week'],
                'times' => [] // Initialize times array
            ];
        }
        
        if ($row['available_time']) {
            $lastIndex = count($chargePoints[$chargePointId]['availability_days']) - 1;
            if ($lastIndex >= 0) {
                $chargePoints[$chargePointId]['availability_days'][$lastIndex]['times'][] = [
                    'available_time' => $row['available_time']
                ];
            }
        }
    }

    return array_values($chargePoints); // Reset array keys
}

 
   public function getHomeOwnersWithoutChargePoints() {
    $query = "SELECT u.* FROM Pro_User u
              LEFT JOIN Pro_ChargePoint cp ON u.user_id = cp.user_id
              WHERE u.role_id = 2 AND cp.charge_point_id IS NULL
              AND u.user_account_status_id = 1";
              
    $statement = $this->dbConnection->prepare($query);
    
    try {
        $statement->execute();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
    // Add charge point for a specific user (admin functionality)
    public function adminAddChargePoint($userId, $data) {
        return $this->addChargePoint($userId, $data);
    }
 public function getChargePointById($chargePointId) {
        $stmt = $this->dbConnection->prepare("SELECT * FROM Pro_ChargePoint WHERE charge_point_id = :id");
        $stmt->bindParam(':id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
        $chargePoint = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch associated address details
        if ($chargePoint) {
            $addressStmt = $this->dbConnection->prepare("SELECT * FROM Pro_ChargePointAddress WHERE charge_point_address_id = :address_id");
            $addressStmt->bindParam(':address_id', $chargePoint['charge_point_address_id'], PDO::PARAM_INT);
            $addressStmt->execute();
            $address = $addressStmt->fetch(PDO::FETCH_ASSOC);

            if ($address) {
                $chargePoint['address'] = $address; // Combine address with charge point data
            }
        }

        return $chargePoint;
    }


    public function getAvailabilityDays($chargePointId) {
        $sql = "SELECT * FROM Pro_AvailabilityDays WHERE charge_point_id = ?";
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute([$chargePointId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailabilityTimes($chargePointId) {
        $sql = "
            SELECT at.* 
            FROM Pro_AvailabilityTimes at
            JOIN Pro_AvailabilityDays ad ON at.availability_day_id = ad.availability_day_id
            WHERE ad.charge_point_id = ?";
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute([$chargePointId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}