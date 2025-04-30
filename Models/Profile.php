<?php
require_once 'Models/Database.php';

class Profile {
    private $dbHandle;
    private $userId;

    public function __construct($userId) {
        $this->userId = $userId;
        $this->dbHandle = Database::getInstance()->getDbConnection();
    }

    public function getUserData() {
        $sql = "SELECT * FROM Pro_User WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUserProfile($firstName, $lastName, $email, $phoneNumber) {
        $sql = "UPDATE Pro_User SET first_name = :first_name, last_name = :last_name, phone_number = :phone_number WHERE user_id = :user_id";
        $stmt = $this->dbHandle->prepare($sql);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':phone_number', $phoneNumber);
        $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

   public function deleteUserAccount() {
    $sql = "DELETE FROM Pro_User WHERE user_id = :user_id";
    $stmt = $this->dbHandle->prepare($sql);
    $stmt->bindParam(':user_id', $this->userId, PDO::PARAM_INT);
    return $stmt->execute(); // returns true on success
}

}
