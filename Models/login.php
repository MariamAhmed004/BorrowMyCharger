<?php
require_once 'Database.php';

class Login
{
    // Authenticate user by email and password
    public static function authenticate($email, $password)
    {
        // Get database connection
        $connection = Database::getInstance()->getDbConnection();
        // Prepare SQL query to find user by email
        $query = "SELECT * FROM pro_user WHERE email = :email LIMIT 1";
        $statement = $connection->prepare($query);
        $statement->bindParam(':email', $email);
        $statement->execute();
        // Fetch user data
        $user = $statement->fetch();

        // Check if user exists
        if (!$user) {
            return 'email_not_found'; // Email does not exist
        }

        // Check if password is correct
        if (!password_verify($password, $user['password'])) {
            return 'incorrect_password'; // Password does not match
        }

        // Return user data if authentication is successful
        return $user;
    }
}