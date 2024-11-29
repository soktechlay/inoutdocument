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

    <div class="col-12 col-lg-12 order-2 order-md-3 order-lg-2 mb-4">
        <!-- Incoming Documents Card -->
        <div class="col">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title me-2 mb-0" id="documentCount">សកម្មភាពឯកសារចូលថ្ងៃនេះ (0)</h5>
                </div>
                <div class="card-body">
                    <!-- Incoming Documents Table -->
                    <div class="table-responsive">
                        <table class="table border-top mb-1 table-striped" id="documentsTable">
                            <thead>
                                <tr>
                                    <th>លេខឯកសារ</th>
                                    <th>កម្មវត្តុ</th>
                                    <th>ទទួលពីក្រសួង/ស្ថាប័ន</th>
                                    <th>មន្រ្តីប្រគល់</th>
                                    <th>ស្ថានភាពឯកសារ</th> <!-- New Column -->
                                    <th>កាលបរិច្ឆេទ</th>
                                    <th>សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody id="documentRows"></tbody>
                        </table>
                        <div id="inDocPagination" class="pagination d-flex justify-content-center mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Outgoing Documents Card -->
    <div class="col-12 col-lg-12 order-2 order-md-3 order-lg-2 mb-4">
        <div class="col">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title me-2 mb-0 " id="outdocumentCount">សកម្មភាពឯកសារចេញថ្ងៃនេះ (0)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table border-top mb-1 table-striped" id="outdocumentsTable">
                            <thead>
                                <tr>
                                    <th>លេខឯកសារ</th>
                                    <th>កម្មវត្តុ</th>
                                    <th>បញ្ជូនទៅក្រសួង/ស្ថាប័ន</th>
                                    <th>មន្រ្តីទទួល</th>
                                    <th>កាលបរិច្ឆេទ</th>
                                    <th>សកម្មភាព</th>
                                </tr>
                            </thead>
                            <tbody id="outdocumentRows"></tbody>
                        </table>
                        <div id="outDocPagination" class="pagination d-flex justify-content-center mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            const permissions = <?= json_encode($departmentIds, JSON_HEX_TAG | JSON_HEX_QUOT); ?>;
            const rowsPerPage = 5; // Number of rows per page
            let currentPage = 1; // Store the current page for pagination
            let documents = { in: [], out: [] }; // Store documents for both types

            // Fetch documents for incoming or outgoing types
            function fetchDocuments(type) {
                $.ajax({
                    url: `realtime.php?type=${type}`,
                    type: 'POST',
                    data: { permissions: permissions },
                    dataType: 'json',
                    success: function (data) {
                        const countId = type === 'in' ? '#documentCount' : '#outdocumentCount';
                        const tableBody = type === 'in' ? '#documentRows' : '#outdocumentRows';
                        const paginationContainer = type === 'in' ? '#inDocPagination' : '#outDocPagination';
                        const folder = type === 'in' ? 'in-doc' : 'out-doc';

                        // Update document count
                        $(countId).html(`
                                            សកម្មភាពឯកសារ${type === 'in' ? 'ចូល' : 'ចេញ'}ក្នុងថ្ងៃនេះ ចំនួន៖  
                                            <span class="text-danger h2">${data.count || 0}</span>
                                        `);

                        // Store documents for pagination and rendering
                        documents[type] = data.documents || [];

                        // After refreshing the data, re-render the current page (preserve pagination)
                        renderTablePage(type, currentPage); // Render for the current page after refresh
                        renderPagination(type); // Render pagination
                    },
                    error: function (xhr, status, error) {
                        console.error(`${type} documents fetch error:`, error);
                    }
                });
            }

            // Paginate the table for a specific document type (in/out)
            function renderTablePage(type, page) {
                const tableBodySelector = type === 'in' ? '#documentRows' : '#outdocumentRows';
                const folder = type === 'in' ? 'in-doc' : 'out-doc';
                const data = documents[type];
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                const rows = data.slice(start, end).map(doc => `
                                    <tr>
            <!-- Document Code ID -->
            <td>${htmlspecialchars(doc.CodeId)}</td>

            <!-- Document Type with Tooltip -->
            <td>
                <div class="d-inline-block text-truncate" style="max-width: 180px;"
                    data-bs-toggle="tooltip"                    
                    title="${htmlspecialchars(doc.Type || doc.Type)}">
                    ${htmlspecialchars(doc.Type || doc.Type)}
                </div>
            </td>

            <!-- Department Name or OutDepartment -->
            <td>${htmlspecialchars(doc.DepartmentName || doc.OutDepartment)}</td>

            <!-- Name of Giver or Receiver -->
            <td>${htmlspecialchars(doc.NameOfgive || doc.NameOFReceive)}</td>

            <!-- Department Receive (conditionally rendered for in-doc folder) -->
            ${folder === 'in-doc' ? `
                <td>${htmlspecialchars(doc.DepartmentReceive || 'កំពុងពិនិត្យ')}</td>
            ` : ''}

            <!-- Formatted Date -->
            <td>${htmlspecialchars(doc.formattedDate)}</td>

            <!-- View Document Link -->
            <td>
                <a href="../../uploads/file/${folder}/${htmlspecialchars(doc.Typedocument)}" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" 
                        width="24" height="24" viewBox="0 0 24 24" 
                        fill="none" stroke="currentColor" 
                        stroke-width="2" stroke-linecap="round" 
                        stroke-linejoin="round" 
                        class="icon icon-tabler icon-tabler-eye text-success">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                        <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                    </svg>
                </a>
            </td>
        </tr>

                                `).join('');

                // Render table rows or show empty state
                $(tableBodySelector).html(rows || `
                                    <tr>
                                        <td colspan="6">
                                            <div class="text-center">
                                                <img src="../../assets/img/illustrations/empty-box.png" alt="No Requests Found" style="max-width: 15%; height: auto;" />
                                                <h5 class="text-muted mt-3">មិនមានទិន្នន័យ</h5>
                                            </div>
                                        </td>
                                    </tr>
                                `);
            }

            // Render pagination buttons dynamically
            function renderPagination(type) {
                const totalPages = Math.ceil(documents[type].length / rowsPerPage);
                const paginationContainerSelector = type === 'in' ? '#inDocPagination' : '#outDocPagination';

                let paginationHtml = '';
                for (let i = 1; i <= totalPages; i++) {
                    paginationHtml += `
                                        <button class="btn ${i === currentPage ? 'btn-primary' : 'btn-light'} btn-sm mx-1" data-page="${i}">
                                            ${i}
                                        </button>`;
                }

                $(paginationContainerSelector).html(paginationHtml);

                // Attach event listener to pagination buttons
                $(paginationContainerSelector).off('click').on('click', 'button', function () {
                    const selectedPage = parseInt($(this).data('page'));
                    if (selectedPage !== currentPage) {
                        currentPage = selectedPage;
                        // Immediately update pagination button styles
                        renderPagination(type); // Update pagination UI
                        renderTablePage(type, currentPage); // Re-render the table for the selected page
                    }
                });
            }

            // Escape HTML for security
            function htmlspecialchars(string) {
                return String(string).replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // Refresh both incoming and outgoing documents (called once when page loads)
            function refreshDocuments() {
                fetchDocuments('in');
                fetchDocuments('out');
            }

            // Initial document fetch on page load
            refreshDocuments();

            // Set interval for document refresh (every 5 seconds)
            setInterval(function () {
                fetchDocuments('in');  // Refresh incoming documents
                fetchDocuments('out'); // Refresh outgoing documents
            }, 5000);
        });      
    </script>
    
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include('../../layouts/user_layout.php'); ?>