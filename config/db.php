<?php
// config/db.php

$host = 'localhost';
$username = 'root';
$password = 'km@24y8*'; // Default WAMP password
$database = 'noticeboard_db'; // Name of your database

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql_create_db) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($database);

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");


// Helper function for quick prepared queries if needed
function executeQuery($conn, $sql, $types = null, ...$params)
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    if ($types && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt;
}

// Note: Notice expiry is handled by cron_scheduler.php, not on every page load
// Note: Use $pdo->exec($cleanup_query);
