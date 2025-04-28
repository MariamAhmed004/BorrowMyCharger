<?php
require_once 'Database.php';

class BrowseCharger {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getDbConnection();
    }

    public function getChargers() {
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
                co.country_id AS countryId,
                co.country_name AS countryName,
                avail.availability_status_id AS availabilityStatusId,
                avail.availability_status_title AS availabilityStatusTitle
            FROM 
                Pro_ChargePoint cp
            LEFT JOIN 
                Pro_ChargePointAddress addr ON cp.charge_point_address_id = addr.charge_point_address_id
            LEFT JOIN 
                Pro_City c ON addr.city_id = c.city_id
            LEFT JOIN 
                Pro_Country co ON c.country_id = co.country_id
            LEFT JOIN 
                Pro_AvailabilityStatus avail ON cp.availability_status_id = avail.availability_status_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredChargers($location, $priceRange, $availability) {
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
                co.country_id AS countryId,
                co.country_name AS countryName,
                avail.availability_status_id AS availabilityStatusId,
                avail.availability_status_title AS availabilityStatusTitle
            FROM 
                Pro_ChargePoint cp
            LEFT JOIN 
                Pro_ChargePointAddress addr ON cp.charge_point_address_id = addr.charge_point_address_id
            LEFT JOIN 
                Pro_City c ON addr.city_id = c.city_id
            LEFT JOIN 
                Pro_Country co ON c.country_id = co.country_id
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

        if (!empty($priceRange) && count($priceRangeParts) == 2) {
            $stmt->bindParam(':priceMin', $priceRangeParts[0], PDO::PARAM_INT);
            $stmt->bindParam(':priceMax', $priceRangeParts[1], PDO::PARAM_INT);
        }

        if (!empty($availability)) {
            $stmt->bindParam(':availability', $availability, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAvailabilityStatus() {
    // SQL query to retrieve all availability statuses from the Pro_AvailabilityStatus table
    $sql = "SELECT availability_status_id, availability_status_title FROM Pro_AvailabilityStatus";

    // Prepare the SQL query
    $stmt = $this->db->prepare($sql);
    // Execute the query
    $stmt->execute();

    // Return the results as an associative array
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
?>
