<?php
include('../../config/dbconn.php');
session_start();

// Set the header to return JSON
header('Content-Type: application/json');

try {
    // Ensure the user is logged in
    $userId = $_SESSION['userid'] ?? null;
    if (!$userId) {
        echo json_encode(['error' => 'User not logged in or user ID not set.']);
        exit;
    }

    // Fetch user's permissions (assuming 'PermissionId' is a comma-separated list)
    $stmt = $dbh->prepare("SELECT PermissionId FROM tbluser WHERE id = ?");
    $stmt->execute([$userId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if 'PermissionId' data was found
    if ($data && isset($data['PermissionId'])) {
        $permissions = explode(',', $data['PermissionId']);
        $permissions = array_map('intval', array_map('trim', $permissions));
    } else {
        echo json_encode(['error' => 'No permission data found for the user.']);
        exit;
    }

    // Check if permissions array is empty
    if (empty($permissions)) {
        echo json_encode(['count' => 0, 'documents' => []]);
        exit;
    }

    // SQL placeholders for permissions
    $permissionsPlaceholders = implode(',', array_fill(0, count($permissions), '?'));

    // Determine the document type (incoming or outgoing)
    $type = $_GET['type'] ?? 'incoming';

    // Prepare SQL queries based on type
    if ($type === 'incoming' || $type === 'in') {
        $countSql = "SELECT COUNT(*) as document_count 
                     FROM indocument 
                     WHERE isdelete = 0 
                     AND permissions IN ($permissionsPlaceholders)
                     AND DATE(Date) = CURDATE()";

        $sql = "SELECT CodeId, Type, DepartmentName, NameOfgive, Typedocument, DATE_FORMAT(Date, '%d/%m/%y %H:%i:%s')
 as formattedDate
                FROM indocument 
                WHERE isdelete = 0 
                AND permissions IN ($permissionsPlaceholders)
                AND DATE(Date) = CURDATE() 
                ORDER BY Date DESC 
                LIMIT 20";
    } else {
        $countSql = "SELECT COUNT(*) as document_count 
                     FROM outdocument 
                     WHERE isdelete = 0 
                     AND permissions IN ($permissionsPlaceholders)
                     AND DATE(Date) = CURDATE()";

        $sql = "SELECT CodeId, Type, OutDepartment, Typedocument, NameOFReceive, DATE_FORMAT(Date, '%d/%m/%y %H:%i:%s')
 as formattedDate
                FROM outdocument 
                WHERE isdelete = 0 
                AND permissions IN ($permissionsPlaceholders)
                AND DATE(Date) = CURDATE() 
                ORDER BY Date DESC 
                LIMIT 20";
    }

    // Execute the count query
    $stmt = $dbh->prepare($countSql);
    $stmt->execute($permissions);
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $documentCount = $countResult['document_count'] ?? 0;

    // Execute the main document query
    $stmt = $dbh->prepare($sql);
    $stmt->execute($permissions);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the result as JSON
    echo json_encode([
        'count' => $documentCount,
        'documents' => $documents
    ]);

} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Handle general errors
    echo json_encode(['error' => $e->getMessage()]);
}
