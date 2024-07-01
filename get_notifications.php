<?php
// Database configuration
$host = 'localhost'; // Replace with your database host
$dbname = 'test'; // Replace with your database name
$username = 'root'; // Replace with your database username
$password = ''; // Replace with your database password

// Establish a connection to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch notifications from the database
try {
    $stmt = $pdo->query('SELECT ID, document, NameOfgive FROM indocument');
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($notifications);
} catch (PDOException $e) {
    die("Error fetching notifications: " . $e->getMessage());
}
?>