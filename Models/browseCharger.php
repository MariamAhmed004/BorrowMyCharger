<?php
require_once 'Database.php';

class BrowseCharger {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getDbConnection();
    }

    public function getChargers($page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                cp.charge_point_id AS chargePointId,
                cp.price_per_kwh AS pricePerKwh,
                cp.charge_point_picture_url AS chargePointPictureUrl,
                addr.charge_point_address_id AS chargePointAddressId,
                addr.postcode AS postcode,
                addr.latitude AS latitude,
                addr.longitude AS longitude,
                addr.streetName AS streetName,
                addr.house_number AS houseNumber,
                addr.road AS road,
                addr.block AS block,
                c.city_id AS cityId,
                c.city_name AS cityName,
                avail.availability_status_id AS availabilityStatusId,
                avail.availability_status_title AS availabilityStatusTitle
            FROM 
                Pro_ChargePoint cp
            LEFT JOIN 
                Pro_ChargePointAddress addr ON cp.charge_point_address_id = addr.charge_point_address_id
            LEFT JOIN 
                Pro_City c ON addr.city_id = c.city_id
            LEFT JOIN 
                Pro_AvailabilityStatus avail ON cp.availability_status_id = avail.availability_status_id
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredChargers($location, $priceRange, $availability, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                cp.charge_point_id AS chargePointId,
                cp.price_per_kwh AS pricePerKwh,
                cp.charge_point_picture_url AS chargePointPictureUrl,
                addr.charge_point_address_id AS chargePointAddressId,
                addr.postcode AS postcode,
                addr.latitude AS latitude,
                addr.longitude AS longitude,
                addr.streetName AS streetName,
                addr.house_number AS houseNumber,
                addr.road AS road,
                addr.block AS block,
                c.city_id AS cityId,
                c.city_name AS cityName,
                avail.availability_status_id AS availabilityStatusId,
                avail.availability_status_title AS availabilityStatusTitle
            FROM 
                Pro_ChargePoint cp
            LEFT JOIN 
                Pro_ChargePointAddress addr ON cp.charge_point_address_id = addr.charge_point_address_id
            LEFT JOIN 
                Pro_City c ON addr.city_id = c.city_id
            LEFT JOIN 
                Pro_AvailabilityStatus avail ON cp.availability_status_id = avail.availability_status_id
            WHERE 1=1
        ";

        if (!empty($location)) {
            $sql .= " AND c.city_id = :location";
        }

        if (!empty($priceRange)) {
            $priceRangeParts = explode('-', $priceRange);
            if (count($priceRangeParts) == 2) {
                $sql .= " AND cp.price_per_kwh BETWEEN :priceMin AND :priceMax";
            } elseif ($priceRange == '20+') {
                $sql .= " AND cp.price_per_kwh > 20";
            }
        }

        if (!empty($availability)) {
            $sql .= " AND avail.availability_status_id = :availability";
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        if (!empty($location)) {
            $stmt->bindParam(':location', $location, PDO::PARAM_INT);
        }

        if (!empty($priceRange) && $priceRangeParts = explode('-', $priceRange)) {
            if (count($priceRangeParts) == 2) {
                $stmt->bindParam(':priceMin', $priceRangeParts[0], PDO::PARAM_INT);
                $stmt->bindParam(':priceMax', $priceRangeParts[1], PDO::PARAM_INT);
            }
        }

        if (!empty($availability)) {
            $stmt->bindParam(':availability', $availability, PDO::PARAM_STR);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalChargers() {
        $sql = "SELECT COUNT(*) as total FROM Pro_ChargePoint";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getTotalFilteredChargers($location, $priceRange, $availability) {
        $sql = "
            SELECT 
                COUNT(*) as total
            FROM 
                Pro_ChargePoint cp
            LEFT JOIN 
                Pro_ChargePointAddress addr ON cp.charge_point_address_id = addr.charge_point_address_id
            LEFT JOIN 
                Pro_City c ON addr.city_id = c.city_id
            LEFT JOIN 
                Pro_AvailabilityStatus avail ON cp.availability_status_id = avail.availability_status_id
            WHERE 1=1
        ";

        if (!empty($location)) {
            $sql .= " AND c.city_id = :location";
        }

        if (!empty($priceRange)) {
            $priceRangeParts = explode('-', $priceRange);
            if (count($priceRangeParts) == 2) {
                $sql .= " AND cp.price_per_kwh BETWEEN :priceMin AND :priceMax";
            } elseif ($priceRange == '20+') {
                $sql .= " AND cp.price_per_kwh > 20";
            }
        }

        if (!empty($availability)) {
            $sql .= " AND avail.availability_status_id = :availability";
        }

        $stmt = $this->db->prepare($sql);

        if (!empty($location)) {
            $stmt->bindParam(':location', $location, PDO::PARAM_INT);
        }

        if (!empty($priceRange) && $priceRangeParts = explode('-', $priceRange)) {
            if (count($priceRangeParts) == 2) {
                $stmt->bindParam(':priceMin', $priceRangeParts[0], PDO::PARAM_INT);
                $stmt->bindParam(':priceMax', $priceRangeParts[1], PDO::PARAM_INT);
            }
        }

        if (!empty($availability)) {
            $stmt->bindParam(':availability', $availability, PDO::PARAM_STR);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getAvailabilityStatus() {
        $sql = "SELECT availability_status_id, availability_status_title FROM Pro_AvailabilityStatus";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                c.city_name AS cityName,
                ad.day_of_week,
                at.available_time
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
            'availableDays' => implode(',', $filteredDays),
            'availableTimes' => $availableTimes
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
}