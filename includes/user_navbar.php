<?php
include('translate.php');

// Redirect to index page if the user is not authenticated
if (!isset($_SESSION['userid'])) {
  header('Location: ../index.php');
  exit();
}
// Include the admin functions file
include 'fuctions.php';

$userId = $_SESSION['userid'];
$notifications = getNotifications($userId);


// Fetch user-specific data from the database
$sqlUser = "SELECT u.*, r.RoleName FROM tbluser u
            INNER JOIN tblrole r ON u.RoleId = r.id
            WHERE u.id = :userId";
$stmtUser = $dbh->prepare($sqlUser);
$stmtUser->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmtUser->execute();
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

$userLanguage = $user['languages']; // Get user's language preference
$default_language = "kh";

// Define language options
$languages = array(
  'kh' => translate('ភាសាខ្មែរ'),
  'en' => translate('English')
);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['language']) && array_key_exists($_POST['language'], $languages)) {
    $selectedLanguage = $_POST['language'];

    // Update the user's language preference in the database
    $updateLanguageSql = "UPDATE tbluser SET languages = :language WHERE id = :userId";
    $stmtUpdateLanguage = $dbh->prepare($updateLanguageSql);
    $stmtUpdateLanguage->bindParam(':language', $selectedLanguage);
    $stmtUpdateLanguage->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtUpdateLanguage->execute();

    // Update the session variable to reflect the updated language immediately
    $_SESSION['user_language'] = $selectedLanguage;

    // Set a success message
    sleep(1);
    $msg = urlencode(translate("Languages have been successfully updated"));
  } else {
    // Set an error message
    sleep(1);
    $error = translate("Invalid language selected");
  }
}

try {
  // Check if session is not already started
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }

  // Get the user ID from the session
  $userId = $_SESSION['userid'];

  // SQL query to count unread notifications for the current user
  $sql = "SELECT COUNT(*) AS unread_count
            FROM notifications
            WHERE is_read = 0
            AND sendid = :userId";  // Changed n.sendid to sendid

  // Prepare the query
  $stmt = $dbh->prepare($sql);
  $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

  // Execute the query
  $stmt->execute();

  // Fetch the result
  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  // Extract the count
  $unreadCount = $result['unread_count'];

  // Output the count
  echo "Unread notifications for user " . $userId . ": " . $unreadCount;
} catch (PDOException $e) {
  // Handle database errors
  die("PDO Error: " . $e->getMessage());
}
?>
<?php include('alert.php'); ?>
<nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="container-xxl">
    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
      <a href="" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">
          <img src="../../assets/img/icons/brands/logo2.png" class="avatar avat" alt="">
        </span>
        <span class="app-brand-text demo menu-text fw-bold d-xl-block d-none d-sm-none text-uppercase" style="font-family:'khmer mef2','Sans Serif';font-size: 1.2rem"><?php echo translate('INTERNAL AUDIT UNIT'); ?></span>
      </a>
      <a href="" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
        <i class="bx bx-chevron-left bx-sm align-middle"></i>
      </a>
    </div>

    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
      <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
        <i class="bx bx-menu bx-sm"></i>
      </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
      <ul class="navbar-nav flex-row align-items-center ms-auto">
        <!-- Language Selector -->
        <li class="nav-item dropdown-language dropdown me-2 me-xl-0">
          <form id="language-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="dropdown-toggle hide-arrow">
            <input type="hidden" name="language" id="selected-language" value="<?php echo $userLanguage; ?>">
            <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
              <i class="bx bx-globe bx-sm"></i>
            </a>
            <!-- Default Language Option -->
            <ul class="dropdown-menu dropdown-menu-end">
              <?php foreach ($languages as $langCode => $langName) : ?>
                <li>
                  <button type="submit" name="language" value="<?php echo $langCode; ?>" class="dropdown-item language-option <?php echo ($userLanguage == $langCode) ? 'active' : ''; ?>">
                    <span class="align-middle"><?php echo $langName; ?></span>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
          </form>
        </li>
        <!-- /Language Selector -->

        <!-- Style Switcher -->
        <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <i class="bx bx-sm"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
            <li>
              <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                <span class="align-middle"><i class="bx bx-sun me-2"></i><?php echo translate('Light'); ?></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                <span class="align-middle"><i class="bx bx-moon me-2"></i><?php echo translate('Dark'); ?></span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                <span class="align-middle"><i class="bx bx-desktop me-2"></i><?php echo translate('System'); ?></span>
              </a>
            </li>
          </ul>
        </li>
        <!-- Notification Bell -->
        <?php
        // Check if session is not already started
        if (session_status() == PHP_SESSION_NONE) {
          session_start();
        }
        // Get the user ID from the session
        $userId = $_SESSION['userid'];
        $sql = "SELECT n.id, n.sendid, n.document, n.message, n.user_id, n.is_read, u.UserName, u.Profile, u.Honorific, u.FirstName, u.LastName
FROM tbluser u
INNER JOIN notifications n ON n.user_id = u.id 
WHERE n.sendid = :userid 
ORDER BY n.id DESC";

        // Prepare and execute the query
        $stmt = $dbh->prepare($sql);
        if (!$stmt) {
          die('PDO Error (prepare): ' . implode(" ", $dbh->errorInfo())); // Check for errors in preparing the statement
        }

        // Bind the user ID parameter and execute the query
        if (!$stmt->execute([':userid' => $userId])) {
          die('PDO Error (execute): ' . implode(" ", $stmt->errorInfo())); // Check for errors in executing the statement
        }

        // Fetch all notifications
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Now you can use $notifications array to display notifications
        ?>

        <!-- HTML structure to display notifications -->
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-1 ">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <i class="bx bx-bell bx-sm"></i>
            <span id="notification-badge-wrapper">
              <span class="badge bg-danger rounded-pill badge-notifications"><?php echo $unreadCount; ?></span>
            </span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end py-0" data-bs-popper="static">
            <li class="dropdown-menu-header border-bottom">
              <div class="dropdown-header d-flex align-items-center py-3">
                <h5 class="text-body mb-0 me-auto">Notifications</h5>
                <a href="javascript:void(0)" class="dropdown-notifications-all text-body" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Mark all as read" data-bs-original-title="Mark all as read">
                  <i class="bx fs-4 bx-envelope-open"></i>
                </a>
              </div>
            </li>
            <li class="dropdown-notifications-list scrollable-container ps">
              <ul class="list-group list-group-flush" id="notification-list">
                <?php if (empty($notifications)) : ?>
                  <li class="list-group-item list-group-item-action dropdown-notifications-item">
                    <div class="d-flex me-1 mb-0 justify-content-center align-items-center" style="flex-direction: column;">
                      <img src="../../assets/img/illustrations/empty-box.png" alt="No Requests Found" style="max-width: 15%; height: auto;" />
                      <p class="text-muted mt-3">No Notifications</p>
                    </div>
                  </li>

                <?php else : ?>
                  <?php foreach ($notifications as $notification) : ?>
                    <li class="list-group-item list-group-item-action dropdown-notifications-item <?php echo $notification['is_read'] ? 'read-notification' : 'unread-notification'; ?>">
                      <a class="text-dark" href="read_notifications.php?id=<?php echo $notification['id']; ?>">
                        <div class="d-flex me-1 mb-0 align-items-center text-dark">
                          <!-- Display notification details -->
                          <div class="avatar me-3 mb-0">
                            <img src="<?php echo htmlspecialchars($notification['Profile']); ?>" alt="Profile" class="avatar avatar-sm rounded-circle" style="object-fit: cover">
                          </div>
                          <div>                            
                            <small class="text-dark p"><?php echo htmlentities($notification['message']); ?></small>                      
                            <h5 class="text-dark text-uppercase mb-0">
                              <small class="text-dark p">ឯកសារមកពី : <?php echo htmlentities($notification['Honorific']) . " " . htmlentities($notification['FirstName']) . " " . htmlentities($notification['LastName']); ?></small>
                            </h5>
                            <p class="text-dark mb-0">ឯកសារភ្ជាប់ :
                              <a href="read_notifications.php?id=<?php echo $notification['id']; ?>" class="text-decoration-none text-dark">View Document <i class="bx bxs-file"></i></a>
                            </p>
                          </div>
                        </div>
                      </a>
                    </li>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ul>
            </li>
            <li class="dropdown-menu-footer border-top p-2">
              <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modalScrollable">View All Notifications</button>
            </li>
          </ul>
        </li>

        <style>
          .read-notification {
            background-color: #f0f0f0;
          }

          .unread-notification {
            background-color: #fff;
            font-weight: bold;
          }
        </style>




        <!-- Add this part to your HTML to include the audio element -->
        <audio id="notification-sound" src="../../assets/notification/sound/notification.mp3" preload="auto"></audio>


        <!-- User Profile Dropdown -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <div class="d-flex">
              <div class="flex-shrink-0">
                <div class="avatar avatar-online">
                  <img src="<?php echo (!empty($user['Profile'])) ? htmlentities($user['Profile']) : '../../assets/img/avatars/no-image.jpg'; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                </div>
              </div>
              <div class="flex-grow-1 mx-2 d-none d-md-block">
                <span class="fw-medium d-block"><?php echo htmlentities($user['Honorific']) . " " . htmlentities($user['FirstName']) . " " . htmlentities($user['LastName']); ?></span>
                <small class="text-muted"><?php echo htmlentities($user['RoleName']); ?></small>
              </div>
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item" href="pages-profile-user.php?uid=<?php echo $_SESSION['userid']; ?>">
                <div class="d-flex">
                  <div class="flex-shrink-0 me-3">
                    <div class="avatar avatar-online">
                      <img src="<?php echo (!empty($user['Profile'])) ? htmlentities($user['Profile']) : '../../assets/img/avatars/no-image.jpg'; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <span class="fw-medium d-block"><?php echo htmlentities($user['Honorific']) . " " . htmlentities($user['FirstName']) . " " . htmlentities($user['LastName']); ?></span>
                    <small class="text-muted"><?php echo htmlentities($user['RoleName']); ?></small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <div class="dropdown-divider"></div>
            </li>
            <li>
              <a class="dropdown-item" href="pages-profile-user.php?uid=<?php echo $_SESSION['userid']; ?>">
                <i class="bx bx-user me-2"></i>
                <span class="align-middle">គណនី​របស់​ខ្ញុំ</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="all-users-security.php?uid=<?php echo $_SESSION['userid']; ?>">
                <i class="bx bx-cog me-2"></i>
                <span class="align-middle">ការកំណត់</span>
              </a>
            </li>
            <li>
              <div class="dropdown-divider"></div>
            </li>
            <li>
              <a class="dropdown-item" href="../../includes/logout.php">
                <i class="bx bx-power-off me-2"></i>
                <span class="align-middle">ចាកចេញ</span>
              </a>
            </li>
          </ul>
        </li>
        <!-- /User Profile Dropdown -->
      </ul>
    </div>
  </div>
</nav>
<!-- Modal -->
<div class="modal animate__animated animate__bounceIn" id="modalScrollable" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalScrollableTitle">View All Notifications</h5>
      </div>
      <div class="modal-body">
        <ul class="list-group list-group-flush" id="notification-list">
          <?php if (empty($notifications)) : ?>
            <li class="list-group-item list-group-item-action dropdown-notifications-item">
              <div class="d-flex me-1 mb-0 align-items-center">
                <img src="../../assets/img/illustrations/empty-box.png" alt="No Requests Found" style="max-width: 15%; height: auto;" />
                <p class="text-muted mt-3">No Notifications</p>
              </div>
            </li>
          <?php else : ?>
            <?php foreach ($notifications as $notification) : ?>
              <li class="list-group-item list-group-item-action dropdown-notifications-item <?php echo $notification['is_read'] ? 'read-notification' : 'unread-notification'; ?>">
                <a href="read_notifications.php?id=<?php echo $notification['id']; ?>">
                  <div class="d-flex me-1 mb-0 align-items-center">
                    <!-- Display notification details -->
                    <div class="avatar me-3 mb-0">
                      <img src="<?php echo htmlspecialchars($notification['Profile']); ?>" alt="Profile" class="avatar avatar-sm rounded-circle" style="object-fit: cover">
                    </div>
                    <div>
                      <h5 class="text-black text-uppercase mb-0">
                        <small class="text-primary p">ឯកសារមកពី : <?php echo htmlentities($notification['Honorific']) . " " . htmlentities($notification['FirstName']) . " " . htmlentities($notification['LastName']); ?></small>
                      </h5>
                      <p class="text-black mb-0">ឯកសារភ្ជាប់ :
                        <a href="read_notifications.php?id=<?php echo $notification['id']; ?>" class="text-decoration-none">View Document <i class="bx bxs-file"></i></a>
                      </p>
                    </div>
                  </div>
                </a>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>