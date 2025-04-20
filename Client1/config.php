<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "user_authentication";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Set timezone
date_default_timezone_set('UTC');
?> 