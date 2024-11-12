<?php
session_start();
include('../../config/dbconn.php');
require '../../vendor1/autoload.php';

use Shuchkin\SimpleXLSXGen;

// Ensure session is started and userid is set
if (!isset($_SESSION['userid'])) {
    header('Location: ../../index.php');
    exit();
}
$userId = $_SESSION['userid'];

// Determine which document type to filter and export
$documentType = $_POST['documentType'] ?? '';
$fromDate = $_POST['fromDate'] ?? '';
$toDate = $_POST['toDate'] ?? '';

// Validate input
if (!$documentType || !$fromDate || !$toDate) {
    die("Invalid input.");
}

// Convert the date formats
$fromDateFormatted = date('Y-m-d 00:00:00', strtotime($fromDate));
$toDateFormatted = date('Y-m-d 23:59:59', strtotime($toDate));

// Prepare parameters
$params = [
    ':fromDate' => $fromDateFormatted,
    ':toDate' => $toDateFormatted
];

// Set up SQL and headers based on document type
if ($documentType === 'outdocument') {
    $sql = "SELECT * FROM outdocument 
            INNER JOIN tbluser ON outdocument.user_id = tbluser.id 
            WHERE outdocument.isdelete = 0 
            AND outdocument.Department = 1 
            AND outdocument.Date BETWEEN :fromDate AND :toDate 
            ORDER BY outdocument.id DESC";
    $header = ['**ល.រ**', '**លេខឯកសារ**', '**កម្មវត្តុ**', '**ចេញទៅស្ថាប័នឬក្រសួង**', '**ឈ្មោះមន្រ្តីទទួល**', '**ឈ្មោះមន្រ្តីប្រគល់**', '**ចេញពីនាយកដ្ឋាន**', '**ប្រភេទឯកសារចេញ**', '**កាលបរិច្ឆេទ**'];
} elseif ($documentType === 'indocument') {
    $sql = "SELECT * FROM indocument 
            INNER JOIN tbluser ON indocument.user_id = tbluser.id 
            WHERE indocument.isdelete = 0 
            AND indocument.Department = 1 
            AND indocument.Date BETWEEN :fromDate AND :toDate 
            ORDER BY indocument.id DESC";
    $header = ['**ល.រ**', '**លេខឯកសារ**', '**កម្មវត្តុ**', '**មកពីស្ថាប័នឬក្រសួង**', '**ឈ្មោះមន្រ្តីប្រគល់**', '**ឈ្មោះមន្រ្តីទទួល**', '**ប្រភេទឯកសារចូល**', '**ប្រភេទឯកសារចំណារ**', '**ឈ្មោះនាយកដ្ឋានទទួលបន្ទុកឬការិយាល័យទទួលបន្ទុក**', '**ឈ្មោះមន្រ្តីទទួលបន្ទុកបន្ត**', '**កាលបរិច្ឆេទ**'];
} else {
    die("Invalid document type.");
}

// Execute the query
$query = $dbh->prepare($sql);
$query->execute($params);
$searchResults = $query->fetchAll(PDO::FETCH_ASSOC);

if (!empty($searchResults)) {
    // Prepare data array for the XLSX file
    $data = [];
    $cnt = 1;
    foreach ($searchResults as $row) {
        if ($documentType === 'outdocument') {
            $data[] = [
                $cnt,
                $row['CodeId'],
                $row['Type'],
                $row['OutDepartment'],
                $row['NameOFReceive'],
                $row['NameOfgive'],
                $row['FromDepartment'],
                $row['Typedocument'],
                $row['Date']
            ];
        } elseif ($documentType === 'indocument') {
            $data[] = [
                $cnt,
                $row['CodeId'],
                $row['Type'],
                $row['DepartmentName'],
                $row['NameOfgive'],
                $row['NameOFReceive'],
                $row['Typedocument'],
                $row['document'],
                $row['DepartmentReceive'],
                $row['NameRecipient'],
                $row['Date']
            ];
        }
        $cnt++;
    }

    // Add header to the beginning of data
    array_unshift($data, $header);
    $xlsx = SimpleXLSXGen::fromArray($data);

    // Set file name and headers for download
    $fileName = $documentType === 'outdocument' ? 'departmentoutdocument_export.xlsx' : 'departmentindocument_export.xlsx';

    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Cache-Control: max-age=0');
    $xlsx->downloadAs($fileName);
    exit;
} else {
    echo "No data found in " . htmlspecialchars($documentType) . ".";
}
?>
