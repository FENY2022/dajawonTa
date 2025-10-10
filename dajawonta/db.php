<?php
$host = 'localhost';
$db = 'dajawonta_db'; // Changed database name to match the project
$user = 'root';  // Default XAMPP user
$pass = '';      // Default empty password

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
