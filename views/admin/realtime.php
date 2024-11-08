<?php
// realtime.php
include('../../config/dbconn.php');

// Set the header to return JSON
header('Content-Type: application/json');

try {
    // Get the document type from the query parameter, default to 'incoming'
    $type = $_GET['type'] ?? 'incoming';

    if ($type === 'incoming' || $type === 'in') {
        // SQL query for incoming documents
        $countSql = "SELECT COUNT(*) as document_count 
                     FROM indocument 
                     WHERE isdelete = 0 
                     AND permissions = 1 
                     AND DATE(Date) = CURDATE()";

        $sql = "SELECT CodeId, DepartmentName, NameOfgive, Typedocument, DATE_FORMAT(Date, '%d/%m/%y') as formattedDate
                FROM indocument 
                WHERE isdelete = 0 
                AND permissions = 1 
                AND DATE(Date) = CURDATE() 
                ORDER BY Date DESC 
                LIMIT 20";
    } else {
        // SQL query for outgoing documents
        $countSql = "SELECT COUNT(*) as document_count 
                     FROM outdocument 
                     WHERE isdelete = 0 
                     AND permissions = 1 
                     AND DATE(Date) = CURDATE()";

        $sql = "SELECT CodeId, OutDepartment, Typedocument, NameOFReceive, DATE_FORMAT(Date, '%d/%m/%y') as formattedDate
                FROM outdocument 
                WHERE isdelete = 0 
                AND permissions = 1 
                AND DATE(Date) = CURDATE() 
                ORDER BY Date DESC 
                LIMIT 20";
    }

    // Execute the count query
    $stmt = $dbh->prepare($countSql);
    $stmt->execute();
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $documentCount = $countResult['document_count'] ?? 0;

    // Execute the document query
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the result as JSON
    echo json_encode([
        'count' => $documentCount,
        'documents' => $documents
    ]);

} catch (PDOException $e) {
    // Return the error in JSON format
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
