<?php
session_start();
include('../../config/dbconn.php');

// Redirect to login page if user is not logged in
if (!isset($_SESSION['userid'])) {
    header('Location: ../../index.php');
    exit();
}

// Include translation if needed
include('../../includes/translate.php');

// Page variables
$pageTitle = "ឯកសារចេញការិយាល័យធនធានមនុស្ស";
$sidebar = "outhr";
$userId = $_SESSION['userid'];
date_default_timezone_set('Asia/Bangkok');
$date = date('Y-m-d H:i:s');
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    // Prepare data array for insertion
    $data = [
        ':userid' => $userId,
        ':code' => $_POST['code'],
        ':type' => $_POST['type'],
        ':fromdepartment' => $_POST['fromdepartment'],
        ':nameofgive' => $_POST['nameofgive'],
        ':outdepartment' => $_POST['outdepartment'],
        ':nameofreceive' => $_POST['nameofreceive'],
        ':file_name' => $_FILES['files']['name'],
        ':date' => $date,
        ':office' => 1 , // Adding offices value
        ':permissions' => 5
    ];

    // File handling
    $file_tmp = $_FILES['files']['tmp_name'];
    $destination = "../../uploads/file/out-doc/" . $data[':file_name'];

    // Move uploaded file to destination
    if (move_uploaded_file($file_tmp, $destination)) {
        // Prepare SQL statement for insertion
        $sql = "INSERT INTO outdocument (CodeId, Type, OutDepartment, NameOfgive, Typedocument, NameOFReceive, FromDepartment, Date, user_id, office, permissions)
                VALUES (:code, :type, :outdepartment, :nameofgive, :file_name, :nameofreceive, :fromdepartment, :date, :userid, :office, :permissions)";
        $query = $dbh->prepare($sql);

        // Execute SQL query with data array
        try {
            $query->execute($data);
            $msg = $query->rowCount() ? "Successfully submitted!" : "Error inserting data into the database.";
            header("Location: outhr.php?msg=" . urlencode($msg) . "&status=success");
            exit();
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
            header("Location: outhr.php?msg=" . urlencode($error) . "&status=error");
            exit();
        }
    } else {
        $error = "Error uploading file.";
        header("Location: outhr.php?msg=" . urlencode($error) . "&status=error");
        exit();
    }
}

// Handle document deletion
if (isset($_GET['delete'])) {
    $sql = "UPDATE outdocument SET isdelete = 1 WHERE ID = :documentID";
    $query = $dbh->prepare($sql);
    $query->bindParam(':documentID', $_GET['delete'], PDO::PARAM_INT);

    // Execute deletion query
    try {
        $query->execute();
        if ($query->rowCount()) {
            $msg = "Document deleted successfully!";
            header("Location: outhr.php?msg=" . urlencode($msg) . "&status=success");
            exit();
        } else {
            $error = "Failed to delete document.";
            header("Location: outhr.php?msg=" . urlencode($error) . "&status=error");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
        header("Location: outhr.php?msg=" . urlencode($error) . "&status=error");
        exit();
    }
}

// Handle search functionality and fetch records
$sql = "SELECT * FROM outdocument 
        JOIN tbluser ON outdocument.user_id = tbluser.id 
        WHERE tbluser.id = :userid 
        AND outdocument.isdelete = 0
        AND outdocument.office = 1";
$params = [':userid' => $userId];

// Handle search query
if (isset($_GET['search'])) {
    $searchKeyword = '%' . $_GET['search'] . '%';
    $sql .= " AND (CodeId LIKE :searchKeyword OR Type LIKE :searchKeyword OR DepartmentName LIKE :searchKeyword)";
    $params[':searchKeyword'] = $searchKeyword;
}

// Handle form filters and date range
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fromDate'], $_POST['toDate'])) {
    $fromDate = date('Y-m-d 00:00:00', strtotime($_POST['fromDate']));
    $toDate = date('Y-m-d 23:59:59', strtotime($_POST['toDate']));
    $sql .= " AND outdocument.Date BETWEEN :fromDate AND :toDate";
    $params[':fromDate'] = $fromDate;
    $params[':toDate'] = $toDate;
}

if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    // Correctly format the date
    date_default_timezone_set('Asia/Bangkok');
    $date = date('Y-m-d H:i:s');
    if (isset($_FILES['files']) && $_FILES['files']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['files']['tmp_name'];
        $fileName = $_FILES['files']['name'];

        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name
        $newFileName = ($fileName) . '.' . $fileExtension;

        // Directory where you want to save the uploaded file
        $uploadFileDir = '../../uploads/file/in-doc/';
        $dest_path = $uploadFileDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $msg = "File is successfully uploaded.";
            $uploadedFile = $newFileName;
        } else {
            $error = 'There was an error moving the uploaded file.';
        }
    } else {
        // No new file uploaded, use the existing file
        $uploadedFile = $_POST['current_file'];
    }
    $query = $dbh->prepare("UPDATE outdocument SET
  CodeId = ?, Type = ?, FromDepartment = ?, NameOfgive = ?,
  OutDepartment = ?, NameOFReceive = ?, Typedocument = ?, `update` = ?
  WHERE ID = ?");
    $query->execute([
        $_POST['code'],
        $_POST['type'],
        $_POST['fromdepartment'],
        $_POST['nameofgive'],
        $_POST['outdepartment'],
        $_POST['nameofreceive'],
        $uploadedFile,
        $update,
        $id
    ]);
    if ($query->rowCount() > 0) {
        header("Location: outhr.php?ID=" . urlencode($id) . "&msg=Successfully+Edited&status=success");
        exit();
    } else {
        header("Location: outhr.php?ID=" . urlencode($id) . "&msg=Error+Edited&status=failed");
        exit();
    }
}
// Finalize SQL query with ORDER BY
$sql .= " ORDER BY outdocument.id DESC";

// Prepare and execute the SQL query
$query = $dbh->prepare($sql);
$query->execute($params);

// Fetch all results into $searchResults
$searchResults = $query->fetchAll(PDO::FETCH_ASSOC);

// Start buffering output
ob_start();
?>
<div class="row">
    <div class="col-md-12">
        <div class="container-xl flex-grow-1">
            <div class="d-flex align-items-center justify-content-between">
                <div class="card-header">
                    <h4 class="py-3 mb-1 text-primary"><span class="text-muted fw-light ">ការិយាល័យធនធានមនុស្ស/</span>ឯកសារចេញ</h4>
                </div>
                <div class="dt-action-buttons pt-md-0">
                    <div class="dt-buttons btn-group flex-wrap ">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal"><span>បញ្ជូលឯកសារចេញ</span></button>
                    </div>
                    <div class="row row-bordered g-0">
                        <div class="modal animate__animated animate__bounceIn" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5 mef2" id="exampleModalLabel">ការដាក់បញ្ជូលឯកសារចេញ</h1>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" class="row g-3 needs-validation" name="example1" enctype="multipart/form-data" novalidate>
                                            <div class="row">
                                                <div class="mb-3 col-md-6">
                                                    <label for="code" class="form-label">លេខឯកសារ</label>
                                                    <div class="input-group input-group-merge">
                                                        <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-book'></i></span>
                                                        <input class="form-control" type="text" id="code" name="code" autocomplete="on" placeholder="បំពេញលេខឯកសារ..." onBlur="checkAvailabilityCodeId()" required>

                                                    </div>
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label for="type" class="form-label">កម្មវត្តុ</label>
                                                    <div class="input-group input-group-merge">
                                                        <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-detail'></i></span>
                                                        <input class="form-control" type="text" name="type" autocomplete="off" id="type" placeholder="បំពេញកម្មវត្តុ..." required>
                                                    </div>
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label for="outdepartment" class="form-label">បញ្ចូនទៅនាយកដ្ឋាន</label>
                                                    <div class="input-group input-group-merge">
                                                        <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bxs-business'></i></span>
                                                        <select class="custom-select form-control form-select rounded-2" name="outdepartment" required>
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
                                                <div class="mb-3 col-md-6">
                                                    <label for="nameofreceive" class="form-label">មន្រ្តីទទួល</label>
                                                    <div class="input-group input-group-merge">
                                                        <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                                                        <select name="nameofreceive" id="nameofreceive" class="form-select form-control" required>
                                                            <option value="">ជ្រើសរើស...</option>
                                                            <?php
                                                            $sql = "SELECT * FROM tbluser";
                                                            $query = $dbh->prepare($sql);
                                                            $query->execute();
                                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                            if ($query->rowCount() > 0) {
                                                                foreach ($results as $result) {
                                                            ?>
                                                                    <option value="<?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>">
                                                                        <?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>
                                                                    </option>
                                                            <?php }
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label for="nameofgive" class="form-label">មន្រ្តី​ប្រគល់</label>
                                                    <div class="input-group input-group-merge">
                                                        <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                                                        <select name="nameofgive" id="nameofgive" class="form-select form-control " required>
                                                            <option value="">ជ្រើសរើស...</option>
                                                            <?php
                                                            $sql = "SELECT * FROM tbluser";
                                                            $query = $dbh->prepare($sql);
                                                            $query->execute();
                                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                            if ($query->rowCount() > 0) {
                                                                foreach ($results as $result) {
                                                            ?>
                                                                    <option value="<?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>">
                                                                        <?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>
                                                                    </option>
                                                            <?php }
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3 col-md-6">
                                                    <label for="fromdepartment" class="form-label">ទទួលពីការិយាល័យ</label>
                                                    <div class="input-group input-group-merge">
                                                        <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bxs-business'></i></span>
                                                        <select class="custom-select form-control form-select rounded-2" name="fromdepartment" required>
                                                            <option value="">ជ្រើសរើស...</option>
                                                            <?php
                                                            // Adjust the query to properly select data from the department and offices tables
                                                            $sql = "SELECT  OfficeName FROM tbloffices";
                                                            $query = $dbh->prepare($sql);
                                                            $query->execute();
                                                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                            if ($query->rowCount() > 0) {
                                                                foreach ($results as $result) {
                                                            ?>
                                                                    <option value="<?php echo htmlentities($result->OfficeName); ?>"><?php echo htmlentities($result->OfficeName); ?></option>

                                                            <?php
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-3 col-md-6">
                                                    <label for="document" class="form-label">ភ្ជាប់ឯកសារចេញ</label>
                                                    <input type="file" class="form-control" id="files" accept=".xlsx,.pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" name="files" >
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">បដិសេធ</button>
                                                <button type="submit" name="submit" class="btn btn-primary mb-0">រក្សាទុក</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <!-- Input field for search -->
                        <div class="col-md-4 mb-1">
                            <input type="text" id="search" onkeyup="filterTable()" placeholder="ស្វែងរក..." class="form-control">
                        </div>
                        <!-- Form for date range -->
                        <div class="col-md-4 mb-1">
                            <form action="" method="post" class="d-flex" id="filterForm">
                                <div class="form-group me-1">
                                    <input type="text" id="dates" name="fromDate" class="form-control" placeholder="ចាប់ពីថ្ងៃខែឆ្នាំ" value="<?php echo isset($_POST['fromDate']) ? htmlspecialchars($_POST['fromDate']) : ''; ?>">
                                </div>
                                <div class="form-group me-1">
                                    <input type="text" id="dates" name="toDate" class="form-control" placeholder="ដល់ថ្ងៃទីខែឆ្នាំ" value="<?php echo isset($_POST['toDate']) ? htmlspecialchars($_POST['toDate']) : ''; ?>">
                                </div>
                                <div class="form-group me-1">
                                    <button type="submit" class="btn btn-icon btn-secondary"><i class='bx bx-search'></i></button>
                                </div>
                            </form>
                        </div>
                        <!-- Export button -->
                        <?php
                        // Define $fromDate and $toDate variables here
                        $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : ''; // Example, replace with your actual value
                        $toDate = isset($_POST['toDate']) ? $_POST['toDate'] : ''; // Example, replace with your actual value
                        ?>
                        <div class="col-md-4 mb-1 text-end">
                            <form method="POST" action="export_scriptin.php" id="filterOutdocument">
                                <input type="hidden" name="documentType" value="outdocument">
                                <input type="hidden" name="fromDate" value="<?php echo $fromDate; ?>">
                                <input type="hidden" name="toDate" value="<?php echo $toDate; ?>">
                                <button id="exportButton" type="sumbit" class="btn btn-primary">
                                    <span class="text-white"><i class="bx bx-export me-1"></i>Export</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- show datatable -->
            <div class="col-12 col-lg-12 order-2 order-md-3 order-lg-2 mb-4">
                <div class="card">
                    <div class="card-datatable dataTable_select text-nowrap pb-2">
                        <div id="DataTables_Table_3_wrapper">
                            <div class="table-responsive">
                                <table class="datatables-ajax dt-select-table table dataTable no-footer dt-checkboxes-select" id="example" aria-describedby="DataTables_Table_3_info" style="width: 1416px;">
                                    <thead>
                                        <tr>
                                            <th>ល.រ</th>
                                            <th>លេខឯកសារ</th>
                                            <th>កម្មវត្តុ</th>
                                            <th>បញ្ចូនទៅនាយកដ្ឋាន</th>
                                            <th>មន្រ្តីទទួល</th>
                                            <th>ឯកសារចេញ</th>
                                            <th>កាលបរិច្ឆេទ</th>
                                            <th>សកម្មភាព</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($searchResults)) {
                                            $cnt = 1;
                                            foreach ($searchResults as $row) {
                                        ?>
                                                <tr>
                                                    <td class="text-sm font-weight-bold text-center mb-0"><b><?php echo htmlentities($cnt); ?></b></td>
                                                    <td>
                                                        <div class=" d-inline-block text-truncate" style="max-width:180px;"><?php echo $row['CodeId'] ?>
                                                    </td>
                                                    <td>
                                                        <div class=" d-inline-block text-truncate" style="max-width:180px;" data-bs-toggle="tooltip" title="<?php echo htmlentities($row['Type']); ?>"><?php echo $row['Type'] ?></div>
                                                    </td>
                                                    <td>
                                                        <div class=" d-inline-block text-truncate" style="max-width:180px;" data-bs-toggle="tooltip" title="<?php echo htmlentities($row['OutDepartment']); ?>"><?php echo $row['OutDepartment'] ?></div>
                                                    </td>
                                                    <td><?php echo $row['NameOFReceive'] ?></td>
                                                    <td><a href="../../uploads/file/out-doc/<?php echo $row['Typedocument']; ?>" target="blank_" class="btn-sm btn-link h6 mb-0 text-primary ">
                                                            ពិនិត្យមើលឯកសារ
                                                        </a></td>
                                                    <td><?php echo $row['Date'] ?></td>
                                                    <td>
                                                        <div class="d-flex ">
                                                            <button type="button" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; margin: 0 4px; background-color: transparent; border: none;" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['ID']; ?>" data-id="<?php echo $row['ID']; ?>">
                                                                <i class='bx bx-edit-alt' style='color:gray'></i>
                                                            </button>
                                                            <button type="button" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; margin: 0 4px; background-color: transparent; border: none;" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $row['ID']; ?>" data-id="<?php echo $row['ID']; ?>">
                                                                <i class='bx bx-show' style='color:blue;'></i>
                                                            </button>
                                                            <a href="#" onclick="confirmDelete(<?php echo $row['ID'] ?>)" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; margin: 0 4px; background-color: transparent; border: none;">
                                                                <i class='bx bx-trash' style='color:#fd0606'></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <!-- Modal edit -->
                                                <div class="modal animate__animated animate__bounceIn" id="editModal<?php echo $row['ID']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title mef2" id="exampleModalLabel4">ក្រែប្រែឯកសារ</h5>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form id="formAccountSettings" method="post" enctype="multipart/form-data">
                                                                    <div class="row">
                                                                        <input type="hidden" name="id" value="<?php echo htmlentities($row['ID']); ?>"> <!-- Hidden input for ID -->
                                                                        <input type="hidden" name="nameofgive" value="<?php echo htmlentities($row['NameOfgive']); ?>"> <!-- Hidden input for NameOfgive -->
                                                                        <input type="hidden" name="fromdepartment" value="<?php echo htmlentities($row['FromDepartment']); ?>"> <!-- Hidden input for FromDepartment -->
                                                                        <input type="hidden" name="current_file" value="<?php echo htmlentities($row['Typedocument']); ?>"> <!-- Hidden input for current file -->
                                                                        <input type="hidden" name="outdepartment" value="<?php echo htmlentities($row['OutDepartment']); ?>"> <!-- Hidden input for OutDepartment -->
                                                                        <input type="hidden" name="nameofreceive" value="<?php echo htmlentities($row['NameOFReceive']); ?>"> <!-- Hidden input for NameOFReceive -->
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="code" class="form-label">លេខឯកសារ</label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-book'></i></span>
                                                                                <input class="form-control" type="text" id="code" name="code" value="<?php echo htmlentities($row['CodeId']); ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="type" class="form-label">កម្មវត្តុ</label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-detail'></i></span>
                                                                                <input class="form-control" type="text" id="type" name="type" value="<?php echo htmlentities($row['Type']); ?>">
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="outdepartment" class="form-label">បញ្ចូនទៅនាយកដ្ឋាន</label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bxs-business'></i></span>
                                                                                <select class="custom-select form-control form-select rounded-2" name="outdepartment" required>
                                                                                    <option value="<?php echo htmlentities($row['OutDepartment']); ?>"><?php echo htmlentities($row['OutDepartment']); ?></option>
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

                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="nameofreceive" class="form-label">មន្រ្តីទទួល</label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                                                                                <select name="nameofreceive" id="nameofreceive" class="form-select form-control">
                                                                                    <option value="<?php echo htmlentities($row['NameOFReceive']); ?>"><?php echo htmlentities($row['NameOFReceive']); ?></option>
                                                                                    <?php
                                                                                    $sql = "SELECT * FROM tbluser";
                                                                                    $query = $dbh->prepare($sql);
                                                                                    $query->execute();
                                                                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                                                    if ($query->rowCount() > 0) {
                                                                                        foreach ($results as $result) {
                                                                                    ?>
                                                                                            <option value="<?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>">
                                                                                                <?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>
                                                                                            </option>
                                                                                    <?php }
                                                                                    } ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="nameofgive" class="form-label">មន្រ្តី​ប្រគល់</label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                                                                                <select name="nameofgive" id="nameofgive" class="form-select form-control">
                                                                                    <option value="<?php echo htmlentities($row['NameOfgive']); ?>"><?php echo htmlentities($row['NameOfgive']); ?></option>
                                                                                    <?php
                                                                                    $sql = "SELECT * FROM tbluser";
                                                                                    $query = $dbh->prepare($sql);
                                                                                    $query->execute();
                                                                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                                                    if ($query->rowCount() > 0) {
                                                                                        foreach ($results as $result) {
                                                                                    ?>
                                                                                            <option value="<?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>">
                                                                                                <?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>
                                                                                            </option>
                                                                                    <?php }
                                                                                    } ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="fromdepartment" class="form-label">ទទួលពីការិយាល័យ</label>
                                                                            <div class="input-group input-group-merge">
                                                                                <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bxs-business'></i></span>
                                                                                <select class="custom-select form-control form-select rounded-2" name="fromdepartment" required>
                                                                                    <option value="<?php echo htmlentities($row['FromDepartment']); ?>"><?php echo htmlentities($row['FromDepartment']); ?></option>
                                                                                    <?php
                                                                                    // Adjust the query to properly select data from the department and offices tables
                                                                                    $sql = "SELECT  OfficeName FROM tbloffices";
                                                                                    $query = $dbh->prepare($sql);
                                                                                    $query->execute();
                                                                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                                                    if ($query->rowCount() > 0) {
                                                                                        foreach ($results as $result) {
                                                                                    ?>
                                                                                            <option value="<?php echo htmlentities($result->OfficeName); ?>"><?php echo htmlentities($result->OfficeName); ?></option>

                                                                                    <?php
                                                                                        }
                                                                                    }
                                                                                    ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="files" class="form-label">ភ្ជាប់ឯកសារចេញ</label>
                                                                            <div class="input-group">
                                                                                <input type="file" class="form-control" id="files" name="files">
                                                                                <input type="text" class="form-control" value="<?php echo htmlentities($row['Typedocument']); ?>" readonly>
                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">បោះបង់</button>
                                                                        <button type="submit" name="edit" class="btn btn-primary">យល់ព្រម</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Modal view -->
                                                <div class="modal animate__animated animate__bounceIn" id="viewModal<?php echo $row['ID']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                                                        <div class="modal-content ">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title mef2" id="exampleModalLabel4">ពិនិត្យមើលឯកសារ</h5>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form id="formAccountSettings" method="post">
                                                                    <div class="row">

                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="code" class="form-label">លេខឯកសារ</label>
                                                                            <input class="form-control" type="text" id="code" name="code" value="<?php echo htmlentities($row['CodeId']); ?>" disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="type" class="form-label">កម្មវត្តុ</label>
                                                                            <input class="form-control" type="text" id="type" name="type" value="<?php echo htmlentities($row['Type']); ?>" disabled data-bs-toggle="tooltip" title="<?php echo htmlentities($row['Type']); ?>">
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="outdepartment" class="form-label">បញ្ចូនទៅនាយកដ្ឋាន</label>
                                                                            <input class="form-control" type="text" id="outdepartment" name="outdepartment" value="<?php echo htmlentities($row['OutDepartment']); ?>" disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="nameofreceive" class="form-label">មន្រ្តីទទួល</label>
                                                                            <input class="form-control" type="text" id="nameofreceive" name="nameofreceive" value="<?php echo htmlentities($row['NameOFReceive']); ?>" disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="nameofgive" class="form-label">មន្រ្តី​ប្រគល់</label>
                                                                            <input class="form-control" type="text" id="nameofgive" name="nameofgive" value="<?php echo htmlentities($row['NameOfgive']); ?>" disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="fromdepartment" class="form-label">បញ្ចូនពីការិយាល័យ</label>
                                                                            <input class="form-control" type="text" id="fromdepartment" name="fromdepartment" value="<?php echo htmlentities($row['FromDepartment']); ?>" disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="files" class="form-label">ភ្ជាប់ឯកសារចេញ</label>
                                                                            <div class="input-group">
                                                                                <div class="input-group-append">

                                                                                    <div class="d-flex justify-content-between p-2 rounded-3">
                                                                                        <a href="../../uploads/file/out-doc/<?php echo $row['Typedocument']; ?>" target="blank_" class="btn-sm btn-link h6 mb-0">
                                                                                            <i class='bx bx-file me-2'></i>ពិនិត្យមើលឯកសារ
                                                                                        </a>
                                                                                    </div>

                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                    <div class="col-md-12 text-end">
                                                                        <!-- Buttons for editing and deleting -->
                                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">បោះបង់</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php
                                                $cnt++;
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal delete -->
<div id="deleteConfirmationModal" class="modal animate__animated animate__bounceIn" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h1 class="modal-title fs-3 text-center mef2" id="exampleModalLabel">លុបឯកសារ</h1>
            </div>
            <!-- Modal body -->
            <div class="modal-body">
                តើអ្នកយល់ព្រមលុបឯកសារដែរ​ ឬ​ ទេ?
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">មិនយល់ព្រម</button>
                <a id="confirmDeleteButton" href="#" class="btn btn-danger">យល់ព្រម</a>
            </div>
        </div>
    </div>
</div>



<?php
// Get the content from output buffer
$content = ob_get_clean();

// Include layout or template file
include('../../layouts/user_layout.php');
?>
<script>
    setTimeout(() => {
        $('.toast').fadeTo("slow", 0.1, () => {
            $('.toast').alert('close');
        });
    }, 5000);

    (() => {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();

    const confirmDelete = (documentID) => {
        document.getElementById("confirmDeleteButton").href = "?delete=" + documentID;
        $('#deleteConfirmationModal').modal('show');
    };

    const filterTable = () => {
    const filter = document.getElementById("search").value.toUpperCase();
    const table = document.getElementById("example");
    const rows = table.getElementsByTagName("tr");
    let visibleRows = 0;

    Array.from(rows).forEach((row, index) => {
      // Skip the header row (index 0 in most cases)
      if (index === 0) return;

      const cells = row.getElementsByTagName("td");
      const isVisible = Array.from(cells).some(cell =>
        cell.textContent.toUpperCase().includes(filter));

      row.style.display = isVisible ? "" : "none";
      if (isVisible) visibleRows++;
    });

    // Show or hide the "No recent activities found" message
    let noDataMessage = document.getElementById("no-data-message");
    if (!noDataMessage) {
      noDataMessage = document.createElement("div");
      noDataMessage.id = "no-data-message";
      noDataMessage.textContent = "មិនមានទិន្នន័យទេ។";
      noDataMessage.style.textAlign = "center";
      noDataMessage.style.marginTop = "10px";
      noDataMessage.style.color = "blue";
      table.parentNode.appendChild(noDataMessage); // Place message below the table
    }

    noDataMessage.style.display = visibleRows === 0 ? "block" : "none";
  };

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Flatpickr for fromDate
        flatpickr("#fromDate", {
            enableTime: false, // Set to true if you want to include time selection
            dateFormat: "Y-m-d", // Format of the selected date (matching HTML date input format)
        });

        // Initialize Flatpickr for toDate
        flatpickr("#toDate", {
            enableTime: false, // Set to true if you want to include time selection
            dateFormat: "Y-m-d", // Format of the selected date (matching HTML date input format)
        });


    });


    $(document).ready(function() {
        $('#example').DataTable({
            "searching": false,
            "paging": true,
            "info": true,
            "lengthChange": true
        });
    });
</script>