<?php
include('../../config/dbconn.php');
session_start();

header('Content-Type: application/json');

try {
    // Check if user is logged in
    $userId = $_SESSION['userid'] ?? null;
    if (!$userId) {
        echo json_encode(['error' => 'User not logged in or user ID not set.']);
        exit;
    }

    // Fetch user's permissions
    $stmt = $dbh->prepare("SELECT PermissionId FROM tbluser WHERE id = ?");
    $stmt->execute([$userId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data && isset($data['PermissionId'])) {
        $permissions = explode(',', $data['PermissionId']); // Convert permissions to array
        $permissions = array_filter(array_map('intval', $permissions)); // Ensure clean integer array
    } else {
        echo json_encode(['error' => 'No permission data found for the user.']);
        exit;
    }

    if (empty($permissions)) {
        echo json_encode(['count' => 0, 'documents' => []]);
        exit;
    }

    // Determine type (incoming or outgoing documents)
    $type = $_GET['type'] ?? 'incoming';

    // Build SQL for FIND_IN_SET dynamically
    $findInSetConditions = implode(' OR ', array_fill(0, count($permissions), "FIND_IN_SET(?, permissions) > 0"));

    if ($type === 'incoming' || $type === 'in') {
        $countSql = "SELECT COUNT(*) as document_count 
                     FROM indocument 
                     WHERE isdelete = 0 
                     AND ($findInSetConditions)
                     AND DATE(Date) = CURDATE()";

        $sql = "SELECT CodeId, DepartmentName, NameOfgive, Typedocument, DATE_FORMAT(Date, '%d/%m/%y') as formattedDate
                FROM indocument 
                WHERE isdelete = 0 
                AND ($findInSetConditions)
                AND DATE(Date) = CURDATE() 
                ORDER BY Date DESC 
                LIMIT 20";
    } else {
        $countSql = "SELECT COUNT(*) as document_count 
                     FROM outdocument 
                     WHERE isdelete = 0 
                     AND ($findInSetConditions)
                     AND DATE(Date) = CURDATE()";

        $sql = "SELECT CodeId, OutDepartment, Typedocument, NameOFReceive, DATE_FORMAT(Date, '%d/%m/%y') as formattedDate
                FROM outdocument 
                WHERE isdelete = 0 
                AND ($findInSetConditions)
                AND DATE(Date) = CURDATE() 
                ORDER BY Date DESC 
                LIMIT 20";
    }

    // Execute count query
    $stmt = $dbh->prepare($countSql);
    $stmt->execute($permissions);
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $documentCount = $countResult['document_count'] ?? 0;

    // Execute main query for documents
    $stmt = $dbh->prepare($sql);
    $stmt->execute($permissions);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return result as JSON
    echo json_encode([
        'count' => $documentCount,
        'documents' => $documents
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'General error: ' . $e->getMessage()]);
}
