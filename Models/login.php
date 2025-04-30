<?php
require_once 'Database.php';

class Login
{
   public static function authenticate($email, $password)
{
    // Get database connection
    $connection = Database::getInstance()->getDbConnection();

    // Prepare SQL query to find user by email
    $query = "SELECT * FROM Pro_User WHERE email = :email LIMIT 1";
    $statement = $connection->prepare($query);
    $statement->bindParam(':email', $email);
    $statement->execute();

    // Fetch user data as an associative array
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$user) {
        return 'email_not_found'; // Email does not exist
    }

    // Check if password is correct
    if (!password_verify($password, $user['password'])) {
        return 'incorrect_password'; // Password does not match
    }

    // Return user data if authentication is successful
    return [
        'id' => $user['user_id'], // Ensure this is the correct primary key
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'username' => $user['username'],
        'role_id' => $user['role_id'],
    ];
}
}