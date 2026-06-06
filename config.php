<?php
/**
 * config.php
 * Database connection class using MySQLi OOP style.
 * Implements Singleton pattern to avoid multiple connections.
 */

class Database {
    // --- Connection Parameters ---
    private string $host     = 'localhost';
    private string $username = 'root';
    private string $password = '';
    private string $dbname   = 'coffeeshop';

    // Holds the single mysqli instance
    private mysqli $conn;

    // Singleton instance
    private static ?Database $instance = null;

    /**
     * Private constructor: establishes the mysqli connection.
     * Throws an exception on failure so errors are caught cleanly.
     */
    private function __construct() {
        $this->conn = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->dbname
        );

        if ($this->conn->connect_error) {
            throw new RuntimeException(
                'Database connection failed: ' . $this->conn->connect_error
            );
        }

        // Force UTF-8 for proper character encoding
        $this->conn->set_charset('utf8mb4');
    }

    /**
     * Returns the single Database instance (Singleton).
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Returns the raw mysqli connection object for use in other classes.
     */
    public function getConnection(): mysqli {
        return $this->conn;
    }

    // Prevent cloning and unserialization of the singleton
    private function __clone() {}
    public function __wakeup(): void {
        throw new RuntimeException('Cannot unserialize a singleton.');
    }
}