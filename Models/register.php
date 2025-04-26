<?php

require_once 'Models/Database.php';

class Register
{
    private $_db;

    public function __construct()
    {
        $this->_db = Database::getInstance()->getDbConnection();
    }

    public function createUser($username, $firstname, $lastname, $email, $password, $phoneNumber, $roleId = null, $accountStatusId = null)
    {
        $sql = "INSERT INTO Pro_User (username, first_name, last_name, password, email, phone_number, role_id, user_account_status_id)
                VALUES (:username, :first_name, :last_name, :password, :email, :phone_number, :role_id, :account_status_id)";
        
        $stmt = $this->_db->prepare($sql);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':first_name', $firstname);
        $stmt->bindParam(':last_name', $lastname);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone_number', $phoneNumber);
        $stmt->bindParam(':role_id', $roleId);
        $stmt->bindParam(':account_status_id', $accountStatusId);

        return $stmt->execute();
    }

    public function checkUserExists($username, $email)
    {
        $sql = "SELECT COUNT(*) FROM Pro_User WHERE username = :username OR email = :email";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetchColumn() > 0; // Returns true if either username or email exists
    }
}
?>