<?php
try {
  // Check if session is not already started
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
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

  // Output the count in JSON format
  header('Content-Type: application/json');
  echo json_encode(['unread_count' => $result['unread_count']]);
} catch (PDOException $e) {
  // Handle database errors
  header('Content-Type: application/json');
  echo json_encode(['error' => $e->getMessage()]);
}
?>
