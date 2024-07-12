<?php
session_start();
include('../../config/dbconn.php');
require '../../vendor1/autoload.php';

use Shuchkin\SimpleXLSXGen;

// Ensure session is started and eid is set
// Ensure session is started and eid is set
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

if ($documentType === 'indocument') {
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
    if (!empty($searchResults) && !empty(array_filter($searchResults))) {
        $header = ['**ល.រ**', '**លេខឯកសារ**', '**កម្មវត្តុ**', '**មកពីស្ថាប័នឬក្រសួង**', '**ឈ្មោះមន្រ្តីប្រគល់**', '**ឈ្មោះមន្រ្តីទទួល**', '**ប្រភេទឯកសារចូល**','**កាលបរិច្ឆេទ**'];

        $data = [];
        $cnt = 1;
        foreach ($searchResults as $row) {
            // Check if the row has non-empty values
            if (!empty(array_filter($row))) {
                $data[] = [
                    $cnt,
                    $row['CodeId'],
                    $row['Type'],
                    $row['DepartmentName'],
                    $row['NameOfgive'],
                    $row['NameOFReceive'],
                    $row['Typedocument'],                    
                    $row['Date']
                ];
                $cnt++;
            }
        }

        if (count($data) > 0) {
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
        echo "No data found in indocument.";
    }
} else {
    echo "Invalid document type.";
}

?>
