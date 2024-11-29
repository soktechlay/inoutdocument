<?php
session_start();
include('../../config/dbconn.php');

// Redirect to login page if user is not logged in
if (!isset($_SESSION['userid'])) {
    header('Location: ../../index.php');
    exit();
}

$id = isset($_GET['ID']) ? intval($_GET['ID']) : null;

if (is_null($id)) {
    $error = "No ID provided";
}

// File upload directories
$targetDir1 = "../../uploads/file/note-doc/";

// Handle form submission for file upload
if (isset($_POST["submit"])) {
    if (!empty($_FILES["file2"]["name"])) {
        $fileName = basename($_FILES["file2"]["name"]);
        $targetDir1 = "../../uploads/file/note-doc/";
        $targetFilePath = $targetDir1 . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $departmentArray = isset($_POST['department']) ? $_POST['department'] : array();
        $burdenArray = isset($_POST['burden']) ? $_POST['burden'] : array();

        // Allow certain file formats
        $allowTypes = array('docx', 'pdf', 'pptx');


        if (move_uploaded_file($_FILES["file2"]["tmp_name"], $targetFilePath)) {
            $userId = $_SESSION['userid'];
            $notificationMessage = "ឯកសារចូលនាយកដ្ឋាន";

            // Collect the recipient names
            $recipientNames = [];
            foreach ($burdenArray as $burdenId) {
                // Fetch the UserName from tbluser based on User ID (burdenId)
                $sqlUser = "SELECT CONCAT(FirstName, ' ', LastName) AS FullName FROM tbluser WHERE ID = :userId";
                $stmtUser = $dbh->prepare($sqlUser);
                $stmtUser->bindParam(':userId', $burdenId, PDO::PARAM_INT);
                $stmtUser->execute();
                $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if ($userRow) {
                    $recipientNames[] = $userRow['FullName'];
                } else {
                    $error2 = "User not found.";
                }
            }

            // Make sure $id is defined and safe
            if (isset($id)) {
                // Update the document record with recipient names
                $sql2 = "UPDATE indocument SET document = ?, NameRecipient = ?, DepartmentReceive = ? WHERE ID = ?";
                $stmt2 = $dbh->prepare($sql2);
                $stmt2->execute([$fileName, implode(', ', $recipientNames), implode(', ', $departmentArray), $id]);
                $success2 = $fileName . " បានរក្សាទុករួចរាល់។";

                // Loop through each selected burden (User ID) again to send notifications
                foreach ($burdenArray as $burdenId) {
                    // Insert into notifications table
                    $sqlNotification = "INSERT INTO notifications (user_id, message, sendid, document) VALUES (:user_id, :message, :sendid, :document)";
                    $queryNotification = $dbh->prepare($sqlNotification);
                    $queryNotification->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $queryNotification->bindParam(':message', $notificationMessage, PDO::PARAM_STR);
                    $queryNotification->bindParam(':sendid', $burdenId, PDO::PARAM_INT);
                    $queryNotification->bindParam(':document', $fileName, PDO::PARAM_STR);

                    if ($queryNotification->execute()) {
                        $success2 .= " Notification sent successfully.";
                    } else {
                        $error2 = "Error sending notification.";
                    }
                }
            } else {
                $error2 = "Invalid document ID.";
            }
        } else {
            $error2 = "Error uploading file.";
        }
    } else {
        $error2 = "File type not allowed.";
    }
    // } else {
    //     $error2 = "Please select a file.";
}




// Fetch the existing documents for display
$sql2 = "SELECT document FROM indocument WHERE ID = ?";
$stmt = $dbh->prepare($sql2);
$stmt->execute([$id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Translate
include('../../includes/translate.php');
$requestId = $_SESSION['userid'];
$pageTitle = "ឯកសារចូលអង្គភាពសវនកម្មផ្ទៃក្នុង";
$sidebar = "inoutdocument";

ob_start();
?>

<div class="app-card-body shadow-sm align-items-center rounded-4 bg-white p-3 mb-3 rounded-3">
    <div class="card-header">
        <div class="title-form d-flex align-items-center justify-content-start p-0">
            <i class='bx bxs-file-doc p-3 rounded-circle bg-label-primary'></i>
            <h4 class="mt-2">ឯកសារចំណារ</h4>
        </div>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <div class="card-body mb-3">
            <input type="hidden" name="userid" value="<?php echo htmlspecialchars($_SESSION['userid']); ?>">
            <div class="form-group mt-2">
                <div class="input-group input-file">
                    <input type="file" name="file2" class="form-control rounded-2" placeholder="Choose document..." />
                </div>
                <?php if (isset($error2)) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error2); ?>
                    </div>
                <?php } elseif (isset($success2)) { ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo htmlspecialchars($success2); ?>
                    </div>
                <?php } ?>
            </div>
            <div class="row mt-2">
                <div class="col mb-3">
                    <label for="burden" class="form-label">បញ្ជូនទៅមន្រ្តីទទួលបន្ទុកបន្ត</label>
                    <select name="burden[]" id="burden" class="form-select select2 form-control" multiple required>
                        <option value="">ជ្រើសរើស...</option>
                        <?php
                        // SQL query to fetch user IDs and names based on specific criteria
                        $sql = "SELECT ID, CONCAT(FirstName, ' ', LastName) AS FullName FROM tbluser";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if ($query->rowCount() > 0) {
                            foreach ($results as $result) { ?>
                                <option value="<?php echo htmlspecialchars($result->ID); ?>">
                                    <?php echo htmlspecialchars($result->FullName); ?>
                                </option>
                            <?php }
                        } else { ?>
                            <option value="" disabled>User not found</option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col mb-3">
                    <label class="form-label">នាយកដ្ឋានទទួលបន្ទុក</label>
                    <select class="custom-select form-control select2 form-select " name="department[]" multiple
                        required>
                        <option value="">ជ្រើសរើស...</option>
                        <?php
                        $sql = "SELECT DepartmentName FROM tbldepartments";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        if ($query->rowCount() > 0) {
                            foreach ($results as $result) { ?>
                                <option value="<?php echo htmlspecialchars($result->DepartmentName); ?>">
                                    <?php echo htmlspecialchars($result->DepartmentName); ?>
                                </option>
                            <?php }
                        } ?>
                    </select>
                </div>
            </div>           

            <?php if (!empty($documents)): ?>
                <div class="h6 mt-4">ឯកសារចំណារ ថ្មីៗ</div>
                <?php foreach ($documents as $document): ?>
                    <?php if (!empty($document['document'])): ?>
                        <div class="d-flex align-items-center justify-content-between bg-label-success p-2 rounded-3">
                            <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($document['document']); ?>"
                                target="_blank" class="btn-sm btn-link h6 mb-0">
                                <?php echo htmlspecialchars($document['document']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No documents found.</p>
            <?php endif; ?>

        </div>
        <div class="card-footer text-end">
            <button type="submit" name="submit" class="btn btn-primary ms-auto pull-right">បញ្ជូនឯកសារ</button>
        </div>
    </form>
</div>


<?php
$content = ob_get_clean();
include('../../layouts/user_layout.php');
?>