<?php
require_once 'Models/Database.php';

class SystemReportsModel {
    protected $_dbHandle;
    
    public function __construct() {
       $this->_dbHandle = Database::getInstance()->getDbConnection();
    }

    /**
     * Get all bookings with user and charge point details
     * @return array All bookings with related data
     */
    public function getAllBookings() {
        $sqlQuery = "SELECT b.booking_id, b.booking_date, b.booking_time, 
                    u.user_id, u.first_name, u.last_name, u.email, u.phone_number,
                    cp.charge_point_id, cp.price_per_kwh,
                    cpa.streetName, cpa.postcode,
                    c.city_name,
                    bs.booking_status_title
                    FROM Pro_Booking b
                    JOIN Pro_User u ON b.user_id = u.user_id
                    JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                    JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                    JOIN Pro_City c ON cpa.city_id = c.city_id
                    JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
                    ORDER BY b.booking_date DESC";
        
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get bookings filtered by status
     * @param int $statusId The booking status ID to filter by
     * @return array Filtered bookings
     */
    public function getBookingsByStatus($statusId) {
        $sqlQuery = "SELECT b.booking_id, b.booking_date, b.booking_time, 
                    u.user_id, u.first_name, u.last_name, u.email,
                    cp.charge_point_id, cp.price_per_kwh,
                    cpa.streetName, cpa.postcode,
                    c.city_name,
                    bs.booking_status_title
                    FROM Pro_Booking b
                    JOIN Pro_User u ON b.user_id = u.user_id
                    JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                    JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                    JOIN Pro_City c ON cpa.city_id = c.city_id
                    JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
                    WHERE b.booking_status_id = :statusId
                    ORDER BY b.booking_date DESC";
        
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindParam(":statusId", $statusId, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all charge points with their availability status and owner info
     * @return array All charge points with details
     */
    public function getAllChargePoints() {
        $sqlQuery = "SELECT cp.charge_point_id, cp.price_per_kwh, cp.charge_point_picture_url,
                    u.user_id, u.first_name, u.last_name, u.email, u.phone_number,
                    cpa.streetName, cpa.postcode, cpa.latitude, cpa.longitude,
                    c.city_name,
                    avs.availability_status_title
                    FROM Pro_ChargePoint cp
                    JOIN Pro_User u ON cp.user_id = u.user_id
                    JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                    JOIN Pro_City c ON cpa.city_id = c.city_id
                    JOIN Pro_AvailabilityStatus avs ON cp.availability_status_id = avs.availability_status_id
                    ORDER BY cp.charge_point_id";
        
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get users by role
     * @param int $roleId The role ID to filter by
     * @return array Users with specified role
     */
    public function getUsersByRole($roleId) {
        $sqlQuery = "SELECT u.user_id, u.username, u.first_name, u.last_name, 
                    u.email, u.phone_number, 
                    r.role_title,
                    uas.user_account_status_title
                    FROM Pro_User u
                    JOIN Pro_Role r ON u.role_id = r.role_id
                    JOIN Pro_UserAccountStatus uas ON u.user_account_status_id = uas.user_account_status_id
                    WHERE u.role_id = :roleId
                    ORDER BY u.user_id";
        
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindParam(":roleId", $roleId, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get users by account status
     * @param int $statusId The account status ID to filter by
     * @return array Users with specified account status
     */
    public function getUsersByStatus($statusId) {
        $sqlQuery = "SELECT u.user_id, u.username, u.first_name, u.last_name, 
                    u.email, u.phone_number, 
                    r.role_title,
                    uas.user_account_status_title
                    FROM Pro_User u
                    JOIN Pro_Role r ON u.role_id = r.role_id
                    JOIN Pro_UserAccountStatus uas ON u.user_account_status_id = uas.user_account_status_id
                    WHERE u.user_account_status_id = :statusId
                    ORDER BY u.user_id";
        
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindParam(":statusId", $statusId, PDO::PARAM_INT);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get booking statistics
     * @return array Statistics about bookings
     */
    public function getBookingStatistics() {
        $stats = [];
        
        // Total bookings
        $sqlQuery = "SELECT COUNT(*) as total FROM Pro_Booking";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        $stats['totalBookings'] = $statement->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Bookings by status
        $sqlQuery = "SELECT bs.booking_status_title, COUNT(*) as count
                    FROM Pro_Booking b
                    JOIN Pro_BookingStatus bs ON b.booking_status_id = bs.booking_status_id
                    GROUP BY b.booking_status_id, bs.booking_status_title";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        $stats['bookingsByStatus'] = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Most popular charge points (by number of bookings)
        $sqlQuery = "SELECT cp.charge_point_id, cpa.streetName, cpa.postcode, c.city_name, 
                    COUNT(*) as booking_count
                    FROM Pro_Booking b
                    JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                    JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                    JOIN Pro_City c ON cpa.city_id = c.city_id
                    GROUP BY cp.charge_point_id, cpa.streetName, cpa.postcode, c.city_name
                    ORDER BY booking_count DESC
                    LIMIT 5";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        $stats['popularChargePoints'] = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Get user statistics
     * @return array Statistics about users
     */
    public function getUserStatistics() {
        $stats = [];
        
        // Total users
        $sqlQuery = "SELECT COUNT(*) as total FROM Pro_User";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        $stats['totalUsers'] = $statement->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Users by role
        $sqlQuery = "SELECT r.role_title, COUNT(*) as count
                    FROM Pro_User u
                    JOIN Pro_Role r ON u.role_id = r.role_id
                    GROUP BY u.role_id, r.role_title";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        $stats['usersByRole'] = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Users by account status
        $sqlQuery = "SELECT uas.user_account_status_title, COUNT(*) as count
                    FROM Pro_User u
                    JOIN Pro_UserAccountStatus uas ON u.user_account_status_id = uas.user_account_status_id
                    GROUP BY u.user_account_status_id, uas.user_account_status_title";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        $stats['usersByStatus'] = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * Get availability statistics for charge points
     * @return array Statistics about charge point availability
     */
    public function getAvailabilityStatistics() {
        $stats = [];
        
        // Most common availability days
        $sqlQuery = "SELECT day_of_week, COUNT(*) as count
                    FROM Pro_AvailabilityDays
                    GROUP BY day_of_week
                    ORDER BY count DESC";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        $stats['popularDays'] = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // Most common availability times
        $sqlQuery = "SELECT at.available_time, COUNT(*) as count
                    FROM Pro_AvailabilityTimes at
                    GROUP BY at.available_time
                    ORDER BY count DESC
                    LIMIT 10";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        $stats['popularTimes'] = $statement->fetchAll(PDO::FETCH_ASSOC);
        // Fetch popular charge points
    $stats['popularChargePoints'] = $this->getPopularChargePoints();
        return $stats;
    }
    
    /**
     * Run a custom SQL query (for admin use only)
     * @param string $sql The SQL query to execute
     * @return array Query results
     */
    public function runCustomQuery($sql) {
        try {
            // Validate that the query is SELECT only (for security)
            $trimmedSql = trim(strtoupper($sql));
            if (!str_starts_with($trimmedSql, 'SELECT')) {
                throw new Exception("Only SELECT queries are allowed");
            }
            
            $statement = $this->_dbHandle->prepare($sql);
            $statement->execute();
            
            return [
                'success' => true,
                'results' => $statement->fetchAll(PDO::FETCH_ASSOC),
                'columnCount' => $statement->columnCount()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all available booking statuses
     * @return array All booking statuses
     */
    public function getBookingStatuses() {
        $sqlQuery = "SELECT * FROM Pro_BookingStatus";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all available user roles
     * @return array All user roles
     */
    public function getUserRoles() {
        $sqlQuery = "SELECT * FROM Pro_Role";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all available user account statuses
     * @return array All user account statuses
     */
    public function getUserAccountStatuses() {
        $sqlQuery = "SELECT * FROM Pro_UserAccountStatus";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
 * Get popular charge points based on the number of bookings
 * @return array Popular charge points
 */
public function getPopularChargePoints() {
    $sqlQuery = "SELECT cp.charge_point_id, cpa.streetName, cpa.postcode, c.city_name, 
                 COUNT(*) as booking_count
                 FROM Pro_Booking b
                 JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
                 JOIN Pro_ChargePointAddress cpa ON cp.charge_point_address_id = cpa.charge_point_address_id
                 JOIN Pro_City c ON cpa.city_id = c.city_id
                 GROUP BY cp.charge_point_id, cpa.streetName, cpa.postcode, c.city_name
                 ORDER BY booking_count DESC
                 LIMIT 5"; 

    $statement = $this->_dbHandle->prepare($sqlQuery);
    $statement->execute();
    
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
}