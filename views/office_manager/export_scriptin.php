<?php
session_start();
include('../../config/dbconn.php');
require '../../vendor1/autoload.php';

use Shuchkin\SimpleXLSXGen;

// Ensure session is started and user ID is set
if (!isset($_SESSION['userid'])) {
    header('Location: ../../index.php');
    exit();
}
$userId = $_SESSION['userid'];

// Fetch permissions for the current user
$stmt = $dbh->prepare("SELECT PermissionId FROM tbluser WHERE id = ?");
$stmt->execute([$userId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data || empty($data['PermissionId'])) die("No permissions found.");

$departmentIds = array_map('trim', explode(',', $data['PermissionId']));
if (empty($departmentIds)) die("No department permissions available.");

// Validate and format inputs
$documentType = $_POST['documentType'] ?? '';
$fromDate = $_POST['fromDate'] ?? '';
$toDate = $_POST['toDate'] ?? '';
if (!$documentType || !$fromDate || !$toDate) die("Invalid input.");

$fromDateFormatted = date('Y-m-d 00:00:00', strtotime($fromDate));
$toDateFormatted = date('Y-m-d 23:59:59', strtotime($toDate));
$permissionsPlaceholders = implode(',', array_fill(0, count($departmentIds), '?'));

// Helper function to fetch and export data
function fetchAndExport($dbh, $sql, $params, $header, $fileName) {
    $query = $dbh->prepare($sql);
    $query->execute($params);
    $searchResults = $query->fetchAll(PDO::FETCH_ASSOC);

    if (empty($searchResults)) die("No data found.");

    $data = [];
    foreach ($searchResults as $index => $row) {
        $data[] = array_merge([$index + 1], array_values($row));
    }
    array_unshift($data, $header);

    $xlsx = SimpleXLSXGen::fromArray($data);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Cache-Control: max-age=0');
    $xlsx->downloadAs($fileName);
    exit;
}

// SQL queries and headers
$baseParams = [$fromDateFormatted, $toDateFormatted];
$params = array_merge($baseParams, $departmentIds);

if ($documentType === 'indocument') {
    $sql = "SELECT CodeId, Type, DepartmentName, NameOfgive, NameOFReceive, Typedocument, Date 
            FROM indocument 
            INNER JOIN tbluser ON indocument.user_id = tbluser.id 
            WHERE indocument.isdelete = 0 AND indocument.office = 1 
            AND indocument.Date BETWEEN ? AND ? 
            AND indocument.permissions IN ($permissionsPlaceholders)
            ORDER BY indocument.id DESC";
    $header = ['**ល.រ**', '**លេខឯកសារ**', '**កម្មវត្តុ**', '**មកពីស្ថាប័នឬក្រសួង**', '**ឈ្មោះមន្រ្តីប្រគល់**', '**ឈ្មោះមន្រ្តីទទួល**', '**ប្រភេទឯកសារចូល**', '**កាលបរិច្ឆេទ**'];
    fetchAndExport($dbh, $sql, $params, $header, 'officeindocument_export.xlsx');
} elseif ($documentType === 'outdocument') {
    $sql = "SELECT CodeId, Type, OutDepartment, NameOFReceive, NameOfgive, FromDepartment, Typedocument, Date 
            FROM outdocument 
            INNER JOIN tbluser ON outdocument.user_id = tbluser.id 
            WHERE outdocument.isdelete = 0 AND outdocument.office = 1
            AND outdocument.Date BETWEEN ? AND ? 
            AND outdocument.permissions IN ($permissionsPlaceholders)
            ORDER BY outdocument.id DESC";
    $header = ['**ល.រ**', '**លេខឯកសារ**', '**កម្មវត្តុ**', '**ចេញទៅស្ថាប័នឬក្រសួង**', '**ឈ្មោះមន្រ្តីទទួល**', '**ឈ្មោះមន្រ្តីប្រគល់**', '**ចេញពីការិល័យ**', '**ប្រភេទឯកសារចេញ**', '**កាលបរិច្ឆេទ**'];
    fetchAndExport($dbh, $sql, $params, $header, 'officeoutdocument_export.xlsx');
} else {
    die("Invalid document type.");
}
?>
