<?php
//
require_once 'Database.php';

class Cities
{
    // Fetch cities based on country
    public static function getCities($countryId = null)
    {
        $connection = Database::getInstance()->getDbConnection();
        $query = "SELECT * FROM Pro_City" . ($countryId ? " WHERE country_id = :country_id" : "");
        $statement = $connection->prepare($query);

        if ($countryId) {
            $statement->bindParam(':country_id', $countryId, PDO::PARAM_INT);
        }

        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC); // Return all results as an associative array
    }
}