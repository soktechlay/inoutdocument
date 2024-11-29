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
$pageTitle = "ឯកសារចេញការិយាល័យសវនកម្មទី៣";
$sidebar = "outofaudit3";
$userId = $_SESSION['userid'];
date_default_timezone_set('Asia/Bangkok');
$date = date('Y-m-d H:i:s');
// Handle form submission
$sql = "SELECT * FROM outdocument o
        JOIN tbluser u ON o.user_id = u.ID
        WHERE o.isdelete = 0 
        AND o.permissions = 10"; // Corrected the table alias and permissions field
// Initialize an array for the query parameters
$params = [];
// Handle search query
if (isset($_GET['search'])) {
    $searchKeyword = '%' . $_GET['search'] . '%';
    $sql .= " AND (o.CodeId LIKE :searchKeyword OR o.Type LIKE :searchKeyword OR o.DepartmentName LIKE :searchKeyword)";
    $params[':searchKeyword'] = $searchKeyword;
}
// Handle form filters and date range
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fromDate'], $_POST['toDate'])) {
    $fromDate = date('Y-m-d 00:00:00', strtotime($_POST['fromDate']));
    $toDate = date('Y-m-d 23:59:59', strtotime($_POST['toDate']));
    $sql .= " AND o.Date BETWEEN :fromDate AND :toDate";
    $params[':fromDate'] = $fromDate;
    $params[':toDate'] = $toDate;
}
// Finalize SQL query with ORDER BY
$sql .= " ORDER BY o.id DESC"; // Corrected to use table alias 'o'
// Prepare and execute the SQL query
$query = $dbh->prepare($sql);
// Execute the query with parameters
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
                    <h4 class="py-3 mb-1 text-primary"><span
                            class="text-muted fw-light ">ការិយាល័យសវនកម្មទី៣/</span>ឯកសារចេញ</h4>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <!-- Input field for search -->
                        <div class="col-md-4 mb-1">
                            <input type="text" id="search" onkeyup="filterTable()" placeholder="ស្វែងរក..."
                                class="form-control">
                        </div>
                        <!-- Form for date range -->
                        <div class="col-md-4 mb-1">
                            <form action="" method="post" class="d-flex" id="filterForm">
                                <div class="form-group me-1">
                                    <input type="text" id="dates" name="fromDate" class="form-control"
                                        placeholder="ចាប់ពីថ្ងៃខែឆ្នាំ"
                                        value="<?php echo isset($_POST['fromDate']) ? htmlspecialchars($_POST['fromDate']) : ''; ?>">
                                </div>
                                <div class="form-group me-1">
                                    <input type="text" id="dates" name="toDate" class="form-control"
                                        placeholder="ដល់ថ្ងៃទីខែឆ្នាំ"
                                        value="<?php echo isset($_POST['toDate']) ? htmlspecialchars($_POST['toDate']) : ''; ?>">
                                </div>
                                <div class="form-group me-1">
                                    <button type="submit" class="btn btn-icon btn-secondary"><i
                                            class='bx bx-search'></i></button>
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
                                <table
                                    class="datatables-ajax dt-select-table table dataTable no-footer dt-checkboxes-select"
                                    id="example" aria-describedby="DataTables_Table_3_info" style="width: 1416px;">
                                    <thead>
                                        <tr>
                                            <th>ល.រ</th>
                                            <th>លេខឯកសារ</th>
                                            <th>កម្មវត្តុ</th>
                                            <th>បញ្ចូនទៅនាយកដ្ឋាន</th>
                                            <th>មន្រ្តីទទួល</th>
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
                                                    <td class="text-sm font-weight-bold text-center mb-0">
                                                        <b><?php echo htmlentities($cnt); ?></b>
                                                    </td>
                                                    <td>
                                                        <div class=" d-inline-block text-truncate" style="max-width:180px;">
                                                            <?php echo $row['CodeId'] ?>
                                                    </td>
                                                    <td>
                                                        <div class=" d-inline-block text-truncate" style="max-width:180px;"
                                                            data-bs-toggle="tooltip"
                                                            title="<?php echo htmlentities($row['Type']); ?>">
                                                            <?php echo $row['Type'] ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class=" d-inline-block text-truncate" style="max-width:180px;"
                                                            data-bs-toggle="tooltip"
                                                            title="<?php echo htmlentities($row['OutDepartment']); ?>">
                                                            <?php echo $row['OutDepartment'] ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $row['NameOFReceive'] ?></td>
                                                    <td><?php echo $row['Date'] ?></td>
                                                    <td>
                                                        <div class="d-flex ">
                                                            <button type="button"
                                                                style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; margin: 0 4px; background-color: transparent; border: none;"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#viewModal<?php echo $row['ID']; ?>"
                                                                data-id="<?php echo $row['ID']; ?>">
                                                                <i class='bx bx-show text-success'></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <!-- Modal view -->
                                                <div class="modal animate__animated animate__bounceIn"
                                                    id="viewModal<?php echo $row['ID']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                                                        <div class="modal-content ">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title mef2" id="exampleModalLabel4">
                                                                    ពិនិត្យមើលឯកសារ</h5>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form id="formAccountSettings" method="post">
                                                                    <div class="row">
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="code"
                                                                                class="form-label">លេខឯកសារ</label>
                                                                            <input class="form-control" type="text" id="code"
                                                                                name="code"
                                                                                value="<?php echo htmlentities($row['CodeId']); ?>"
                                                                                disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="type"
                                                                                class="form-label">កម្មវត្តុ</label>
                                                                            <input class="form-control" type="text" id="type"
                                                                                name="type"
                                                                                value="<?php echo htmlentities($row['Type']); ?>"
                                                                                disabled data-bs-toggle="tooltip"
                                                                                title="<?php echo htmlentities($row['Type']); ?>">
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="outdepartment"
                                                                                class="form-label">បញ្ចូនទៅនាយកដ្ឋាន</label>
                                                                            <input class="form-control" type="text"
                                                                                id="outdepartment" name="outdepartment"
                                                                                value="<?php echo htmlentities($row['OutDepartment']); ?>"
                                                                                disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="nameofreceive"
                                                                                class="form-label">មន្រ្តីទទួល</label>
                                                                            <input class="form-control" type="text"
                                                                                id="nameofreceive" name="nameofreceive"
                                                                                value="<?php echo htmlentities($row['NameOFReceive']); ?>"
                                                                                disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="nameofgive"
                                                                                class="form-label">មន្រ្តី​ប្រគល់</label>
                                                                            <input class="form-control" type="text"
                                                                                id="nameofgive" name="nameofgive"
                                                                                value="<?php echo htmlentities($row['NameOfgive']); ?>"
                                                                                disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="fromdepartment"
                                                                                class="form-label">បញ្ចូនពីការិយាល័យ</label>
                                                                            <input class="form-control" type="text"
                                                                                id="fromdepartment" name="fromdepartment"
                                                                                value="<?php echo htmlentities($row['FromDepartment']); ?>"
                                                                                disabled>
                                                                        </div>
                                                                        <div class="mb-3 col-md-6">
                                                                            <label for="files"
                                                                                class="form-label">ភ្ជាប់ឯកសារចេញ</label>
                                                                            <div class="input-group">
                                                                                <div class="input-group-append">
                                                                                    <div
                                                                                        class="d-flex justify-content-between p-2 rounded-3">
                                                                                        <a href="../../uploads/file/out-doc/<?php echo $row['Typedocument']; ?>"
                                                                                            target="blank_"
                                                                                            class="btn-sm btn-link h6 mb-0 text-primary">
                                                                                            <i
                                                                                                class='bx bx-file me-2'></i>ពិនិត្យមើលឯកសារ
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-12 text-end">
                                                                        <!-- Buttons for editing and deleting -->
                                                                        <button type="button" class="btn btn-outline-secondary"
                                                                            data-bs-dismiss="modal">បោះបង់</button>
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
<?php
// Get the content from output buffer
$content = ob_get_clean();
// Include layout or template file
include('../../layouts/user_layout.php');
?>
<script>
    // Automatically close toast notifications after 5 seconds
    setTimeout(() => {
        $('.toast').fadeTo("slow", 0.1, () => {
            $('.toast').alert('close');
        });
    }, 5000);
    // Bootstrap form validation
    (() => {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    })();
    // Table filter
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
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize Flatpickr for date inputs
        flatpickr("#fromDate, #toDate", { enableTime: false, dateFormat: "Y-m-d" });

        // Initialize DataTable
        $('#example').DataTable({ "searching": false, "paging": true, "info": true, "lengthChange": true });
    });
</script>