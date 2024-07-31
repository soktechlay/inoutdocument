<?php
session_start();
include('../../config/dbconn.php');

// Redirect if user is not logged in
if (!isset($_SESSION['userid'])) {
  header('Location: ../../index.php');
  exit();
}

// Include translation functionality
include('../../includes/translate.php');

$pageTitle = "ឯកសារចូលអង្គភាពសវនកម្មផ្ទៃក្នុង";
$sidebar = "iau";
$userId = $_SESSION['userid'];
date_default_timezone_set('Asia/Bangkok');
$date = date('Y-m-d H:i:s');

// Fetch the submitted code from the form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
  // Ensure file upload configuration allows large files
  $code = htmlspecialchars(trim($_POST['code']));
  $userid = isset($_SESSION['userid']) ? intval($_SESSION['userid']) : 0;

  // Check if the code already exists in the database
  $sql_check = "SELECT * FROM indocument WHERE CodeId = :code AND isdelete = 0 AND user_id = :userid";
  $query_check = $dbh->prepare($sql_check);
  $query_check->bindParam(':code', $code, PDO::PARAM_STR);
  $query_check->bindParam(':userid', $userid, PDO::PARAM_INT);
  $query_check->execute();

  if ($query_check->rowCount() > 0) {
      // Redirect with an error message
      $error = "លេខឯកសារនេះបានបញ្ចូលរួចហើយ។";
      header("Location: iniau.php?msg=" . urlencode($error) . "&status=error");
      exit();
  } else {
      if (isset($_FILES['files']) && $_FILES['files']['error'] == UPLOAD_ERR_OK) {
          // Fetch and sanitize file details
          $fileTmpPath = $_FILES['files']['tmp_name'];
          $fileName = $_FILES['files']['name'];
          $fileSize = $_FILES['files']['size'];
          $fileError = $_FILES['files']['error'];
          $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
          $newFileName = preg_replace("/[^a-zA-Z0-9_\-\.]/", "", pathinfo($fileName, PATHINFO_FILENAME)) . '.' . $fileExtension;
          $uploadFileDir = '../../uploads/file/in-doc/';
          $dest_path = $uploadFileDir . $newFileName;

          // Check file upload errors and validate file size
          if ($fileError === UPLOAD_ERR_OK) {
              if (!is_dir($uploadFileDir)) {
                  mkdir($uploadFileDir, 0755, true);
              }

              $maxFileSize = 100 * 1024 * 1024; // 100MB
              if ($fileSize > $maxFileSize) {
                  $error = 'File size exceeds the maximum limit of 100MB.';
                  header("Location: iniau.php?msg=" . urlencode($error) . "&status=error");
                  exit();
              }

              // Move uploaded file
              if (move_uploaded_file($fileTmpPath, $dest_path)) {
                  $data = [
                      ':code' => $code,
                      ':type' => htmlspecialchars(trim($_POST['type'])),
                      ':echonomic' => htmlspecialchars(trim($_POST['echonomic'])),
                      ':give' => htmlspecialchars(trim($_POST['give'])),
                      ':recrived' => htmlspecialchars(trim($_POST['recrived'])),
                      ':file_name' => $newFileName,
                      ':date' => date('Y-m-d H:i:s'),
                      ':userid' => $userid,
                      ':department' => 1
                  ];

                  $sql_insert = "INSERT INTO indocument (CodeId, Type, DepartmentName, NameOfgive, NameOFReceive, Typedocument, Date, user_id, Department)
                                 VALUES (:code, :type, :echonomic, :give, :recrived, :file_name, :date, :userid, :department)";
                  $query_insert = $dbh->prepare($sql_insert);

                  try {
                      $query_insert->execute($data);
                      $msg = $query_insert->rowCount() ? "Successfully submitted!" : "Error inserting data into the database.";
                      header("Location: iniau.php?msg=" . urlencode($msg) . "&status=success");
                      exit();
                  } catch (PDOException $e) {
                      $error = "Error: " . $e->getMessage();
                      header("Location: iniau.php?msg=" . urlencode($error) . "&status=error");
                      exit();
                  }
              } else {
                  $error = "Error uploading file.";
                  header("Location: iniau.php?msg=" . urlencode($error) . "&status=error");
                  exit();
              }
          } else {
              $error_messages = [
                  UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                  UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                  UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                  UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                  UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                  UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                  UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
              ];

              $error = isset($error_messages[$fileError]) ? $error_messages[$fileError] : 'Unknown upload error.';
              header("Location: iniau.php?msg=" . urlencode($error) . "&status=error");
              exit();
          }
      } else {
          $error = 'No file was uploaded or there was an upload error.';
          header("Location: iniau.php?msg=" . urlencode($error) . "&status=error");
          exit();
      }
  }
}


// Handle document deletion
if (isset($_GET['delete'])) {
  $sql = "UPDATE indocument SET isdelete = 1 WHERE ID = :documentID";
  $query = $dbh->prepare($sql);
  $query->bindParam(':documentID', $_GET['delete'], PDO::PARAM_INT);
  $query->execute();

  if ($query->rowCount()) {
    $msg = "Document deleted successfully!";
    // Redirect with success message
    header("Location: iniau.php?msg=" . urlencode($msg) . "&status=success");
    exit();
  } else {
    $error = "Failed to delete document.";
    // Redirect with error message
    header("Location: iniau.php?msg=" . urlencode($error) . "&status=error");
    exit();
  }
}

// Construct base SQL query for fetching documents
$sql = "SELECT * FROM indocument 
        JOIN tbluser ON indocument.user_id = tbluser.id 
        WHERE tbluser.id = :userid 
        AND indocument.isdelete = 0
         AND indocument.Department = 1";

$params = [':userid' => $userId];

// Handle search functionality
if (isset($_GET['search'])) {
  $searchKeyword = '%' . $_GET['search'] . '%';
  $sql .= " AND (CodeId LIKE :searchKeyword OR Type LIKE :searchKeyword OR DepartmentName LIKE :searchKeyword)";
  $params[':searchKeyword'] = $searchKeyword;
}

// Handle form filters and date range
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fromDate'], $_POST['toDate'])) {
  $fromDate = date('Y-m-d 00:00:00', strtotime($_POST['fromDate']));
  $toDate = date('Y-m-d 23:59:59', strtotime($_POST['toDate']));
  $sql .= " AND indocument.Date BETWEEN :fromDate AND :toDate";
  $params[':fromDate'] = $fromDate;
  $params[':toDate'] = $toDate;
}
if (isset($_POST['edit'])) {
  $id = $_POST['id'];

  // Correctly format the date
  date_default_timezone_set('Asia/Bangkok');
  $date = date('Y-m-d H:i:s');

  // Initialize variables for file names
  $uploadedFile = $_POST['current_file'];
  $uploadedFile1 = $_POST['current_file1'];

  // Handle the first file upload
  if (isset($_FILES['files']) && $_FILES['files']['error'] == UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['files']['tmp_name'];
    $fileName = $_FILES['files']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Sanitize file name
    $newFileName = preg_replace("/[^a-zA-Z0-9_\-\.]/", "", pathinfo($fileName, PATHINFO_FILENAME)) . '.' . $fileExtension;

    // Directory where you want to save the uploaded file
    $uploadFileDir = '../../uploads/file/in-doc/';
    $dest_path = $uploadFileDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
      $uploadedFile = $newFileName;
    } else {
      echo 'There was an error moving the uploaded file.';
    }
  }

  // Handle the second file upload
  if (isset($_FILES['files1']) && $_FILES['files1']['error'] == UPLOAD_ERR_OK) {
    $fileTmpPath1 = $_FILES['files1']['tmp_name'];
    $fileName1 = $_FILES['files1']['name'];
    $fileExtension1 = strtolower(pathinfo($fileName1, PATHINFO_EXTENSION));

    // Sanitize file name
    $newFileName1 = preg_replace("/[^a-zA-Z0-9_\-\.]/", "", pathinfo($fileName1, PATHINFO_FILENAME)) . '.' . $fileExtension1;

    // Directory where you want to save the uploaded file
    $uploadFileDir1 = '../../uploads/file/note-doc/';
    $dest_path1 = $uploadFileDir1 . $newFileName1;

    if (move_uploaded_file($fileTmpPath1, $dest_path1)) {
      $uploadedFile1 = $newFileName1;
    } else {
      echo 'There was an error moving the uploaded file.';
    }
  }

  $edit = "UPDATE indocument SET
              CodeId = ?,
              Type = ?,
              NameOfgive = ?,
              DepartmentName = ?,
              NameOFReceive = ?,
              DepartmentReceive = ?,                
              NameRecipient = ?,
              Typedocument = ?,
              document = ?,
              `update` = ?
          WHERE ID = ?";

  $query = $dbh->prepare($edit);

  // Bind parameters using positional placeholders
  $query->execute([
    $_POST['code'],
    $_POST['type'],
    $_POST['give'],
    $_POST['echonomic'],
    $_POST['recrived'],
    $_POST['department'],
    $_POST['burden'],
    $uploadedFile,  // Use the file name (new or existing) for the first file
    $uploadedFile1, // Use the file name (new or existing) for the second file
    $date,  // Use the formatted date
    $id
  ]);

  if ($query->rowCount() > 0) {
    header("Location: iniau.php?ID=" . urlencode($id) . "&msg=Successfully+Edited&status=success");
    exit();
  } else {
    header("Location: iniau.php?ID=" . urlencode($id) . "&msg=Error+Edited&status=failed");
    exit();
  }
}

// Finalize SQL query with ORDER BY
$sql .= " ORDER BY indocument.id DESC";

// Prepare and execute the SQL query
$query = $dbh->prepare($sql);
$query->execute($params);

// Fetch all results into $searchResults
$searchResults = $query->fetchAll(PDO::FETCH_ASSOC);

// Example usage of $searchResults
ob_start();
?>


<div class="row">
  <div class="col-md-12">
    <div class="container-xl flex-grow-1">
      <div class="d-flex align-items-center justify-content-between">
        <div class="card-header">
          <h4 class="py-3 mb-1 text-primary"><span class="text-muted fw-light ">អង្គភាពសវនកម្មផ្ទៃក្នុង/</span>ឯកសារចូល</h4>
        </div>
        <div class="dt-action-buttons pt-md-0">
          <div class="dt-buttons btn-group flex-wrap ">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal"><span>បញ្ជូលឯកសារចូល</span></button>
          </div>
          <div class="row row-bordered g-0">
            <div class="modal animate__animated animate__bounceIn" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h1 class="modal-title fs-5 mef2" id="exampleModalLabel">ការដាក់បញ្ជូលឯកសារចូល</h1>
                  </div>
                  <div class="modal-body">
                    <form method="POST" class="row g-2 needs-validation" name="example" enctype="multipart/form-data" novalidate>
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
                          <label for="echonomic" class="form-label">មកពីស្ថាប័នឬក្រសួង</label>
                          <div class="input-group input-group-merge">
                            <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bxs-business'></i></span>
                            <input class="form-control" type="text" id="name" name="echonomic" placeholder="បំពេញឈ្មោះស្ថាប័នឬក្រសួង..." required>
                          </div>
                        </div>
                        <div class="mb-3 col-md-6">
                          <label for="give" class="form-label">ឈ្មោះមន្រ្តីប្រគល់</label>
                          <div class="input-group input-group-merge">
                            <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                            <input type="text" class="form-control" id="give" name="give" placeholder="បំពេញឈ្មោះមន្រ្តី​ប្រគល់..." required>
                          </div>
                        </div>
                        <div class="mb-3 col-md-6">
                          <label for="recrived" class="form-label">ឈ្មោះមន្រ្តីទទួល</label>
                          <div class="input-group input-group-merge">
                            <span id="basic-icon-default-company2" class="input-group-text"><i class='bx bx-user'></i></span>
                            <select name="recrived" id="recrived" class="form-select form-control" required>
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
                          <label for="document" class="form-label">ប្រភេទឯកសារចូល</label>
                          <input type="file" class="form-control" id="files" accept=".xlsx,.pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" name="files" required>
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
                  <input type="text" id="fromDate" name="fromDate" class="form-control" placeholder="ចាប់ពីថ្ងៃខែឆ្នាំ" value="<?php echo isset($_POST['fromDate']) ? htmlspecialchars($_POST['fromDate']) : ''; ?>">
                </div>
                <div class="form-group me-1">
                  <input type="text" id="toDate" name="toDate" class="form-control" placeholder="ដល់ថ្ងៃទីខែឆ្នាំ" value="<?php echo isset($_POST['toDate']) ? htmlspecialchars($_POST['toDate']) : ''; ?>">
                </div>
                <div class="form-group me-1">
                  <button type="submit" class="btn btn-icon btn-secondary"><i class='bx bx-search'></i></button>
                </div>
                <!-- <div class="form-group me-1 fw-medium">
                  <button type="button" class="btn btn-icon btn-label-danger btn-reset" id="resetButton">
                    <span><i class='bx bx-refresh'></i></span>
                  </button>
                </div> -->
              </form>
            </div>

            <!-- Export button -->
            <?php
            // Define $fromDate and $toDate variables here
            $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : ''; // Example, replace with your actual value
            $toDate = isset($_POST['toDate']) ? $_POST['toDate'] : ''; // Example, replace with your actual value
            ?>
            <div class="col-md-4 mb-1 text-end">
              <form method="POST" action="export_script.php" id="filterIndocument">
                <input type="hidden" name="documentType" value="indocument">
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
                      <th>មកពីស្ថាប័នឬក្រសួង</th>
                      <th>ឈ្មោះមន្រ្តីប្រគល់</th>
                      <th>ប្រភេទឯកសារចំណារ</th>
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
                            <div class=" d-inline-block text-truncate" style="max-width:180px;"><?php echo htmlentities($row['CodeId']); ?>
                          </td>
                          <td>
                            <div class="d-inline-block text-truncate" style="max-width:180px;" data-bs-toggle="tooltip" title="<?php echo htmlentities($row['Type']); ?>"><?php echo htmlentities($row['Type']); ?></div>
                          </td>
                          <td><?php echo htmlentities($row['DepartmentName']); ?></td>
                          <td><?php echo htmlentities($row['NameOfgive']); ?></td>
                          <td><a class="btn-link link-primary" href="send.php?ID=<?php echo htmlentities($row['ID']); ?>">ផ្ទេរឯកសារ</a></td>
                          <td><?php echo htmlentities($row['Date']); ?></td>
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
                        <!-- Modal view -->
                        <div class="modal animate__animated animate__bounceIn" id="viewModal<?php echo $row['ID']; ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                            <div class="modal-content ">
                              <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel4">ពិនិត្យមើលឯកសារ</h5>
                              </div>
                              <div class="modal-body">
                                <form id="formAccountSettings" method="post">
                                  <div class="row">

                                    <div class="mb-3 col-md-6">
                                      <label for="code" class="form-label">លេខឯកសារ</label>
                                      <input class="form-control" type="text" id="code" name="code" value="<?php echo htmlentities($row['CodeId']) ?>" disabled>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                      <label for="type" class="form-label">កម្មវត្តុ</label>
                                      <input class="form-control " type="text" id="type" name="type" value="<?php echo htmlentities($row['Type']) ?>" data-bs-toggle="tooltip" title="<?php echo htmlentities($row['Type']); ?>" disabled>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                      <label for="echonomic" class="form-label">មកពីស្ថាប័នឬក្រសួង</label>
                                      <input class="form-control" type="text" id="echonomic" name="echonomic" value="<?php echo htmlentities($row['DepartmentName']) ?>" disabled>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                      <label for="give" class="form-label">ឈ្មោះមន្រ្ទី​ប្រគល់</label>
                                      <input class="form-control" type="text" id="give" name="give" value="<?php echo htmlentities($row['NameOfgive']) ?>" disabled>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                      <label for="recrived" class="form-label">ឈ្មោះមន្រ្ទីទទួល</label>
                                      <input class="form-control" type="text" id="recrived" name="recrived" value="<?php echo htmlentities($row['NameOFReceive']) ?>" disabled>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                      <label for="files" class="form-label">ប្រភេទឯកសារចូល</label>
                                      <div class="input-group ">
                                        <div class="input-group-append">

                                          <div class="d-flex  justify-content-between  p-2 rounded-3">
                                            <a href="../../uploads/file/in-doc/<?php echo $row['Typedocument']; ?>" target="blank_" class="btn-sm btn-link h6 mb-0  ">
                                              <i class='bx bx-file me-2'></i>ពិនិត្យមើលឯកសារ
                                            </a>
                                          </div>

                                        </div>
                                      </div>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                      <label for="files" class="form-label">ប្រភេទឯកសារចំណារ</label>
                                      <div class="input-group">
                                        <div class="input-group-append">
                                          <div class="d-flex justify-content-between p-2 rounded-3">
                                            <?php if (!empty($row['document'])) : ?>
                                              <a href="../../uploads/file/note-doc/<?php echo htmlentities($row['document']); ?>" target="_blank" class="btn-sm btn-link h6 mb-0">
                                                <i class='bx bx-file me-2'></i>ពិនិត្យមើលឯកសារ
                                              </a>
                                            <?php else : ?>
                                              <span class="text-muted h6 mb-0">មិនទាន់មានឯកសារ</span>
                                            <?php endif; ?>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                      <label for="department" class="form-label">នាយកដ្ឋានទទួលបន្ទុក</label>
                                      <input class="form-control" type="text" id="department" name="department" value="<?php echo htmlentities($row['DepartmentReceive']) ?>" disabled>
                                    </div>
                                    <div class="mb-3 col-md-6">
                                      <label for="burden" class="form-label">ឈ្មោះមន្រ្តីទទួលបន្ទុកបន្ត</label>
                                      <input class="form-control" type="text" id="burden" name="burden" value="<?php echo htmlentities($row['NameRecipient']) ?>" disabled>
                                    </div>
                                  </div>
                                  <div class="mt-2">
                                    <!-- Button trigger modal -->
                                    <div class="col-md-12 text-end">
                                      <!-- Buttons for editing and deleting -->
                                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">បោះបង់</button>
                                    </div>
                                  </div>
                                </form>
                              </div>
                            </div>
                          </div>
                        </div>
                        <!-- Modal edit -->
                        <div class="modal animate__animated animate__bounceIn" id="editModal<?php echo $row['ID']; ?>"" tabindex=" -1" aria-hidden="true">
                          <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title mef2" id="exampleModalLabel4">ក្រែប្រែឯកសារ</h5>
                              </div>
                              <div class="modal-body">
                                <form id="formAccountSettings" method="post" enctype="multipart/form-data">
                                  <div class="row">
                                    <input type="hidden" name="id" value="<?php echo htmlentities($row['ID']); ?>">
                                    <input type="hidden" name="current_file1" value="<?php echo htmlentities($row['document']); ?>">
                                    <input type="hidden" name="department" value="<?php echo htmlentities($row['DepartmentReceive']); ?>">
                                    <input type="hidden" name="burden" value="<?php echo htmlentities($row['NameRecipient']); ?>"> <!-- Hidden input for ID -->
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
                                      <label for="echonomic" class="form-label">មកពីស្ថាប័នឬក្រសួង</label>
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
                                              <option value="<?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>">
                                                <?php echo htmlentities($result->FirstName . ' ' . $result->LastName); ?>
                                              </option>
                                          <?php }
                                          } ?>
                                        </select>
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
    const tr = document.getElementById("example").getElementsByTagName("tr");
    Array.from(tr).forEach(row => {
      const td = row.getElementsByTagName("td");
      row.style.display = Array.from(td).some(cell =>
        cell.textContent.toUpperCase().includes(filter)) ? "" : "none";
    });
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

    // Reset Button Event Listener
    document.getElementById("resetButton").addEventListener("click", function() {
      const form = document.getElementById("filterForm");
      const fromDateInput = document.getElementById("fromDate");
      const toDateInput = document.getElementById("toDate");

      if (form && fromDateInput && toDateInput) {
        console.log("Reset button clicked. Clearing date inputs and submitting form.");

        // Clear the date input fields
        document.getElementById("fromDate").value = "";
        document.getElementById("toDate").value = "";
        document.getElementById("filterForm").submit();
      } else {
        console.error("Form or date input elements not found.");
      }
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