<?php
session_start();
include('../../config/dbconn.php');

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: ../../index.php');
    exit();
}

// Initialize variables
$id = isset($_GET['ID']) ? intval($_GET['ID']) : null;

if (isset($_POST['delete'])) {
    $delete = "UPDATE indocument SET isdelete = 1 WHERE ID = :id";
    $query = $dbh->prepare($delete);
    $query->bindParam(':id', $id);
    $query->execute();

    if ($query->rowCount() > 0) {
        header("Location: inaudit1.php?msg=Successfully+Deleted&status=success");
        exit();
    } else {
        header("Location: inaudit1.php?msg=Error:+Something+went+wrong&status=failed");
        exit();
    }
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

    $edit = "UPDATE indocument SET
            CodeId = ?,
            Type = ?,
            NameOfgive = ?,
            DepartmentName = ?,
            NameOFReceive = ?,  
            Typedocument = ?,
            `update` = ?  -- Backticks added to escape reserved keyword
        WHERE ID = ?";

    $query = $dbh->prepare($edit);

    // Bind parameters using positional placeholders
    $query->execute([
        $_POST['code'],
        $_POST['type'],
        $_POST['give'],
        $_POST['echonomic'],
        $_POST['recrived'],
        $uploadedFile,  // Use the file name (new or existing)
        $date,  // Use the formatted date
        $id
    ]);

    if ($query->rowCount() > 0) {
        header("Location: showinaudit1.php?ID=" . urlencode($id) . "&msg=Successfully+Edited&status=success");
        exit();
    } else {
        header("Location: showinaudit1.php?ID=" . urlencode($id) . "&msg=Error+Edited&status=failed");
        exit();
    }
}

include('../../includes/translate.php');
$pageTitle = "ឯកសារចូលនាយកដ្ឋានសវនកម្មទី១";
$sidebar = "audit1";
ob_start();
?>
<div class="row">
    <div class="col-md-12">
        <div class="container-xxl flex-grow-1">
            <div class="card mb-2">
                <h5 class="card-header text-primary">ពិនិត្យមើលឯកសារចូល</h5>
                <hr class="my-0">
                <div class="card-body">
                    <form id="formAccountSettings" method="post">
                        <div class="row">
                            <?php
                            $sql = "SELECT * FROM indocument WHERE ID = :id";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':id', $id, PDO::PARAM_INT);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_ASSOC); // Changed variable name to $results

                            foreach ($results as $row) { // Loop through each result
                            ?>
                                <div class="mb-3 col-md-6">
                                    <label for="code" class="form-label">លេខឯកសារ</label>
                                    <input class="form-control" type="text" id="code" name="code" value="<?php echo htmlentities($row['CodeId']) ?>" disabled>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="type" class="form-label">កម្មវត្តុ</label>
                                    <input class="form-control " type="text" id="type" name="type" value="<?php echo htmlentities($row['Type']) ?>" disabled>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="echonomic" class="form-label">ឈ្មោះនាយកដ្ឋាន</label>
                                    <input class="form-control" type="text" id="echonomic" name="echonomic" value="<?php echo htmlentities($row['DepartmentName']) ?>" disabled>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="give" class="form-label">ឈ្មោះមន្រ្តី​ប្រគល់</label>
                                    <input class="form-control" type="text" id="give" name="give" value="<?php echo htmlentities($row['NameOfgive']) ?>" disabled>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="files" class="form-label">ប្រភេទឯកសារចូល</label>
                                    <div class="input-group ">
                                        <div class="input-group-append">
                                            <?php
                                            $sql2 = "SELECT Typedocument FROM indocument WHERE ID = ?";
                                            $stmt2 = $dbh->prepare($sql2);
                                            $stmt2->execute([$id]);
                                            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) { ?>
                                                <div class="d-flex justify-content-between  p-2 rounded-3">
                                                    <a href="../../uploads/file/in-doc/<?php echo $row2['Typedocument']; ?>" target="blank_" class="btn-sm btn-link h6 mb-0">
                                                    <i class='bx bx-file me-2'></i>ពិនិត្យមើលឯកសារ
                                                    </a>

                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label for="recrived" class="form-label">ឈ្មោះមន្រ្តីទទួល</label>
                                    <input class="form-control" type="text" id="recrived" name="recrived" value="<?php echo htmlentities($row['NameOFReceive']) ?>" disabled>
                                </div>
                                <!-- Repeat for other input fields -->
                            <?php
                            }
                            ?>
                        </div>
                        <div class="mt-2">
                            <!-- Button trigger modal -->
                            <div class="col-md-12 text-end">
                                <!-- Buttons for editing and deleting -->
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">កែប្រែ</button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">លុប</button>
                                <a href="iniau.php" class="btn btn-secondary">បោះបង់</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal delete -->
<div class="modal animate__animated animate__bounceIn" id="deleteModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-3 text-center mef2" id="exampleModalLabel">លុបឯកសារ</h1>
            </div>
            <div class="modal-body">
                តើអ្នកយល់ព្រមលុបឯកសារដែរ​ ឬ​ ទេ?
            </div>
            <form id="deleteForm" method="post">
                <input type="hidden" name="id" value="<?php echo htmlentities($row['ID']); ?>">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">មិនយល់ព្រម</button>
                    <button type="submit" name="delete" class="btn btn-danger">យល់ព្រម</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit -->
<div class="modal animate__animated animate__bounceIn" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content ">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel4">ក្រែប្រែឯកសារ</h5>

            </div>
            <div class="modal-body">
                <form id="formAccountSettings" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <?php
                        $sql = "SELECT * FROM indocument WHERE ID = :id";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':id', $id, PDO::PARAM_INT);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($results as $row) {
                        ?>
                            <input type="hidden" name="id" value="<?php echo htmlentities($row['ID']); ?>"> <!-- Hidden input for ID -->
                            <input type="hidden" name="current_file" value="<?php echo htmlentities($row['Typedocument']); ?>"> <!-- Hidden input for current file -->
                            <input type="hidden" name="recrived" value="<?php echo htmlentities($row['NameOFReceive']); ?>"> <!-- Hidden input for ID -->
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
                                <label for="echonomic" class="form-label">ឈ្មោះនាយកដ្ឋាន</label>
                                <div class="input-group input-group-merge">
                                    <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bxs-business'></i></span>
                                    <input class="form-control" type="text" id="echonomic" name="echonomic" value="<?php echo htmlentities($row['DepartmentName']); ?>">
                                </div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="give" class="form-label">ឈ្មោះមន្រ្តី​ប្រគល់</label>
                                <div class="input-group input-group-merge">
                                    <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                                    <input class="form-control" type="text" id="give" name="give" value="<?php echo htmlentities($row['NameOfgive']); ?>">
                                </div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="files" class="form-label">ប្រភេទឯកសារចូល</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="files" name="files">
                                    <input type="text" class="form-control" value="<?php echo htmlentities($row['Typedocument']); ?>" readonly>
                                </div>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="recrived" class="form-label">ឈ្មោះមន្រ្តីទទួល</label>
                                <div class="input-group input-group-merge">
                                    <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                                    <select name="recrived" id="recrived" class="form-select form-control">
                                        <option value="<?php echo htmlentities($row['NameOFReceive']); ?>"><?php echo htmlentities($row['NameOFReceive']); ?></option>
                                        <?php
                                        $sql = "SELECT * FROM tbluser";
                                        $query = $dbh->prepare($sql);
                                        $query->execute();
                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                        if ($query->rowCount() > 0) {
                                            foreach ($results as $result) {
                                        ?>
                                                <option value="<?php echo htmlentities($result->UserName); ?>"><?php echo htmlentities($result->UserName); ?></option>
                                        <?php }
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
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
<!-- HTML code for displaying completed and rejected requests -->

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
    }, 1000);

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
</script>