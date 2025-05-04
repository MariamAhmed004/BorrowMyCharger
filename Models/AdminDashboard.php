<?php
require_once 'Models/Database.php';

class AdminDashboard {
    private $dbHandle;  

    public function __construct() {

        $this->dbHandle = Database::getInstance()->getDbConnection();
    }

    //get the total system users count
    public function getUserCount() {
        $sql = "SELECT COUNT(*) FROM Pro_User";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    //get the total home owners count
    public function getHomeOwnerCount() {
        $sql = "SELECT COUNT(*) FROM Pro_User WHERE role_id = 2";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    

    //get the total home owners count
    public function getChargePointCount() {
        $sql = "SELECT COUNT(*) FROM Pro_ChargePoint";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    //get the total Pending Approval count
    public function getPendingApproval() {
        $sql = "SELECT COUNT(*) FROM Pro_User WHERE user_account_status_id = 3";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

}
