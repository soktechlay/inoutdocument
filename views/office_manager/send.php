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
// $targetDir = "../../uploads/file/in-doc/";
$targetDir1 = "../../uploads/file/note-doc/";

// if (isset($_POST["submited"])) {
//     if (!empty($_FILES["file1"]["name"])) {
//         $fileName = basename($_FILES["file1"]["name"]);
//         $targetFilePath = $targetDir . $fileName;
//         $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

//         // Allow certain file formats 
//         $allowTypes = array('docx', 'pdf', 'pptx');
//         if (in_array($fileType, $allowTypes)) {
//             if (move_uploaded_file($_FILES["file1"]["tmp_name"], $targetFilePath)) {
//                 $sql1 = "UPDATE indocument SET Typedocument = ? WHERE ID = ?";
//                 $stmt1 = $dbh->prepare($sql1);
//                 $stmt1->execute([$fileName, $id]);
//                 $success1 = $fileName . " បានរក្សាទុករួចរាល់។";
//             } else {
//                 $error1 = "សូមអភ័យទោស, មានបញ្ហាកើតឡើងកំលុងពេលរក្សាទុកឯកសារ។";
//             }
//         }
//     }
// }

if (isset($_POST["submit"])) {
    if (!empty($_FILES["file2"]["name"])) {
        $fileName = basename($_FILES["file2"]["name"]);
        $targetFilePath = $targetDir1 . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        $department = $_POST['department'];
        $burden = $_POST['burden']; // Assuming $_POST['burden'] holds the UserName

        // Allow certain file formats 
        $allowTypes = array('docx', 'pdf', 'pptx');
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES["file2"]["tmp_name"], $targetFilePath)) {
                // Fetch the ID from tbluser based on UserName (burden)
                $sqlUser = "SELECT ID FROM tbluser WHERE UserName = :userName";
                $stmtUser = $dbh->prepare($sqlUser);
                $stmtUser->bindParam(':userName', $burden);
                $stmtUser->execute();
                $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if ($userRow) {
                    $sendid = $userRow['ID'];

                    // Update SQL statement for indocument table
                    $sql2 = "UPDATE indocument SET document = ?, NameRecipient = ?, DepartmentReceive = ? WHERE ID = ?";
                    $stmt2 = $dbh->prepare($sql2);
                    if ($stmt2) {
                        // Execute SQL statement
                        $stmt2->execute([$fileName, $burden, $department, $id]);
                        $success2 = $fileName . " បានរក្សាទុករួចរាល់។";

                        // Insert into notifications table
                        $userId = $_SESSION['userid']; // Assuming you have the user ID stored in session
                        // $notificationMessage = "New submitted by user ID: $userId with send ID: $sendid";
                        $notificationMessage = "ឯកសារចូលនាយកដ្ឋាន";

                        $sqlNotification = "INSERT INTO notifications (user_id, message, sendid, document) VALUES (:user_id, :message, :sendid, :document)";
                        $queryNotification = $dbh->prepare($sqlNotification);
                        $queryNotification->bindParam(':user_id', $userId); // Use admin ID here
                        $queryNotification->bindParam(':message', $notificationMessage);
                        $queryNotification->bindParam(':sendid', $sendid);
                        $queryNotification->bindParam(':document', $fileName);

                        if ($queryNotification->execute()) {
                            $success2 .= " Notification sent successfully.";
                        } else {
                            $error2 = "Error sending notification.";
                        }
                    } else {
                        $error2 = "សូមអភ័យទោស, មានបញ្ហាកើតឡើងកំលុងពេលរក្សាទុកឯកសារ។";
                    }
                } else {
                    $error2 = "User not found.";
                }
            } else {
                $error2 = "Error uploading file.";
            }
        } else {
            $error2 = "File type not allowed.";
        }
    } else {
        $error2 = "Please select a file.";
    }
}




// Translate
include('../../includes/translate.php');
$requestId = $_SESSION['userid'];
$pageTitle = "ឯកសារចូលអង្គភាពសវនកម្មផ្ទៃក្នុង";
$sidebar = "inoutdocument";

ob_start();
?>


<!-- <div class="app-card-body shadow-sm align-items-center rounded-4 bg-white p-3 mb-3">
    <div class="row col-md-12 d-flex justify-content-between align-items-center">
        <div class="title-form d-flex align-items-center justify-content-start p-0">
            <i class='bx bxs-file-doc p-3 rounded-circle bg-label-primary'></i>
            <h4 class="mt-2">ឯកសារចូល</h4>
        </div>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group mt-2">
            <div class="input-group input-file" name="Fichier1">
                <input type="file" name="file1" class="form-control rounded-2" placeholder="Choose document..." />
                <span class="input-group-btn ml-1">
                    <button class="btn btn-danger btn-reset" type="button">Reset</button>
                </span>
                <div class="form-group ml-1">
                    <button type="submit" name="submited" class="btn btn-primary me-2 pull-right">Submit</button>
                </div>
            </div>
            <?php if (isset($error1)) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error1; ?>
                </div>
            <?php } elseif (isset($success1)) { ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success1; ?>
                </div>
            <?php } ?>
        </div>
    </form>
    <div class="h6 mt-4">ឯកសារចូល ថ្មីៗ</div>
    <?php
    $sql1 = "SELECT Typedocument FROM indocument WHERE ID = ?";
    $stmt1 = $dbh->prepare($sql1);
    $stmt1->execute([$id]);
    while ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) { ?>
        <div class="d-flex align-items-center justify-content-between bg-label-success p-2 rounded-3">
            <a href="../../uploads/file/in-doc/<?php echo htmlspecialchars($row1['Typedocument']); ?>" target="_blank" class="btn-sm btn-link h6 mb-0 ">
                <?php echo htmlspecialchars($row1['Typedocument']); ?>
            </a>
            <a href="../../uploads/file/in-doc/<?php echo htmlspecialchars($row1['Typedocument']); ?>" target="_blank" class="btn-sm bg-gradient-success text-white h6 mb-0"><i class="bi bi-download"></i> Download</a>
        </div>
    <?php } ?>
</div> -->

<div class="app-card-body shadow-sm align-items-center rounded-4 bg-white p-3 mb-3">
    <div class="row col-md-12 d-flex justify-content-between align-items-center">
        <div class="title-form d-flex align-items-center justify-content-start p-0">
            <i class='bx bxs-file-doc p-3 rounded-circle bg-label-primary'></i>
            <h4 class="mt-2">ឯកសារចំណារ</h4>
        </div>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="userid" value="<?php echo $_SESSION['userid'] ?>">

        <div class="row mt-2">
            <div class="mb-3 col-md-6">
                <label for="burden" class="form-label">បញ្ជូនទៅមន្រ្តីទទួលបន្ទុកបន្ត</label>
                <div class="input-group input-group-merge">
                    <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                    <select name="burden" id="burden" class="form-select form-control" required>
                        <option value="">ជ្រើសរើស...</option>
                        <?php
                        $sql = "SELECT UserName, FirstName, LastName FROM tbluser WHERE iau = 1 OR general = 1 OR audit1 = 1 OR audit2 = 1";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if ($query->rowCount() > 0) {
                            foreach ($results as $result) { ?>
                                <option value="<?php echo htmlentities($result->UserName); ?>">
                                    <?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>
                                </option>
                        <?php } } ?>
                    </select>
                </div>
            </div>
            <div class="mb-3 col-md-6">
                <label class="form-label">នាយកដ្ឋានទទួលបន្ទុក</label>
                <div class="input-group input-group-merge">
                    <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bxs-business'></i></span>
                    <select class="custom-select form-control form-select rounded-2" name="department" required>
                        <option value="">ជ្រើសរើស...</option>
                        <?php
                        $sql = "SELECT * FROM tbldepartments";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        if ($query->rowCount() > 0) {
                            foreach ($results as $result) { ?>
                                <option value="<?php echo htmlentities($result->DepartmentName); ?>"><?php echo htmlentities($result->DepartmentName); ?></option>
                        <?php }
                        } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group mt-2">
            <div class="input-group input-file" name="Fichier2">
                <input type="file" name="file2" class="form-control rounded-2" placeholder="Choose document..." />
                <span class="input-group-btn ml-1">
                    <button class="btn btn-danger btn-reset" type="button" onclick="resetFileInput('file2')">ត្រឡប់</button>
                </span>
                <div class="form-group ml-1">
                    <button type="submit" name="submit" class="btn btn-primary me-2 pull-right">បញ្ជូន</button>
                </div>
            </div>
            <?php if (isset($error2)) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error2; ?>
                </div>
            <?php } elseif (isset($success2)) { ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success2; ?>
                </div>
            <?php } ?>
        </div>

        <div class="h6 mt-4">ឯកសារចំណារ ថ្មីៗ</div>
        <?php
        $sql2 = "SELECT document FROM indocument WHERE ID = ?";
        $stmt2 = $dbh->prepare($sql2);
        $stmt2->execute([$id]);
        while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) { ?>
            <div class="d-flex align-items-center justify-content-between bg-label-success p-2 rounded-3">
                <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($row2['document']); ?>" target="_blank" class="btn-sm btn-link h6 mb-0 ">
                    <?php echo htmlspecialchars($row2['document']); ?>
                </a>
                <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($row2['document']); ?>" target="_blank" class="btn-sm bg-gradient-success text-white h6 mb-0"><i class="bi bi-download"></i> Download</a>
            </div>
        <?php } ?>
    </form>
</div>



<?php
$content = ob_get_clean();
include('../../layouts/user_layout.php');
?>
<script>
    function resetFileInput(name) {
        const fileInput = document.querySelector(`input[name="${name}"]`);
        fileInput.value = '';
    }

    document.querySelectorAll('.btn-reset').forEach(button => {
        button.addEventListener('click', function() {
            const fileInput = this.closest('.input-group').querySelector('input[type="file"]');
            fileInput.value = '';
        });
    });
</script>