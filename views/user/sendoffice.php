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

            $success2 = "";
$recipientNames = [];

// 1. Get selected user IDs
$burdenArray = $_POST['burden'] ?? [];

// 2. Fetch recipient names from database
if (!empty($burdenArray)) {
    $placeholders = rtrim(str_repeat('?,', count($burdenArray)), ',');
    $sqlUsers = "SELECT ID, CONCAT(FirstName, ' ', LastName) AS FullName FROM tbluser WHERE ID IN ($placeholders)";
    $stmtUsers = $dbh->prepare($sqlUsers);
    $stmtUsers->execute($burdenArray);

    $usersData = [];
    while ($row = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
        $usersData[$row['ID']] = $row['FullName'];
    }

    // 3. Insert notifications and collect names
    foreach ($burdenArray as $burdenId) {
        $sqlNotification = "INSERT INTO notifications (user_id, message, sendid, document) VALUES (:user_id, :message, :sendid, :document)";
        $queryNotification = $dbh->prepare($sqlNotification);
        $queryNotification->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $queryNotification->bindParam(':message', $notificationMessage, PDO::PARAM_STR);
        $queryNotification->bindParam(':sendid', $burdenId, PDO::PARAM_INT);
        $queryNotification->bindParam(':document', $fileName, PDO::PARAM_STR);

        if ($queryNotification->execute()) {
            if (isset($usersData[$burdenId])) {
                $recipientNames[] = $usersData[$burdenId];
            }
        }
    }

    // 4. Khmer success message
    if (!empty($recipientNames)) {
        $recipientsKhmer = implode(', ', $recipientNames);
        $success2 = "$fileName បានរក្សាទុករួចរាល់ និង បានជូនដំណឹងទៅកាន់ $recipientsKhmer បានជោគជ័យ។";
    }
}

            // Make sure $id is defined and safe
            if (isset($id)) {
                // Update the document record with recipient names
                $sql2 = "UPDATE indocument SET document = ?, NameRecipient = ?, DepartmentReceive = ? WHERE ID = ?";
                $stmt2 = $dbh->prepare($sql2);
                $stmt2->execute([$fileName, implode(', ', $recipientNames), implode(', ', $departmentArray), $id]);
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
    //     $error2 = "Please select a file.";»
}

// Translate
include('../../includes/translate.php');
$requestId = $_SESSION['userid'];
$pageTitle = "Document Entry";
$sidebar = "inoutdocument";

ob_start();
?>

<!-- <div class="app-card-body shadow-sm align-items-center rounded-4 bg-white p-3 mb-3">
    <div class="row col-md-12 d-flex justify-content-between align-items-center">
        <div class="title-form d-flex align-items-center justify-content-start p-0">
            <i class='bx bxs-file-doc p-3 rounded-circle bg-label-primary'></i>
            <h4 class="mt-2">ផ្ទេរឯកសារ</h4>
        </div>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="userid" value="<?php echo htmlspecialchars($_SESSION['userid']); ?>">
        <div class="row mt-2">
            <div class="mb-3 col-md-6">

                <div class="mb-3 col-md-6">
                    <label class="form-label">ការិយាល័យទទួលបន្ទុក</label>
                    <div class="input-group input-group-merge">

                        <select class="ustom-select form-control select2 form-select " name="department[]" multiple
                            required>
                            <option value="">ជ្រើសរើស......</option>
                            <?php
                            $sql = "SELECT OfficeName FROM tbloffices";
                            $query = $dbh->prepare($sql);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                            if ($query->rowCount() > 0) {
                                foreach ($results as $result) { ?>
                                    <option value="<?php echo htmlentities($result->OfficeName); ?>">
                                        <?php echo htmlentities($result->OfficeName); ?>
                                    </option>
                            <?php }
                            } ?>
                        </select>
                    </div>
                </div>
                <label for="burden" class="form-label">បញ្ជូនទៅមន្រ្តីទទួលបន្ទុកបន្ត</label>
                <div class="input-group input-group-merge">

                    <select name="burden" id="burden" class="form-select form-control" required>
                        <option value="">ជ្រើសរើស......</option>
                        <?php
                        // SQL query to fetch user names based on specific criteria
                        $sql = "SELECT CONCAT(FirstName, ' ', LastName) AS FullName FROM tbluser WHERE hr = 1 OR training = 1 OR it = 1 OR ofaudit1 = 1 OR ofaudit2 = 1 OR ofaudit3 = 1 OR ofaudit4 = 1";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        // Check if there are results
                        if ($query->rowCount() > 0) {
                            foreach ($results as $result) { ?>
                                <option value="<?php echo htmlspecialchars($result->FullName); ?>">
                                    <?php echo htmlspecialchars($result->FullName); ?>
                                </option>
                            <?php }
                        } else { ?>
                            <option value="" disabled>User not found</option>
                        <?php } ?>

                    </select>
                </div>
            </div>
        </div>
        <div class="form-group mt-2">
            <div class="input-group input-file" name="Fichier2">
                <input type="file" name="file2" class="form-control rounded-2" placeholder="Choose document..." />
                <div class="form-group ml-1">
                    <button type="submit" name="submit" class="btn btn-primary me-2 pull-right">បញ្ជូនឯកសារ</button>
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
                <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($row2['document']); ?>" target="_blank"
                    class="btn-sm btn-link h6 mb-0 ">
                    <?php echo htmlspecialchars($row2['document']); ?>
                </a>
                <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($row2['document']); ?>" target="_blank"
                    class="btn-sm bg-gradient-success text-white h6 mb-0"><i class="bi bi-download"></i> Download</a>
            </div>
        <?php } ?>
    </form>
</div> -->

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
            <div class="row mt-2">
                <div class="col mb-3">
                    <label class="form-label">នាយកដ្ឋានទទួលបន្ទុក</label>
                    <select class="custom-select form-control select2 form-select " name="department[]" multiple
                        required>
                        <option value="">ជ្រើសរើស......</option>
                        <?php
                        $sql = "SELECT OfficeName FROM tbloffices";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        if ($query->rowCount() > 0) {
                            foreach ($results as $result) { ?>
                                <option value="<?php echo htmlentities($result->OfficeName); ?>">
                                    <?php echo htmlentities($result->OfficeName); ?>
                                </option>
                        <?php }
                        } ?>
                    </select>
                </div>
                <div class="col mb-3">
                    <label for="burden" class="form-label">បញ្ជូនទៅមន្រ្តីទទួលបន្ទុកបន្ត</label>
                    <select name="burden[]" id="burden" class="form-select select2 form-control" multiple required>
                        <option value="">ជ្រើសរើស...</option>
                        <?php
                        // SQL query to fetch user ID and full name based on specific criteria
                        $sql = "SELECT ID, CONCAT(FirstName, ' ', LastName) AS FullName 
                FROM tbluser 
                WHERE hr = 1 OR training = 1 OR it = 1 
                   OR ofaudit1 = 1 OR ofaudit2 = 1 OR ofaudit3 = 1 OR ofaudit4 = 1";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        // Check if there are results
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

            </div>

            <div class="form-group mt-2">
                <div class="input-group input-file" name="Fichier2">
                    <input type="file" name="file2" class="form-control rounded-2" placeholder="Choose document..." />

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
            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $document = $row2['document'];
                if (!empty($document)) { ?>
                    <div class="d-flex align-items-center justify-content-between bg-label-success p-2 rounded-3">
                        <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($document); ?>" target="_blank"
                            class="btn-sm btn-link h6 mb-0 ">
                            <?php echo htmlspecialchars($document); ?>
                        </a>
                        <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($document); ?>" target="_blank"
                            class="btn-sm bg-gradient-success text-white h6 mb-0">
                            <i class="bi bi-download"></i> Download
                        </a>
                    </div>
            <?php
                }
            } ?>

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