<?php

declare(strict_types=1);
session_start();
include('../../config/dbconn.php');

// Redirect to index page if the user is not authenticated
if (!isset($_SESSION['userid'])) {
  header('Location: ../../index.php');
  exit();
}


require '../../vendor/autoload.php';

// Initialize Google Authenticator
$secret = 'XVQ2UIGO75XRUKJO';
$link = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate('Long', $secret, 'Leaves');
$g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();

$pageTitle = "ព័ត៌មានគណនី";
$sidebar = "all-user-permission";
ob_start(); // Start output buffering
include('../../controllers/form_process.php');

// Retrieve user information from the database based on the provided user ID
$getid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;

$sql = "SELECT u.*, r.RoleName, oh.*, d.*
        FROM tbluser u
        LEFT JOIN tblrole r ON u.RoleId = r.id
        LEFT JOIN tbloffices oh ON u.Office = oh.id
        LEFT JOIN tbldepartments d ON u.Department = d.id
        WHERE u.id = :id";
$query = $dbh->prepare($sql);
$query->bindParam(':id', $getid, PDO::PARAM_INT);
$query->execute();
$userData = $query->fetch(PDO::FETCH_ASSOC);

// Function to generate initials from user's name
function generateInitials(string $name): string
{
  $initials = '';
  $nameParts = explode(' ', $name);
  foreach ($nameParts as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
  }
  return $initials;
}

// Check if user data exists
if ($userData) {
  // Your code to display user information goes here
  // Example: echo '<p>' . htmlspecialchars($userData['UserName'], ENT_QUOTES, 'UTF-8') . '</p>';
?>

  <div class="row">
    <div class="col-12">
      <div class="card mb-3">
        <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-4">
          <div class="user-profile-header-banner">
            <!-- Clickable profile banner to change cover -->
            <label for="coverInput" class="profile-image">
              <?php
              $coverImageSrc = !empty($userData['Cover']) ? $userData['Cover'] : '../../assets/img/pages/profile-banner.png';
              ?>
              <img id="coverImage" src="<?php echo htmlentities($coverImageSrc); ?>" alt="Banner image" class="rounded-top" style="height: 310px ; width: 100%; object-fit: cover;">
            </label>
          </div>
        </div>
        <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-4">
          <form id="formAuthentication" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="login_type" value="updatedimg">
            <div class="flex-shrink-0 mt-n5 mx-sm-0 mx-auto">
              <!-- Clickable profile picture to change profile image -->
              <label for="profileInput" class="profile-image">
                <?php if (!empty($userData['Profile'])) : ?>
                  <img src="<?php echo htmlentities($userData['Profile']); ?>" alt="user image" class="d-block h-auto ms-0 ms-sm-5 rounded border p-1 bg-light user-profile-img" height="150" width="150" style="object-fit: cover;">
                <?php else : ?>
                  <!-- Placeholder image or initials -->
                  <span class="avatar-initial rounded-circle bg-label-success">
                    <?php echo generateInitials($userData['FirstName'] . ' ' . $userData['LastName']); ?>
                  </span>
                <?php endif; ?>
              </label>
              <input type="file" name="updateimg" class="d-none" accept="image/*">
            </div>
          </form>
          <div class="flex-grow-1 mt-3 mt-sm-5">
            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-4 flex-md-row flex-column gap-4">
              <div class="user-profile-info">
                <h4 class="mef2 fw-bolder">
                  <?php echo htmlentities($userData['Honorific'] . ' ' . $userData['FirstName'] . ' ' . $userData['LastName']); ?>
                </h4>
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-2">
                  <li class="list-inline-item fw-medium">
                    <i class="bx bx-pen"></i> <?php echo htmlentities($userData['RoleName']); ?>
                  </li>
                  <li class="list-inline-item fw-medium">
                    <i class="bx bx-map"></i> <?php echo htmlentities($userData['Address']); ?>
                  </li>
                  <li class="list-inline-item fw-medium">
                    <i class="bx bx-calendar-alt"></i> Joined
                    <?php
                    if (!empty($userData['CreationDate'])) {
                      echo date('D F Y', strtotime($userData['CreationDate']));
                    } else {
                      echo 'Date not available';
                    }
                    ?>
                  </li>
                </ul>
              </div>
              <!-- Additional buttons or actions -->
              <a href="#" class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#profileModal">
                <i class="bx bx-user-check me-1"></i> View Profile
              </a>
              <!-- Profile Modal -->
              <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="profileModalLabel">User Profile</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <!-- PHP code to fetch and display user's profile information -->
                      <?php
                      // You may need to adjust this query based on your database schema
                      $sql = "SELECT * FROM tbluser WHERE id = :userId";
                      $query = $dbh->prepare($sql);
                      $query->bindParam(':userId', $getid, PDO::PARAM_INT);
                      $query->execute();
                      $user = $query->fetch(PDO::FETCH_ASSOC);

                      // Display fetched profile information within a form for editing
                      if ($user) {
                      ?>
                        <form id="editProfileForm" method="POST" enctype="multipart/form-data">
                          <input type="hidden" name="login_type" value="updatedimg">
                          <input type="hidden" name="userId" value="<?php echo $getid; ?>">

                          <!-- Profile Picture -->
                          <div class="mb-3 text-center">
                            <!-- Clickable profile picture to change profile image -->
                            <label for="profileInput">
                              <?php if (!empty($user['Profile'])) : ?>
                                <img src="<?php echo htmlentities($user['Profile']); ?>" alt="user image" class="img-fluid rounded-4 profile-image" style="cursor: pointer; object-fit:contain; width: 320px; height: 320px">
                              <?php else : ?>
                                <!-- Placeholder image or initials -->
                                <span class="avatar-initial rounded-circle bg-label-success" style="cursor: pointer;"><?php echo generateInitials($user['FirstName'] . ' ' . $user['LastName']); ?></span>
                              <?php endif; ?>
                            </label>
                            <!-- File input to choose new profile picture -->
                            <input type="file" name="updateimg" id="profileInput" class="d-none" accept="image/*">
                          </div>

                          <!-- Other Information -->
                          <div class="mb-3">
                            <label for="userName" class="form-label">Username:</label>
                            <input type="text" class="form-control" id="userName" name="userName" value="<?php echo htmlentities($user['UserName']); ?>">
                          </div>
                          <div class="mb-3">
                            <label for="firstName" class="form-label">First Name:</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlentities($user['FirstName']); ?>">
                          </div>
                          <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name:</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlentities($user['LastName']); ?>">
                          </div>
                          <!-- Add more fields as needed -->

                          <!-- Modal Footer -->
                          <div class="modal-footer">
                            <!-- Close Button -->
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                          </div>
                        </form>
                      <?php
                      } else {
                        echo "User not found.";
                      }
                      ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-12">
      <ul class="nav nav-pills flex-column flex-sm-row mb-3">
        <!-- Profile Tab (Always Visible) -->
        <li class="nav-item">
          <a class="nav-link <?php if ($sidebar == 'alluser') echo 'active'; ?>" href="all-users-detail.php?uid=<?php echo $getid; ?>">
            <i class='bx bx-user-circle me-2'></i>
            <span data-i18n="profile.title">Profile</span>
          </a>
        </li>
        <!-- Security Tab -->
        <li class="nav-item">
          <a class="nav-link <?php if ($sidebar == 'all-user-security') echo 'active'; ?>" href="all-users-security.php?uid=<?php echo $getid; ?>">
            <i class="bx bx-shield me-1"></i>
            <span data-i18n="security">Security</span>
          </a>
        </li>
        <!-- Permission Tab -->
        <li class="nav-item">
          <a class="nav-link <?php if ($sidebar == 'all-user-permission') echo 'active'; ?>" href="all-users-permission.php?uid=<?php echo $getid; ?>">
            <i class="bx bx-lock me-1"></i>
            <span data-i18n="permission">Permission</span>
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="row">
    <!-- user-detail -->
    <div class="col-xl-4 col-lg-5 col-md-5">
      <!-- About User -->
      <div class="card mb-3">
        <div class="card-body">
          <small class="text-muted text-uppercase" data-i18n="About">About</small>
          <ul class="list-unstyled mb-4 mt-3">
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-user"></i>
              <span class="fw-medium mx-2" data-i18n="Full Name">Full Name:</span>
              <span><?php echo htmlentities(($userData['Honorific'] ?? '') . ' ' . ($userData['FirstName'] ?? '') . ' ' . ($userData['LastName'] ?? '')); ?></span>
            </li>
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-check"></i>
              <span class="fw-medium mx-2" data-i18n="Status">Status:</span>
              <span data-i18n="Active">Active</span>
            </li>
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-star"></i>
              <span class="fw-medium mx-2" data-i18n="Role">Role:</span>
              <span><?php echo htmlentities($userData['RoleName'] ?? ''); ?></span>
            </li>
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-flag"></i>
              <span class="fw-medium mx-2" data-i18n="Address">Address:</span>
              <span><?php echo htmlentities($userData['Address'] ?? ''); ?></span>
            </li>
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-buildings"></i>
              <span class="fw-medium mx-2" data-i18n="Department">Department:</span>
              <span><?php echo htmlentities($userData['DepartmentName'] ?? ''); ?></span>
            </li>
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-building"></i>
              <span class="fw-medium mx-2" data-i18n="Office">Office:</span>
              <span><?php echo htmlentities($userData['OfficeName'] ?? ''); ?></span>
            </li>
          </ul>
          <small class="text-muted text-uppercase" data-i18n="Contacts">Contacts</small>
          <ul class="list-unstyled mb-4 mt-3">
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-phone"></i>
              <span class="fw-medium mx-2" data-i18n="Contact">Contact:</span>
              <span><?php echo htmlentities($userData['Contact'] ?? ''); ?></span>
            </li>
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-envelope"></i>
              <span class="fw-medium mx-2" data-i18n="Email">Email:</span>
              <span><?php echo htmlentities($userData['Email'] ?? ''); ?></span>
            </li>
          </ul>
          <small class="text-muted text-uppercase" data-i18n="Teams">Teams</small>
          <ul class="list-unstyled mt-3 mb-0">
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-user-circle me-2"></i>
              <div class="d-flex flex-wrap">
                <span class="fw-medium mx-2" data-i18n="Head Of Department">Head Of Department:</span>
                <span class="fw-medium me-2 mef2"><?php echo htmlentities($userData['HeadOfDepartment'] ?? ''); ?></span>
              </div>
            </li>
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-user-circle me-2"></i>
              <div class="d-flex flex-wrap">
                <span class="fw-medium mx-2" data-i18n="Deputy Head Of Department">Deputy Head Of Department:</span>
                <span class="fw-medium me-2 mef2"><?php echo htmlentities($userData['DepHeadOfDepartment'] ?? ''); ?></span>
              </div>
            </li>
            <li class="d-flex align-items-center mb-3">
              <i class="bx bx-user-circle me-2"></i>
              <div class="d-flex flex-wrap">
                <span class="fw-medium mx-2" data-i18n="Head Of Office">Head Of Office:</span>
                <span class="fw-medium me-2 mef2"><?php echo htmlentities($userData['HeadOfOffice'] ?? ''); ?></span>
              </div>
            </li>
            <li class="d-flex align-items-center">
              <i class="bx bx-user-circle me-2"></i>
              <div class="d-flex flex-wrap">
                <span class="fw-medium mx-2" data-i18n="Deputy Head Of Office">Deputy Head Of Office:</span>
                <span class="fw-medium me-2 mef2"><?php echo htmlentities($userData['DepHeadOffice'] ?? ''); ?></span>
              </div>
            </li>
          </ul>
        </div>
      </div>
      <!--/ About User -->
    </div>
    <!-- end-user-detail -->
    <!-- user-security -->
    <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
      <!-- Project table -->
      <div class="card mb-4">
        <!-- Notifications -->
        <div class="card-header">
          <h5 class="card-title">ការអនុញ្ញាតបច្ចុប្បន្ន</h5>
          <span class="card-subtitle">ប្រសិនបើធ្វើការផ្លាស់ប្តរូការអនុញ្ញាតគណនីរបស់ថ្នាក់ដឹកនាំនិងមន្រ្តីនិងមានមុខងារបន្ថែមឬបន្ថយទៅតាមការអនុញ្ញាតនិមួយៗ។</span>
        </div>
        <form id="formAuthentication" class="row g-3 mb-3" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="login_type" value="update-permission">
          <input type="hidden" name="updateinout" value="<?php echo htmlentities((string)$getid); ?>">
          <div class="table-responsive">
            <table class="table border-top mb-1 table-striped">
              <thead>
                <tr>
                  <th class="text-nowrap">Type</th>
                  <th class="text-nowrap text-center">Permission</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Ensure $dbh (PDO instance) and $getid (user ID) are properly defined and initialized
                // Query to fetch permissions from tbluser for the user
                $sqlUser = "SELECT iau, general, audit1, audit2, hr, training, it, ofaudit1, ofaudit2, ofaudit3, ofaudit4 FROM tbluser WHERE id = :getid";
                $queryUser = $dbh->prepare($sqlUser);
                $queryUser->bindParam(':getid', $getid, PDO::PARAM_INT);
                $queryUser->execute();
                $user = $queryUser->fetch(PDO::FETCH_ASSOC);

                // Display user-specific permissions if user exists
                if ($user) :
                  $userPermissions = [
                    'iau' => 'អង្គភាពសវនកម្មការផ្ទៃក្នុង', // Internal Audit Unit
                    'general' => 'នាយកដ្ឋានកិច្ចការទូទៅ', // General
                    'audit1' => 'នាយកដ្ឋានសវនកម្មទី១', // Audit 1
                    'audit2' => 'នាយកដ្ឋានសវនកម្មទី២', // Audit 2
                    'hr' => 'ការិយាល័យធនធានមនុស្ស', // Human Resources
                    'training' => 'ការិយាល័យបណ្តុះបណ្តាល', // Training
                    'it' => 'ការិយាល័យគ្រប់គ្រងព័ត៌មានវីទ្យា', // Information Technology
                    'ofaudit1' => 'ការិយាល័យសវនកម្មទី១', // Office Audit 1
                    'ofaudit2' => 'ការិយាល័យសវនកម្មទី២', // Office Audit 2
                    'ofaudit3' => 'ការិយាល័យសវនកម្មកាទី៣', // Office Audit 3
                    'ofaudit4' => 'ការិយាល័យសវនកម្មទី៤'  // Office Audit 4
                  ];
                  foreach ($userPermissions as $key => $permissionName) :
                ?>
                    <tr>
                      <td class="text-nowrap fw-medium">
                        <?= htmlentities($permissionName); ?>
                      </td>
                      <td class="d-flex flex-row justify-content-center">
                        <label class="switch switch-primary">
                          <input type="checkbox" name="pid[]" value="<?= htmlentities((string)$key); ?>" <?= $user[$key] ? 'checked' : ''; ?> class="switch-input permission-toggle">
                          <span class="switch-toggle-slider">
                            <span class="switch-on">
                              <i class="bx bx-check"></i>
                            </span>
                            <span class="switch-off">
                              <i class="bx bx-x"></i>
                            </span>
                          </span>
                        </label>
                      </td>
                    </tr>
                  <?php
                  endforeach;
                else :
                  ?>
                  <tr>
                    <td colspan="2">User not found.</td>
                  </tr>
                <?php
                endif;
                ?>
              </tbody>
            </table>
          </div>
          <div>
            <button type="button" class="btn btn-primary me-2 mx-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
              រក្សាទុកការផ្លាស់ប្តូរ
            </button>
          </div>
          <!-- Modal -->
          <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h1 class="modal-title fs-5 mef2" id="exampleModalLabel">
                    ផ្លាស់ប្តូរការអនុញ្ញាតិ
                  </h1>
                </div>
                <div class="modal-body">
                  តើអ្នកប្រាកដទេថានិងផ្លាស់ប្តូរការកំណត់នេះ?
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">មិនទាន់</button>
                  <button type="submit" class="btn btn-danger">យល់ព្រម</button>
                </div>
              </div>
            </div>
          </div>
          <!--/ Layout container -->
        </form>
      </div>
      <!-- /Project table -->
    </div>
    <!-- end-user-security -->
  </div>

  <script>
    // Display selected profile image
    document.getElementById('profileInput').addEventListener('change', function() {
      const profileImage = this.previousElementSibling.querySelector('img');
      const file = this.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        profileImage.src = e.target.result;
        updateProfileImage(file); // Call function to update profile image
      };
      reader.readAsDataURL(file);
    });
  </script>
<?php
} else {

  // User data not found
  echo "<div class='text-center'>User not found.</div>";
}
$content = ob_get_clean(); ?>
<!-- <script src="../../assets/js/form-validat
ion.js"></script> -->
<?php include('../../includes/layout.php'); ?>