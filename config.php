<?php
// Database configuration for XAMPP
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'root';
$dbName = 'weather_app';

// Establish database connection
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// API configuration
$apiKey = '1748a7ddac9930b4f0a60fe503ef2469';

