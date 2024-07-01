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
    <h3 class="mb-0"><?php echo translate('welcome') ?>,<span class="mef2 text-primary mx-2 me-0 mb-0"><?php echo  $_SESSION['username'] ?></span></h3>
    <div class="dropdown">
        <button class="btn btn-primary"><i class="bx bx-calendar me-2"></i><?php echo date('D-m-Y h:i A') ?></button>
    </div>
</div>
<!-- <div class="row">
    
    <div class="col-9 col-sm-12">
        <div class="card mb-4">
            <div class="card-widget-separator-wrapper">
                <div class="card-body card-widget-separator">
                    <div class="row gy-4 gy-sm-1">
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-3 pb-sm-0">
                                <div>
                                    <h3 class="mb-1" data-i18n="10">10</h3>
                                    <p class="mb-0" data-i18n="Leave Taken"><?php echo translate('Leave Taken') ?></p>
                                </div>
                                <span class="badge bg-label-warning rounded p-2 me-sm-4" data-i18n="<i class='bx bx-calendar-check bx-sm'></i>"></span>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-4">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-3 pb-sm-0">
                                <div>
                                    <h3 class="mb-1" data-i18n="5">5</h3>
                                    <p class="mb-0" data-i18n="Leave Approved"><?php echo translate('Leave Approved') ?></p>
                                </div>
                                <span class="badge bg-label-success rounded p-2 me-lg-4" data-i18n="<i class='bx bx-check-double bx-sm'></i>"></span>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start border-end pb-3 pb-sm-0 card-widget-3">
                                <div>
                                    <h3 class="mb-1" data-i18n="2">2</h3>
                                    <p class="mb-0" data-i18n="Leave Rejected"><?php echo translate('Leave Rejected') ?></p>
                                </div>
                                <span class="badge bg-label-danger rounded p-2 me-sm-4" data-i18n="<i class='bx bx-x-circle bx-sm'></i>"></span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h3 class="mb-1" data-i18n="3">3</h3>
                                    <p class="mb-0" data-i18n="Leave This Week"><?php echo translate('Leave This Week') ?></p>
                                </div>
                                <span class="badge bg-label-primary rounded p-2" data-i18n="<i class='bx bx-calendar-event bx-sm'></i>">
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-2 g-4">
    <!-- Activity Card -->
    <div class="col">
        <div class="card h-100">
            <div class="card-header  d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title me-2 mb-0">សកម្មភាពឯកសារចូល</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table border-top mb-1 table-striped">
                        <thead>
                            <tr>
                                <th>មកពីស្ថាប័នឬក្រសួង</th>
                                <th>ឈ្មោះអ្នក​ប្រគល់</th>
                                <th>កាលបរិច្ឆេទ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // Assuming $userId contains the user ID
                                $sql = "SELECT indocument.*, tbluser.username FROM indocument 
                                    JOIN tbluser ON indocument.user_id = tbluser.id 
                                    WHERE tbluser.id = :userid 
                                    AND indocument.isdelete = 0
                                    ORDER BY indocument.Date DESC 
                                    LIMIT 8"; // Adjust the limit as needed

                                $query = $dbh->prepare($sql);
                                $query->bindParam(':userid', $userId, PDO::PARAM_INT);
                                $query->execute();
                                $searchResults = $query->fetchAll(PDO::FETCH_ASSOC);

                                // Check if query returned results
                                if (!empty($searchResults)) {
                                    foreach ($searchResults as $row) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['DepartmentName']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['NameOfgive']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['Date']) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3'><div class='text-center'>
                                        <img src='../../assets/img/illustrations/empty-box.png' alt='No Requests Found' style='max-width: 18%; height: auto;' />
                                        <h5 class='text-muted mt-3'>No recent activities found.</h5>
                                    </div></td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='3'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>







    <!-- progressbar  -->

    <div class="col">
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title me-2 mb-0">សកម្មភាពឯកសារចេញ</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table border-top mb-1 table-striped">
                    <thead>
                        <tr>
                            <th>ចេញទៅស្ថាប័នឬក្រសួង</th>
                            <th>ឈ្មោះអ្នកទទួល</th>
                            <th>កាលបរិច្ឆេទ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Assuming $userId contains the user ID
                            $sql = "SELECT outdocument.*, tbluser.username FROM outdocument 
                                    JOIN tbluser ON outdocument.user_id = tbluser.id 
                                    WHERE tbluser.id = :userid 
                                    AND outdocument.isdelete = 0
                                    ORDER BY outdocument.Date DESC 
                                    LIMIT 5"; // Adjust the limit as needed

                            $query = $dbh->prepare($sql);
                            $query->bindParam(':userid', $userId, PDO::PARAM_INT);
                            $query->execute();
                            $searchResults = $query->fetchAll(PDO::FETCH_ASSOC);

                            // Check if query returned results
                            if (!empty($searchResults)) {
                                foreach ($searchResults as $row) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['OutDepartment']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['NameOFReceive']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['Date']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3'><div class='text-center'>
                                    <img src='../../assets/img/illustrations/empty-box.png' alt='No Requests Found' style='max-width: 15%; height: auto;' />
                                    <h5 class='text-muted mt-3'>No recent activities found.</h5>
                                </div></td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='3'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>







</div>


<?php $content = ob_get_clean(); ?>
<?php include('../../layouts/user_layout.php'); ?>