<?php
// config.php - Database configuration
// This file contains the database connection settings for the application.
// Ensure that the database credentials are correct before using this file.
// Usage: Include this file in scripts that require database access.
$host = 'localhost';
$dbname = 'ncitad';
$username = 'root';
$password = '12345678';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>