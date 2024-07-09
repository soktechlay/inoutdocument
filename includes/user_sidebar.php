<?php
include('../../config/dbconn.php');

// Include translation function
include('../../includes/translate.php');

// Fetch user permissions from tbluser
$userId = $_SESSION['userid']; // Assuming you have the user's ID stored in the session
// $query = "SELECT PermissionId FROM tbluser WHERE id = :userId";
// $stmt = $dbh->prepare($query);
// $stmt->bindParam(':userId', $userId);
// $stmt->execute();
// $userPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// // Prepare a comma-separated string of permission IDs for the SQL query
// $permissionIds = implode(',', $userPermissions);

// // Query to fetch sidebar menu details based on user permissions
// $query = "SELECT p.PermissionName, p.NavigationUrl, p.IconClass, p.EngName
//           FROM tblpermission p
//           WHERE p.id IN ($permissionIds)";

// // Execute the query and fetch sidebar menu details
// $stmt = $dbh->query($query);
// $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php
$currentUrl = basename($_SERVER['PHP_SELF']); // Get the current page filename

$sidebarRelationships = array(
  'dashboard' => array('dashboard.php', 'index.php'), // Add all pages related to the dashboard sidebar
  'audits' => array('audits.php', 'create_report_page.php'), // Corrected 'create_report_page.php' instead of 'create_reports_page.php'
  // Add more relationships as needed
);
// Determine the active page based on the sidebar variable
$activePage = '';
if (isset($sidebar) && array_key_exists($sidebar, $sidebarRelationships)) {
  foreach ($sidebarRelationships[$sidebar] as $relatedPage) {
    if ($currentUrl === $relatedPage) {
      $activePage = $relatedPage;
      break;
    }
  }
}
// If no related pages match the current URL, use the current URL itself
if (empty($activePage)) {
  $activePage = $currentUrl;
}
?>
<aside id="layout-menu" class="layout-menu-horizontal menu menu-horizontal container-fluid flex-grow-0 bg-menu-theme" data-bg-class="bg-menu-theme" style="touch-action: none; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
  <div class="container-xxl d-flex h-100">
    <ul class="menu-inner">
      <!-- Add the dashboard menu item directly -->
      <li class="menu-item <?php echo ($activePage === 'dashboard.php') ? 'active' : ''; ?>">
        <a href="dashboard.php" class="menu-link">
          <i class="menu-icon tf-icons bx bxs-dashboard"></i>
          <div data-i18n="Dashboard"><?php echo translate('Dashboard'); ?></div>
        </a>
      </li>
      <!-- <?php foreach ($menuItems as $item) : ?> -->
        <!-- <li class="menu-item <?php echo ($item['NavigationUrl'] === $activePage) ? 'active' : ''; ?>">
          <a href="<?php echo $item['NavigationUrl']; ?>" class="menu-link">
            <i class="menu-icon tf-icons bx <?php echo $item['IconClass']; ?>"></i>
            <div data-i18n="<?php echo $item['EngName']; ?>"><?php echo translate($item['PermissionName']); ?></div>
          </a>
        </li> -->

      <!-- <?php endforeach;
      ?> -->

      <?php
      $userId = $_SESSION['userid'];
      $query = "SELECT iau, general,audit1,audit2 FROM tbluser WHERE id = :userId";
      $stmt = $dbh->prepare($query);
      $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_OBJ);

      // Check if data is fetched properly
      if (!$result) {
        die("Error fetching user permissions.");
      }

      ?>
      <!-- Tables Menu Item with Submenu -->
      <?php if ($result->iau == 1) : ?>
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i>
            <div data-i18n="Tables">ឯកសារចេញចូលអង្គភាពសវនកម្មផ្ទៃក្នុង</div>
          </a>
          <ul class="menu-sub">
            <!-- Tables Submenu Items for iau -->
            <?php $tablesSubMenu = array(
              array('NavigationUrl' => 'iniau.php', 'PermissionName' => 'ឯកសារចូល'),
              array('NavigationUrl' => 'outiau.php', 'PermissionName' => 'ឯកសារចេញ')
            ); ?>
            <?php foreach ($tablesSubMenu as $subItem) : ?>
              <li class="menu-item <?php echo ($subItem['NavigationUrl'] === $activePage) ? 'active' : ''; ?>">
                <a href="<?php echo $subItem['NavigationUrl']; ?>" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-table"></i>
                  <div><?php echo translate($subItem['PermissionName']); ?></div>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($result->general == 1) : ?>
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i>
            <div data-i18n="Tables">ឯកសារចេញចូលនាយកដ្ឋានកិច្ចការទូទៅ</div>
          </a>
          <ul class="menu-sub">
            <!-- Tables Submenu Items for general -->
            <?php $tablesSubMenu = array(
              array('NavigationUrl' => 'ingeneral.php', 'PermissionName' => 'ឯកសារចូល'),
              array('NavigationUrl' => 'outgeneral.php', 'PermissionName' => 'ឯកសារចេញ')
            ); ?>
            <?php foreach ($tablesSubMenu as $subItem) : ?>
              <li class="menu-item <?php echo ($subItem['NavigationUrl'] === $activePage) ? 'active' : ''; ?>">
                <a href="<?php echo $subItem['NavigationUrl']; ?>" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-table"></i>
                  <div><?php echo translate($subItem['PermissionName']); ?></div>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php endif; ?>
      <?php if ($result->audit1 == 1) : ?>
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i>
            <div data-i18n="Tables">ឯកសារចេញចូលនាយកដ្ឋានសវនកម្មទី១</div>
          </a>
          <ul class="menu-sub">
            <!-- Tables Submenu Items for iau -->
            <?php $tablesSubMenu = array(
              array('NavigationUrl' => 'inaudit1.php', 'PermissionName' => 'ឯកសារចូល'),
              array('NavigationUrl' => 'outaudit1.php', 'PermissionName' => 'ឯកសារចេញ')
            ); ?>
            <?php foreach ($tablesSubMenu as $subItem) : ?>
              <li class="menu-item <?php echo ($subItem['NavigationUrl'] === $activePage) ? 'active' : ''; ?>">
                <a href="<?php echo $subItem['NavigationUrl']; ?>" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-table"></i>
                  <div><?php echo translate($subItem['PermissionName']); ?></div>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php endif; ?>      
      <?php if ($result->audit2 == 1) : ?>
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i>
            <div data-i18n="Tables">ឯកសារចេញចូលនាយកដ្ឋានសវនកម្មទី២</div>
          </a>
          <ul class="menu-sub">
            <!-- Tables Submenu Items for iau -->
            <?php $tablesSubMenu = array(
              array('NavigationUrl' => 'inaudit2.php', 'PermissionName' => 'ឯកសារចូល'),
              array('NavigationUrl' => 'outaudit2.php', 'PermissionName' => 'ឯកសារចេញ')
            ); ?>
            <?php foreach ($tablesSubMenu as $subItem) : ?>
              <li class="menu-item <?php echo ($subItem['NavigationUrl'] === $activePage) ? 'active' : ''; ?>">
                <a href="<?php echo $subItem['NavigationUrl']; ?>" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-table"></i>
                  <div><?php echo translate($subItem['PermissionName']); ?></div>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php endif; ?>       



    </ul>
  </div>
</aside>