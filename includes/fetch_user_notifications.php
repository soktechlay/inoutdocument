<?php
// Include the database connection file
include '../config/dbconn.php'; // Adjust the path as necessary

try {
    // Check if session is not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user ID is available in session
    if (!isset($_SESSION['userid'])) {
        throw new Exception('User ID not set in session.');
    }

    // Get the user ID from the session
    $userId = $_SESSION['userid'];

    // SQL query to count unread notifications for the current user
    $sql = "SELECT COUNT(*) AS unread_count
            FROM notifications
            WHERE is_read = 0
            AND sendid = :userId";

    // Prepare the query
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Output the count
    echo $result['unread_count'];
} catch (PDOException $e) {
    // Handle database errors
    echo "Database Error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle general errors
    echo "Error: " . $e->getMessage();
}
?>
