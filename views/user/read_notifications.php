<?php
session_start();
include('../../config/dbconn.php');

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: ../../index.php');
    exit();
}

// Check if id parameter is provided in the URL
if (!isset($_GET['id'])) {
    // Redirect or handle error if id is not provided
    header("Location: ../../index.php"); // Redirect to homepage or appropriate error page
    exit();
}
$pageTitle = "Notification";
// Example: Fetch notification details from database based on id
$notificationId = $_GET['id'];

// Update query to set is_read to 1 for the specified notification ID
$sql = "UPDATE notifications SET is_read = 1 WHERE id = :id";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);

// Execute update query
if ($stmt->execute()) {
    // Query to fetch notification details after update (if needed)
    $sqlFetchNotification = "SELECT n.document, n.user_id, n.created_at, i.NameRecipient, i.DepartmentName, i.NameOfgive
                             FROM indocument i 
                             LEFT JOIN notifications n ON n.user_id = i.user_id 
                             WHERE n.id = :id";
    $stmtFetchNotification = $dbh->prepare($sqlFetchNotification);
    $stmtFetchNotification->bindParam(':id', $notificationId, PDO::PARAM_INT);

    // Initialize variables for notification details
    $document = '';
    $user_id = '';
    $created_at = '';
    $nameRecipient = '';
    $DepartmentName = '';
    $NameOfgive = '';

    if ($stmtFetchNotification->execute()) {
        $row = $stmtFetchNotification->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $document = htmlspecialchars($row["document"]);
            $user_id = htmlspecialchars($row["user_id"]);
            $created_at = htmlspecialchars($row["created_at"]);
            $nameRecipient = htmlspecialchars($row["NameRecipient"]);
            $DepartmentName = htmlspecialchars($row["DepartmentName"]);
            $NameOfgive = htmlspecialchars($row["NameOfgive"]);
            
        } else {
            // Handle case where notification with given id is not found
            $error_message = "Notification not found.";
        }
    } else {
        // Handle database execution error
        $error_message = "Failed to fetch notification.";
    }
} else {
    // Handle update query execution error
    $error_message = "Failed to mark notification as read.";
}

// Get the content from output buffer
ob_start();
?>




<?php if (!empty($error_message)) : ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error_message; ?>
    </div>
<?php else : ?>
    <div class="container d-flex justify-content-center align-items-center">
        <div class="col-6">
            <h4 class="py-3 mb-1">Notification</h4>
            <div class="card mb-4">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class='bx bx-user me-2'></i>ឈ្មោះអ្នកប្រគល់ :</span>
                        <span class="text-end"><?php echo $NameOfgive; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class='bx bx-building'></i>ឈ្មោះស្ថាប័នឬក្រសួង :</span>
                        <span class="text-end"><?php echo $DepartmentName; ?></span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class='bx bxs-file-pdf me-2'></i>ឯកសារភ្ជាប់ :</span>
                        <span class="text-end text-break col-8">
                            <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($row['document']); ?>" target="_blank" class="text-primary">
                                <?php echo $document; ?>
                            </a>
                        </span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class='bx bx-calendar me-2'></i>ថ្ងៃខែឆ្នាំផ្ញើរឯកសារ :</span>
                        <span class="text-end"><?php echo $created_at; ?></span>
                    </li>
                    <!-- <li class="list-group-item d-flex justify-content-center align-items-center">
                        <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($row['document']); ?>" target="_blank" class="btn-sm  text-primary h5 mb-0"><i class="bi bi-download"></i> Download</a>
                    </li> -->
                   
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Get the content from output buffer
$content = ob_get_clean();

// Include layout or template file
include('../../layouts/user_layout.php');
?>