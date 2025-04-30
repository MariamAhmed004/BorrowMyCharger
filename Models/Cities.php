<?php
//
require_once 'Database.php';

class Cities
{
    // Fetch cities
    public static function getCities()
    {
        $connection = Database::getInstance()->getDbConnection();
        $query = "SELECT * FROM Pro_City";
        $statement = $connection->prepare($query);

       

        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC); // Return all results as an associative array
    }
}