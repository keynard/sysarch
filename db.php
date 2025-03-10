<?php
$host = "localhost";
$dbname = "registration_system";
$username = "root";
$password = "";

try {
    // Create a PDO connection with error mode set to exception
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

