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
    <h3 class="mb-0"><?php echo translate('welcome') ?>,<span
            class="mef2 text-primary mx-2 me-0 mb-0"><?php echo $_SESSION['username'] ?></span></h3>
    <div class="dropdown">
        <?php
        date_default_timezone_set('Asia/Bangkok');
        ?>
        <button class="btn btn-primary">
            <i class="bx bx-calendar me-2"></i>
            <span id="real-time-clock"><?php echo date('D-m-Y h:i:s A'); ?></span>
        </button>
    </div>
</div>
<script>
    function updateDateTime() {
        const clockElement = document.getElementById('real-time-clock');
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

    // Update the date and time every second (1000 milliseconds).
    setInterval(updateDateTime, 1000);

    // Initial update.
    updateDateTime();
</script>
<?php
// Start session if it's not already started


if (!isset($_SESSION['userid'])) {
    echo "You are not logged in.";
} else {
    echo "Welcome, " . $_SESSION['username'] . ". Your office is: " . ($_SESSION['permission'] ?? "N/A");
}
?>

<?php

// Ensure the 'Permission' session values are available
$departmentIds = isset($data['PermissionId']) ? explode(',', $data['PermissionId']) : [];
$departmentIds = array_map('trim', $departmentIds);

// Prepare and execute a query to get the PermissionId based on user ID
$stmt = $dbh->prepare("SELECT PermissionId FROM tbluser WHERE id = ?");
$stmt->execute([$_SESSION['userid']]);

// Fetch the permission data
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug output to confirm fetched PermissionId
echo "<pre>";
print_r($data); // Check the fetched data
echo "</pre>";

if ($data && isset($data['PermissionId'])) {
    // Process the PermissionId into an array
    $departmentIds = explode(',', $data['PermissionId']);
    $departmentIds = array_map('trim', $departmentIds); // Remove any extra spaces
    
    // Debug output to verify the department IDs
    echo "<pre>";
    print_r($departmentIds); // Check if department IDs are being split correctly
    echo "</pre>";
} else {
    echo "No department data found for the user.";
}

// Check if the user's permission is in the allowed departments list
if (in_array($_SESSION['permission'], $departmentIds)):
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
    function fetchDocuments() {
        // Fetch incoming documents
        $.ajax({
            url: 'realtime.php?type=in',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                $('#documentCount').text('សកម្មភាពឯកសារចូលថ្ងៃនេះ (' + (data.count || 0) + ')');

                let rows = '';
                if (data.documents && data.documents.length > 0) {
                    // Loop through documents and populate rows
                    data.documents.forEach(function (doc) {
                        rows += `<tr>
                        <td class="text-truncate" style="max-width:100px;">${htmlspecialchars(doc.CodeId)}</td>
                        <td class="text-truncate" style="max-width:100px;">${htmlspecialchars(doc.DepartmentName)}</td>
                        <td class="text-truncate" style="max-width:100px;">${htmlspecialchars(doc.NameOfgive)}</td>
                        <td>${doc.formattedDate}</td>
                        <td class="text-truncate" style="max-width:100px;">
                            <a href="../../uploads/file/in-doc/${htmlspecialchars(doc.Typedocument)}" target="_blank">Download</a>
                        </td>
                    </tr>`;
                    });
                } else {
                    // If no documents, display the "No recent activities" message
                    rows = `<tr>
                        <td colspan='5'>
                            <div class='text-center'>
                                <img src='../../assets/img/illustrations/empty-box.png' alt='No Requests Found' style='max-width: 15%; height: auto;' />
                                <h5 class='text-muted mt-3'>No recent activities found.</h5>
                            </div>
                        </td>
                    </tr>`;
                }
                // Update the document rows
                $('#documentRows').html(rows);
            },
            error: function (xhr, status, error) {
                console.error('AJAX error: ', status, error);
            }
        });

        // Fetch outgoing documents (repeat similar structure)
        $.ajax({
            url: 'realtime.php?type=out',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                $('#outdocumentCount').text('សកម្មភាពឯកសារចេញថ្ងៃនេះ (' + (data.count || 0) + ')');

                let rows = '';
                if (data.documents && data.documents.length > 0) {
                    data.documents.forEach(function (doc) {
                        rows += `<tr>
                        <td class="text-truncate" style="max-width:100px;">${htmlspecialchars(doc.CodeId)}</td>
                        <td class="text-truncate" style="max-width:100px;">${htmlspecialchars(doc.OutDepartment)}</td>
                        <td class="text-truncate" style="max-width:100px;">${htmlspecialchars(doc.NameOFReceive)}</td>
                        <td>${doc.formattedDate}</td>
                        <td class="text-truncate" style="max-width:100px;">
                            <a href="../../uploads/file/out-doc/${htmlspecialchars(doc.Typedocument)}" target="_blank">Download</a>
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
                $('#outdocumentRows').html(rows);
            },
            error: function (xhr, status, error) {
                console.error('AJAX error: ', status, error);
            }
        });
    }

    // Initialize fetch
    fetchDocuments();
    // Set interval to fetch documents every 5 seconds
    setInterval(fetchDocuments, 5000);

    // HTML escape function to prevent XSS
    function htmlspecialchars(string) {
        return string.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});

    </script>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include('../../layouts/user_layout.php'); ?>