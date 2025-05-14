<?php
require_once 'Models/Database.php';

class AdminDashboard {
    private $dbHandle;  
    
    public function __construct() {
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }
    
    // Get total users count
    public function getUserCount() {
        $sql = "SELECT COUNT(*) FROM Pro_User";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    // Get counts of users by role
    public function getUserCountsByRole() {
        $sql = "
            SELECT r.role_title, COUNT(u.user_id) as count
            FROM Pro_User u
            JOIN Pro_Role r ON u.role_id = r.role_id
            GROUP BY u.role_id, r.role_title
            ORDER BY count DESC
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // Get total homeowners count
    public function getHomeOwnerCount() {
        $sql = "SELECT COUNT(*) FROM Pro_User WHERE role_id = 2";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    // Get total charge points count
    public function getChargePointCount() {
        $sql = "SELECT COUNT(*) FROM Pro_ChargePoint";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    // Get pending approval count
    public function getPendingApproval() {
        $sql = "SELECT COUNT(*) FROM Pro_User WHERE user_account_status_id = 3";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    // Get charge point status counts with status titles
    public function getChargePointStatusCounts() {
        $sql = "
            SELECT a.availability_status_title as status, COUNT(c.charge_point_id) as count
            FROM Pro_ChargePoint c
            JOIN Pro_AvailabilityStatus a ON c.availability_status_id = a.availability_status_id
            GROUP BY c.availability_status_id, a.availability_status_title
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // Get user account status counts with status titles
    public function getUserStatusCounts() {
        $sql = "
            SELECT s.user_account_status_title as status, COUNT(u.user_id) as count
            FROM Pro_User u
            JOIN Pro_UserAccountStatus s ON u.user_account_status_id = s.user_account_status_id
            GROUP BY u.user_account_status_id, s.user_account_status_title
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // Get booking status counts
    public function getBookingStatusCounts() {
        $sql = "
            SELECT s.booking_status_title as status, COUNT(b.booking_id) as count
            FROM Pro_Booking b
            JOIN Pro_BookingStatus s ON b.booking_status_id = s.booking_status_id
            GROUP BY b.booking_status_id, s.booking_status_title
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // Get bookings by date (last 7 days)
    public function getBookingsLastSevenDays() {
        $sql = "
            SELECT DATE(booking_date) as date, COUNT(*) as count
            FROM Pro_Booking
            WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(booking_date)
            ORDER BY date
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // Get top 5 charge points with most bookings
    public function getTopChargePoints($limit = 5) {
        $sql = "
            SELECT cp.charge_point_id, COUNT(b.booking_id) as booking_count
            FROM Pro_ChargePoint cp
            LEFT JOIN Pro_Booking b ON cp.charge_point_id = b.charge_point_id
            GROUP BY cp.charge_point_id
            ORDER BY booking_count DESC
            LIMIT :limit
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // Get price range of charge points
    public function getChargePointPriceStats() {
        $sql = "
            SELECT 
                MIN(price_per_kwh) as min_price,
                MAX(price_per_kwh) as max_price,
                AVG(price_per_kwh) as avg_price,
                STDDEV(price_per_kwh) as std_price
            FROM Pro_ChargePoint
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get most popular booking days
    public function getPopularBookingDays() {
        $sql = "
            SELECT ad.day_of_week, COUNT(b.booking_id) as booking_count
            FROM Pro_Booking b
            JOIN Pro_ChargePoint cp ON b.charge_point_id = cp.charge_point_id
            JOIN Pro_AvailabilityDays ad ON cp.charge_point_id = ad.charge_point_id
            GROUP BY ad.day_of_week
            ORDER BY booking_count DESC
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    // Get most popular booking times
    public function getPopularBookingTimes() {
        $sql = "
            SELECT HOUR(b.booking_time) as hour, COUNT(b.booking_id) as booking_count
            FROM Pro_Booking b
            GROUP BY HOUR(b.booking_time)
            ORDER BY booking_count DESC
        ";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}