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

$params = [
    ':userid' => $userId,
    ':fromDate' => $fromDateFormatted,
    ':toDate' => $toDateFormatted
];

if ($documentType === 'outdocument') {
    $sql = "SELECT * FROM outdocument 
            INNER JOIN tbluser ON outdocument.user_id = tbluser.id 
            WHERE tbluser.id = :userid
            AND outdocument.isdelete = 0 
            AND outdocument.Date BETWEEN :fromDate AND :toDate 
            ORDER BY outdocument.id DESC";

    $query = $dbh->prepare($sql);
    $query->execute($params);
    $searchResults = $query->fetchAll(PDO::FETCH_ASSOC);

    // Check if any non-empty row exists
    if (!empty($searchResults)) {
        $header = ['**ល.រ**', '**លេខឯកសារ**', '**កម្មវត្តុ**', '**ចេញទៅស្ថាប័នឬក្រសួង**', '**ឈ្មោះមន្រ្តីទទួល**', '**ឈ្មោះមន្រ្តីប្រគល់**', '**មកពីនាយកដ្ឋាន**', '**ប្រភេទឯកសារចេញ**', '**កាលបរិច្ឆេទ**'];

        $data = [];
        $cnt = 1;
        foreach ($searchResults as $row) {
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
            $cnt++;
        }

        array_unshift($data, $header); // Add header to the beginning of data
        $xlsx = SimpleXLSXGen::fromArray($data);
        $fileName = 'outdocument_export.xlsx';

        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Cache-Control: max-age=0');
        $xlsx->downloadAs($fileName);
        exit;
    } else {
        echo "No data found in outdocument.";
    }
} elseif ($documentType === 'indocument') {
    $sql = "SELECT * FROM indocument 
            INNER JOIN tbluser ON indocument.user_id = tbluser.id 
            WHERE tbluser.id = :userid
            AND indocument.isdelete = 0 
            AND indocument.Date BETWEEN :fromDate AND :toDate 
            ORDER BY indocument.id DESC";

    $query = $dbh->prepare($sql);
    $query->execute($params);
    $searchResults = $query->fetchAll(PDO::FETCH_ASSOC);

    // Check if any non-empty row exists
    if (!empty($searchResults)) {
        $header = ['**ល.រ**', '**លេខឯកសារ**', '**កម្មវត្តុ**', '**មកពីស្ថាប័នឬក្រសួង**', '**ឈ្មោះមន្រ្តីប្រគល់**', '**ឈ្មោះមន្រ្តីទទួល**', '**ប្រភេទឯកសារចូល**', '**ប្រភេទឯកសារចំណារ**', '**ឈ្មោះនាយកដ្ឋានទទួលបន្ទុក**', '**ឈ្មោះមន្រ្តីទទួលបន្ទុកបន្ត**', '**កាលបរិច្ឆេទ**'];

        $data = [];
        $cnt = 1;
        foreach ($searchResults as $row) {
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
            $cnt++;
        }

        array_unshift($data, $header); // Add header to the beginning of data
        $xlsx = SimpleXLSXGen::fromArray($data);
        $fileName = 'indocument_export.xlsx';

        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Cache-Control: max-age=0');
        $xlsx->downloadAs($fileName);
        exit;
    } else {
        echo "No data found in indocument.";
    }
} else {
    echo "Invalid document type.";
}
?>
