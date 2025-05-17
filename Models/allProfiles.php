<?php
require_once 'Database.php';

class Profiles {
    private $_db;
    
    public function __construct() {
        $this->_db = Database::getInstance()->getDbConnection();
    }
    
   public function getAllProfiles($nameFilter = '', $roleFilter = '', $statusFilter = '') {
    $query = "
        SELECT 
            u.user_id, 
            u.first_name, 
            u.last_name, 
            u.username,
            u.email, 
            r.role_title,
            s.user_account_status_title,
            s.user_account_status_id
        FROM Pro_User u 
        JOIN Pro_Role r ON u.role_id = r.role_id
        LEFT JOIN Pro_UserAccountStatus s ON u.user_account_status_id = s.user_account_status_id
        WHERE u.role_id IN (2, 3)
    ";
    
    $params = [];
    
    // Add filters - improved name filter handling
    if (!empty($nameFilter)) {
        // Log the name filter for debugging
        error_log("Using name filter: '$nameFilter'");
        
        // Search for the name in first_name, last_name, or concatenated full name
        $query .= " AND (u.first_name LIKE :nameFilter 
                     OR u.last_name LIKE :nameFilter 
                     OR CONCAT(u.first_name, ' ', u.last_name) LIKE :nameFilter)";
        $params[':nameFilter'] = '%' . $nameFilter . '%';
    }
    
    if (!empty($roleFilter)) {
        $query .= " AND r.role_title = :roleFilter";
        $params[':roleFilter'] = $roleFilter;
    }
    
    if (!empty($statusFilter)) {
        $query .= " AND s.user_account_status_title = :statusFilter";
        $params[':statusFilter'] = $statusFilter;
    }
    
    error_log("Full SQL query: " . $query);
    error_log("Parameters: " . print_r($params, true));
    
    $stmt = $this->_db->prepare($query);
    
    // Bind all parameters at once
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log count of results for debugging
    error_log("Found " . count($results) . " results for the query");
    
    return $results;
}
    public function getUniqueNames() {
        $stmt = $this->_db->prepare("SELECT DISTINCT CONCAT(first_name, ' ', last_name) AS full_name FROM Pro_User");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getAccountStatuses() {
        $stmt = $this->_db->prepare("SELECT user_account_status_id, user_account_status_title FROM Pro_UserAccountStatus");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
  public function getRoles() {
    $stmt = $this->_db->prepare("
        SELECT role_id, role_title 
        FROM Pro_Role 
        WHERE role_id != 1
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
   public function deleteUser($userId) {
    try {
        // Start transaction for data integrity
        $this->_db->beginTransaction();
        
        // First get user role
        $roleStmt = $this->_db->prepare("SELECT role_id FROM Pro_User WHERE user_id = :user_id");
        $roleStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $roleStmt->execute();
        $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$role) {
            throw new Exception("User not found");
        }
        
        $roleId = $role['role_id'];
        
        // If user is HomeOwner (role_id = 2)
        if ($roleId == 2) {
            // Get all charge points owned by this user
            $cpStmt = $this->_db->prepare("SELECT charge_point_id FROM Pro_ChargePoint WHERE user_id = :user_id");
            $cpStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $cpStmt->execute();
            $chargePoints = $cpStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($chargePoints as $cp) {
                $cpId = $cp['charge_point_id'];
                
                // 1. Delete bookings related to this charge point
                $deleteBookingsStmt = $this->_db->prepare("
                    DELETE FROM Pro_Booking WHERE charge_point_id = :charge_point_id
                ");
                $deleteBookingsStmt->bindParam(':charge_point_id', $cpId, PDO::PARAM_INT);
                $deleteBookingsStmt->execute();
                
                // 2. Delete availability times related to this charge point
                $deleteAvailTimesStmt = $this->_db->prepare("
                    DELETE Pro_AvailabilityTimes 
                    FROM Pro_AvailabilityTimes 
                    JOIN Pro_AvailabilityDays ON Pro_AvailabilityTimes.availability_day_id = Pro_AvailabilityDays.availability_day_id 
                    WHERE Pro_AvailabilityDays.charge_point_id = :charge_point_id
                ");
                $deleteAvailTimesStmt->bindParam(':charge_point_id', $cpId, PDO::PARAM_INT);
                $deleteAvailTimesStmt->execute();
                
                // 3. Delete availability days for this charge point
                $deleteAvailDaysStmt = $this->_db->prepare("
                    DELETE FROM Pro_AvailabilityDays WHERE charge_point_id = :charge_point_id
                ");
                $deleteAvailDaysStmt->bindParam(':charge_point_id', $cpId, PDO::PARAM_INT);
                $deleteAvailDaysStmt->execute();
                
                // 4. Get the address ID for this charge point
                $addressStmt = $this->_db->prepare("
                    SELECT charge_point_address_id FROM Pro_ChargePoint WHERE charge_point_id = :charge_point_id
                ");
                $addressStmt->bindParam(':charge_point_id', $cpId, PDO::PARAM_INT);
                $addressStmt->execute();
                $address = $addressStmt->fetch(PDO::FETCH_ASSOC);
                $addressId = $address['charge_point_address_id'];
                
                // 5. Delete the charge point itself
                $deleteCpStmt = $this->_db->prepare("
                    DELETE FROM Pro_ChargePoint WHERE charge_point_id = :charge_point_id
                ");
                $deleteCpStmt->bindParam(':charge_point_id', $cpId, PDO::PARAM_INT);
                $deleteCpStmt->execute();
                
                // 6. Delete the charge point address
                $deleteAddressStmt = $this->_db->prepare("
                    DELETE FROM Pro_ChargePointAddress WHERE charge_point_address_id = :address_id
                ");
                $deleteAddressStmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
                $deleteAddressStmt->execute();
            }
        }
        // If user is RentalUser (role_id = 3)
        else if ($roleId == 3) {
            // Delete all bookings made by this user
            $deleteBookingsStmt = $this->_db->prepare("
                DELETE FROM Pro_Booking WHERE user_id = :user_id
            ");
            $deleteBookingsStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $deleteBookingsStmt->execute();
        }
        
        // Finally, delete the user
        $deleteUserStmt = $this->_db->prepare("
            DELETE FROM Pro_User WHERE user_id = :user_id
        ");
        $deleteUserStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $deleteUserStmt->execute();
        
        // Commit transaction
        $this->_db->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $this->_db->rollBack();
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}
    public function updateUserStatus($userId, $statusTitle) {
        // Get the ID for the specified status
        $statusIdStmt = $this->_db->prepare("SELECT user_account_status_id FROM Pro_UserAccountStatus WHERE user_account_status_title = :title");
        $statusIdStmt->bindParam(':title', $statusTitle);
        $statusIdStmt->execute();
        
        $statusId = $statusIdStmt->fetchColumn();
        if ($statusId) {
            // Update the user status
            $stmt = $this->_db->prepare("UPDATE Pro_User SET user_account_status_id = :statusId WHERE user_id = :userId");
            $stmt->bindParam(':statusId', $statusId, PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }
    
    public function suspendUser($userId) {
        return $this->updateUserStatus($userId, 'Suspended');
    }
    
    public function unsuspendUser($userId) {
        return $this->updateUserStatus($userId, 'Approved');
    }
    
    public function approveUser($userId) {
        return $this->updateUserStatus($userId, 'Approved');
    }
    
    public function getUserStatus($userId) {
        $stmt = $this->_db->prepare("
            SELECT s.user_account_status_title 
            FROM Pro_User u
            JOIN Pro_UserAccountStatus s ON u.user_account_status_id = s.user_account_status_id
            WHERE u.user_id = :userId
        ");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}