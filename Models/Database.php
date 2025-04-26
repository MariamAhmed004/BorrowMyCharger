<?php

class Database {
    protected static $_dbInstance = null;
    protected $_dbHandle;

    public static function getInstance() {
        $username = 'u202201907'; // Your database username
        $password = 'u202201907'; // Your database password
        $host = 'localhost'; // Use the IP address or hostname directly
        $dbName = 'db202201907';  // Your database name

        if (self::$_dbInstance === null) {
            self::$_dbInstance = new self($username, $password, $host, $dbName);
        }

        return self::$_dbInstance;
    }

   
    private function __construct($username, $password, $host, $database) {
        try {
            $this->_dbHandle = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
            $this->_dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage();
            exit();
        }
    }

    public function getDbConnection() {
        return $this->_dbHandle;
    }

    public function __destruct() {
        $this->_dbHandle = null;
    }
}
?>