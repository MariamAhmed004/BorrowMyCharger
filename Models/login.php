<?php
require_once 'Database.php';

class Login
{
    public static function authenticate($email, $password)
    {
        $connection = Database::getInstance()->getDbConnection();

        $query = "SELECT * FROM Pro_User WHERE email = :email LIMIT 1";
        $statement = $connection->prepare($query);
        $statement->bindParam(':email', $email);
        $statement->execute();

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return 'email_not_found';
        }

        if (!password_verify($password, $user['password'])) {
            return 'incorrect_password';
        }

        // Check user account status
        switch ($user['user_account_status_id']) {
            case 2:
                return 'account_suspended';
            case 3:
                return 'account_pending';
        }

        return [
            'id' => $user['user_id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'username' => $user['username'],
            'role_id' => $user['role_id']
        ];
    }
}
