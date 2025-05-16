<?php
require_once 'Database.php';
class BrowseCharger {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getDbConnection();
    }
    
      public function getChargers($page = 1, $limit = 8) {
        $offset = ($page - 1) * $limit;
        
  $query = "SELECT cp.charge_point_id as chargePointId, 
                 cp.price_per_kwh as pricePerKwh, 
                 cp.charge_point_picture_url as chargePointPictureUrl, 
                 cpa.postcode, 
                 cpa.streetName as streetName, 
                 cpa.house_number as houseNumber, 
                 cpa.road, 
                 cpa.block, 
                 c.city_name as cityName, 
                 ast.availability_status_id as availabilityStatusId,
                 ast.availability_status_title as availabilityStatusTitle 
          FROM Pro_ChargePoint cp 
          JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id 
          JOIN Pro_City c ON cpa.city_id = c.city_id 
          JOIN Pro_AvailabilityStatus ast ON cp.availability_status_id = ast.availability_status_id 
          ORDER BY cp.charge_point_id DESC 
          LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get filtered charge points
    public function getFilteredChargers($location = '', $priceRange = '', $availabilityStatus = '', $page = 1, $limit = 12, $locationQuery = '', $availabilityQuery = '') {
        $offset = ($page - 1) * $limit;
        $params = [];
        
       $query = "SELECT cp.charge_point_id as chargePointId, 
                 cp.price_per_kwh as pricePerKwh, 
                 cp.charge_point_picture_url as chargePointPictureUrl, 
                 cpa.postcode, 
                 cpa.streetName, 
                 cpa.house_number as houseNumber, 
                 cpa.road, 
                 cpa.block, 
                 c.city_name as cityName, 
                 ast.availability_status_id as availabilityStatusId,
                 ast.availability_status_title as availabilityStatusTitle 
          FROM Pro_ChargePoint cp 
          JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id 
          JOIN Pro_City c ON cpa.city_id = c.city_id 
          JOIN Pro_AvailabilityStatus ast ON cp.availability_status_id = ast.availability_status_id 
          WHERE 1=1";
        
        // Filter by location (city)
        if (!empty($location)) {
            $query .= " AND cpa.city_id = :cityId";
            $params[':cityId'] = $location;
        }
        
        // Filter by location search query (city name)
        if (!empty($locationQuery)) {
            $query .= " AND LOWER(c.city_name) LIKE :locationQuery";
            $params[':locationQuery'] = '%' . strtolower($locationQuery) . '%';
        }
        
        // Filter by price range
        if (!empty($priceRange)) {
            list($min, $max) = explode('-', $priceRange);
            $query .= " AND cp.price_per_kwh BETWEEN :minPrice AND :maxPrice";
            $params[':minPrice'] = $min;
            $params[':maxPrice'] = $max;
        }
        
        // Filter by availability status
        if (!empty($availabilityStatus)) {
            $query .= " AND cp.availability_status_id = :availabilityStatusId";
            $params[':availabilityStatusId'] = $availabilityStatus;
        }
        
        // Filter by availability search query
        if (!empty($availabilityQuery)) {
            $query .= " AND LOWER(ast.availability_status_title) LIKE :availabilityQuery";
            $params[':availabilityQuery'] = '%' . strtolower($availabilityQuery) . '%';
        }
        
        $query .= " ORDER BY cp.charge_point_id DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
     // Get total number of charge points
    public function getTotalChargers() {
        $query = "SELECT COUNT(*) as total FROM Pro_ChargePoint";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // Get availability status options
    public function getAvailabilityStatus() {
        $query = "SELECT availability_status_id, availability_status_title FROM Pro_AvailabilityStatus";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
     // Get total number of filtered charge points (for pagination)
    public function getTotalFilteredChargers($location = '', $priceRange = '', $availabilityStatus = '', $locationQuery = '', $availabilityQuery = '') {
        $params = [];
        
        $query = "SELECT COUNT(*) as total
                FROM Pro_ChargePoint cp 
                JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id 
                JOIN Pro_City c ON cpa.city_id = c.city_id 
                JOIN Pro_AvailabilityStatus ast ON cp.availability_status_id = ast.availability_status_id 
                WHERE 1=1";
        
        // Filter by location (city)
        if (!empty($location)) {
            $query .= " AND cpa.city_id = :cityId";
            $params[':cityId'] = $location;
        }
        
        // Filter by location search query (city name)
        if (!empty($locationQuery)) {
            $query .= " AND LOWER(c.city_name) LIKE :locationQuery";
            $params[':locationQuery'] = '%' . strtolower($locationQuery) . '%';
        }
        
        // Filter by price range
        if (!empty($priceRange)) {
            list($min, $max) = explode('-', $priceRange);
            $query .= " AND cp.price_per_kwh BETWEEN :minPrice AND :maxPrice";
            $params[':minPrice'] = $min;
            $params[':maxPrice'] = $max;
        }
        
        // Filter by availability status
        if (!empty($availabilityStatus)) {
            $query .= " AND cp.availability_status_id = :availabilityStatusId";
            $params[':availabilityStatusId'] = $availabilityStatus;
        }
        
        // Filter by availability search query
        if (!empty($availabilityQuery)) {
            $query .= " AND LOWER(ast.availability_status_title) LIKE :availabilityQuery";
            $params[':availabilityQuery'] = '%' . strtolower($availabilityQuery) . '%';
        }
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
   
 public function getChargerById($id) {
    $sql = "
    SELECT
        cp.charge_point_id AS chargePointId,
        cp.price_per_kwh AS pricePerKwh,
        cp.charge_point_picture_url AS chargePointPictureUrl,
        addr.house_number AS houseNumber,
        addr.streetName AS streetName,
        addr.road AS road,
        addr.postcode AS postcode,
        addr.latitude AS latitude,
        addr.longitude AS longitude,
        c.city_name AS cityName,
        ad.day_of_week,
        at.available_time,
        CONCAT(u.first_name, ' ', u.last_name) AS homeownerFullName,
        u.phone_number AS homeownerPhone,
        u.email AS homeownerEmail
    FROM
        Pro_ChargePoint cp
    LEFT JOIN
        Pro_ChargePointAddress addr ON cp.charge_point_address_id = addr.charge_point_address_id
    LEFT JOIN
        Pro_City c ON addr.city_id = c.city_id
    LEFT JOIN
        Pro_AvailabilityDays ad ON cp.charge_point_id = ad.charge_point_id
    LEFT JOIN
        Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
    LEFT JOIN
        Pro_User u ON cp.user_id = u.user_id
    WHERE
        cp.charge_point_id = :id
    ORDER BY
        FIELD(ad.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
        at.available_time ASC";
        
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $availabilityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $availableDays = [];
    $availableTimes = [];
    
    foreach ($availabilityData as $item) {
        $day = $item['day_of_week'];
        $time = $item['available_time'];
        
        if (!in_array($day, $availableDays)) {
            $availableDays[] = $day;
        }
        
        if (!isset($availableTimes[$day])) {
            $availableTimes[$day] = [];
        }
        
        if ($time) {
            $availableTimes[$day][] = $time;
        }
    }
    
    // Only include days that have at least one available time
    $filteredDays = array_filter($availableDays, function($day) use ($availableTimes) {
        return !empty($availableTimes[$day]);
    });
    
    return [
        'chargePointId' => $availabilityData[0]['chargePointId'] ?? '',
        'pricePerKwh' => $availabilityData[0]['pricePerKwh'] ?? '',
        'chargePointPictureUrl' => $availabilityData[0]['chargePointPictureUrl'] ?? '',
        'houseNumber' => $availabilityData[0]['houseNumber'] ?? '',
        'streetName' => $availabilityData[0]['streetName'] ?? '',
        'road' => $availabilityData[0]['road'] ?? '',
        'postcode' => $availabilityData[0]['postcode'] ?? '',
        'cityName' => $availabilityData[0]['cityName'] ?? '',
        'latitude' => $availabilityData[0]['latitude'] ?? '',
        'longitude' => $availabilityData[0]['longitude'] ?? '',
        'availableDays' => implode(',', $filteredDays),
        'availableTimes' => $availableTimes,
        'homeownerFullName' => $availabilityData[0]['homeownerFullName'] ?? '',
        'homeownerPhone' => $availabilityData[0]['homeownerPhone'] ?? '',
        'homeownerEmail' => $availabilityData[0]['homeownerEmail'] ?? ''
    ];
}
    
    public function getChargerAvailability($id) {
        $sql = "
        SELECT
            ad.day_of_week,
            at.available_time
        FROM
            Pro_AvailabilityDays ad
        LEFT JOIN
            Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
        WHERE
            ad.charge_point_id = :id
        ORDER BY
            FIELD(ad.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
            at.available_time ASC";
            
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get the availability data for a charger
     * 
     * @param int $chargePointId The charge point ID
     * @return array Availability data
     */
    public function getChargerAvailabilityDays($chargePointId) {
        $sql = "SELECT ad.day_of_week, at.available_time 
                FROM Pro_AvailabilityDays ad
                JOIN Pro_AvailabilityTimes at ON ad.availability_day_id = at.availability_day_id
                WHERE ad.charge_point_id = :charge_point_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
        /**
     * Get booked slots for a specific charger
     * 
     * @param int $chargePointId The charge point ID
     * @return array Booked slots
     */
    public function getBookedSlots($chargePointId) {
        $sql = "SELECT DATE_FORMAT(booking_date, '%Y-%m-%d') as booked_date, booking_time 
                FROM Pro_Booking 
                WHERE charge_point_id = :charge_point_id 
                AND (booking_status_id = 1 OR booking_status_id = 2)"; // Pending or Approved
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':charge_point_id', $chargePointId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create associative array of booked slots
        $bookedSlots = [];
        foreach ($results as $result) {
            if (!isset($bookedSlots[$result['booked_date']])) {
                $bookedSlots[$result['booked_date']] = [];
            }
            $bookedSlots[$result['booked_date']][] = $result['booking_time'];
        }
        
        return $bookedSlots;
    }
    
     /**
     * Get status information for multiple charge points
     * 
     * @param array $chargePointIds Array of charge point IDs
     * @return array Status data for each charge point
     */
    public function getChargePointStatuses($chargePointIds) {
        $statusData = [];
        
        if (empty($chargePointIds)) {
            return $statusData;
        }
        
        // Convert array of IDs into placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($chargePointIds), '?'));
        
        $query = "SELECT cp.charge_point_id as chargePointId, 
                ast.availability_status_id as availabilityStatusId,
                ast.availability_status_title as availabilityStatusTitle 
            FROM Pro_ChargePoint cp 
            JOIN Pro_AvailabilityStatus ast ON cp.availability_status_id = ast.availability_status_id 
            WHERE cp.charge_point_id IN ($placeholders)";
        
        $stmt = $this->db->prepare($query);
        
        // Bind each ID as a parameter
        foreach ($chargePointIds as $index => $id) {
            $stmt->bindValue($index + 1, (int)$id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the results into an associative array by ID
        foreach ($results as $result) {
            $id = $result['chargePointId'];
            $statusData[$id] = [
                'status' => $result['availabilityStatusTitle'],
                'statusId' => $result['availabilityStatusId'],
                'statusClass' => strtolower(str_replace(' ', '-', $result['availabilityStatusTitle'] ?? ''))
            ];
        }
        
        return $statusData;
    }
}