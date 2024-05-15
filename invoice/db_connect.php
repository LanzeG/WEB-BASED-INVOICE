<?php
// Database configuration
$servername = "localhost:3307"; // Change if your database server is not on localhost
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password
$dbname = "invoice_db"; // The name of your database

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
