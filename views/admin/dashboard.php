<?php
session_start();
// Include database connection
include('../../config/dbconn.php');
include('../../includes/translate.php');
// Redirect to the index page if the user is not authenticated
if (!isset($_SESSION['userid'])) {
    header('Location: ../../index.php');
    exit();
}
// Set page-specific variables
$pageTitle = "ទំព័រដើម";
$sidebar = "dashboard";
$userId = $_SESSION['userid']; // Assuming the user ID is stored in the session
$_SESSION['prevPageTitle'] = $pageTitle;
ob_start();
// Fetch user activities from the database
?>
<div class="col-12 d-flex align-items-center justify-content-between mb-3">
    <div class="dropdown">
        <?php
        date_default_timezone_set('Asia/Bangkok');
        ?>
        <div class=" text-primary">
            <i class="bx bx-calendar me-2"></i>
            <span id="real-time-clock"><?php echo date('D-m-Y h:i:s A'); ?></span>
        </div>
    </div>
</div>
<script>
    function updateDateTime() {
        const currentTime = new Date();

        // Define Khmer arrays for days of the week and months.
        const daysOfWeek = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
        const dayOfWeek = daysOfWeek[currentTime.getDay()];

        const months = ['មករា', 'កុម្ភៈ', 'មិនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
        const month = months[currentTime.getMonth()];

        const day = currentTime.getDate();
        const year = currentTime.getFullYear();

        // Calculate and format hours, minutes, seconds, and time of day in Khmer.
        let hours = currentTime.getHours();
        let period;

        if (hours >= 5 && hours < 12) {
            period = 'ព្រឹក'; // Khmer for AM (morning)
        } else if (hours >= 12 && hours < 17) {
            period = 'រសៀល'; // Khmer for afternoon
        } else if (hours >= 17 && hours < 20) {
            period = 'ល្ងាច'; // Khmer for evening
        } else {
            period = 'យប់'; // Khmer for night
        }

        hours = hours % 12 || 12;
        const minutes = currentTime.getMinutes().toString().padStart(2, '0');
        const seconds = currentTime.getSeconds().toString().padStart(2, '0');

        // Construct the date and time string in the desired Khmer format.
        const dateTimeString = `${dayOfWeek}, ${day} ${month} ${year} ${hours}:${minutes}:${seconds} ${period}`;
        clockElement.textContent = dateTimeString;
    }
    const clockElement = document.getElementById('real-time-clock');

    // Update the date and time every second (1000 milliseconds).
    setInterval(updateDateTime, 1000);

    // Initial update.
    updateDateTime();
</script>

<?php
// Fetch the user's permissions
$stmt = $dbh->prepare("SELECT PermissionId FROM tbluser WHERE id = ?");
$stmt->execute([$_SESSION['userid']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$departmentIds = [];
if ($data && isset($data['PermissionId'])) {
    // Convert PermissionId to an array
    $departmentIds = explode(',', $data['PermissionId']);
    $departmentIds = array_map('trim', $departmentIds); // Clean up the array
}

// Check if there's an overlap between session permission and database permissions
$userPermissions = isset($_SESSION['permission']) ? explode(',', $_SESSION['permission']) : [];
$userPermissions = array_map('trim', $userPermissions);

$allowed = !empty(array_intersect($departmentIds, $userPermissions));

if ($allowed):
?>

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-2 g-4">
    <!-- Incoming Documents Card -->
    <div class="col">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title me-2 mb-0" id="documentCount">សកម្មភាពឯកសារចូលថ្ងៃនេះ (0)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table border-top mb-1 table-striped" id="documentsTable">
                        <thead>
                            <tr>
                                <th>លេខឯកសារ</th>
                                <th>មកពីស្ថាប័នឬក្រសួង</th>
                                <th>ឈ្មោះមន្រ្តីប្រគល់</th>
                                <th>កាលបរិច្ឆេទ</th>
                                <th>ឯកសារ</th>
                            </tr>
                        </thead>
                        <tbody id="documentRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Outgoing Documents Card -->
    <div class="col">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title me-2 mb-0" id="outdocumentCount">សកម្មភាពឯកសារចេញថ្ងៃនេះ (0)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table border-top mb-1 table-striped" id="outdocumentsTable">
                        <thead>
                            <tr>
                                <th>លេខឯកសារ</th>
                                <th>ចេញទៅស្ថាប័នឬក្រសួង</th>
                                <th>ឈ្មោះមន្រ្តីទទួល</th>
                                <th>កាលបរិច្ឆេទ</th>
                                <th>ឯកសារ</th>
                            </tr>
                        </thead>
                        <tbody id="outdocumentRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
    const permissions = <?= json_encode($departmentIds); ?>; // Correctly fetch PHP permissions array

    function fetchDocuments() {
        // Fetch incoming documents
        $.ajax({
            url: 'realtime.php?type=in',
            type: 'POST',
            data: { permissions: permissions },
            dataType: 'json',
            success: function (data) {
                $('#documentCount').text(`សកម្មភាពឯកសារចូលថ្ងៃនេះ (${data.count || 0})`);
                updateTable(data.documents, '#documentRows', 'in-doc');
            },
            error: function (xhr, status, error) {
                console.error('Incoming documents fetch error:', error);
            }
        });

        // Fetch outgoing documents
        $.ajax({
            url: 'realtime.php?type=out',
            type: 'POST',
            data: { permissions: permissions },
            dataType: 'json',
            success: function (data) {
                $('#outdocumentCount').text(`សកម្មភាពឯកសារចេញថ្ងៃនេះ (${data.count || 0})`);
                updateTable(data.documents, '#outdocumentRows', 'out-doc');
            },
            error: function (xhr, status, error) {
                console.error('Outgoing documents fetch error:', error);
            }
        });
    }

            function updateTable(documents, tableBodySelector, folder) {
                let rows = '';
                if (documents && documents.length > 0) {
                    documents.forEach(function (doc) {
                        rows += `<tr>
                            <td class="truncate-cell">${htmlspecialchars(doc.CodeId)}</td>
                            <td class="truncate-cell">${htmlspecialchars(doc.DepartmentName || doc.OutDepartment)}</td>
                            <td class="truncate-cell">${htmlspecialchars(doc.NameOfgive || doc.NameOFReceive)}</td>
                            <td>${doc.formattedDate}</td>
                            <td class="truncate-cell">
                                <a href="../../uploads/file/${folder}/${htmlspecialchars(doc.Typedocument)}" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-eye text-success">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                </a>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = `<tr>
                        <td colspan='5'>
                            <div class='text-center'>
                                <img src='../../assets/img/illustrations/empty-box.png' alt='No Requests Found' style='max-width: 15%; height: auto;' />
                                <h5 class='text-muted mt-3'>No recent activities found.</h5>
                            </div>
                        </td>
                    </tr>`;
                }
                $(tableBodySelector).html(rows);
            }

            function htmlspecialchars(string) {
                return string.replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // Fetch documents initially and set interval for updates
            fetchDocuments();
            setInterval(fetchDocuments, 5000);
        });
    </script>
</div>


        
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include('../../layouts/user_layout.php'); ?>