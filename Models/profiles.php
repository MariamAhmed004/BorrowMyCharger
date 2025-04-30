<?php
require_once 'Database.php';

class Profiles {
    private $_db;
    
    public function __construct() {
        $this->_db = Database::getInstance()->getDbConnection();
    }
    
    public function getAllProfiles() {
        $stmt = $this->_db->prepare("
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
        "); 
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt = $this->_db->prepare("SELECT role_id, role_title FROM Pro_Role");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteUser($userId) {
        $stmt = $this->_db->prepare("DELETE FROM Pro_User WHERE user_id = :user_id"); 
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
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