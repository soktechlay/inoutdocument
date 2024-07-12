<?php
include('../../config/dbconn.php');

// Fetch user permissions from tbluser
$userId = $_SESSION['userid']; // Assuming you have the user's ID stored in the session
$query = "SELECT iau, general, audit1, audit2 FROM tbluser WHERE id = :userId";
$stmt = $dbh->prepare($query);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->execute();
$userPermissions = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user permissions are fetched properly
if (!$userPermissions) {
  die("Error fetching user permissions.");
}

// Active page determination (assuming $activePage is set somewhere in your script)
$activePage = ''; // Replace with your logic to determine active page

?>

<aside id="layout-menu" class="layout-menu-horizontal menu menu-horizontal container-fluid flex-grow-0 bg-menu-theme" data-bg-class="bg-menu-theme" style="touch-action: none; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
  <div class="container-xxl d-flex h-100">
    <ul class="menu-inner">
      <!-- Dashboard Menu Item -->
      <li class="menu-item <?php echo ($activePage === 'dashboard.php') ? 'active' : ''; ?>">
        <a href="dashboard.php" class="menu-link">
          <i class="menu-icon tf-icons bx bxs-dashboard"></i>
          <div data-i18n="Dashboard"><?php echo translate('Dashboard'); ?></div>
        </a>
      </li>

      <!-- Dynamic Menu Items based on User Permissions -->
      <?php if ($userPermissions['iau'] == 1) : ?>
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i>
            <div data-i18n="Tables">គ្រប់គ្រងបញ្ចីឯកសារចេញចូលអង្គភាព</div>
          </a>
          <ul class="menu-sub">
            <!-- Submenu Items for iau -->
            <li class="menu-item <?php echo ($activePage === 'iniau.php') ? 'active' : ''; ?>">
              <a href="iniau.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div data-i18n="Login Documents">បញ្ចីឯកសារចូល</div>
              </a>
            </li>
            <li class="menu-item <?php echo ($activePage === 'outiau.php') ? 'active' : ''; ?>">
              <a href="outiau.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div data-i18n="Logout Documents">បញ្ចីឯកសារចេញ</div>
              </a>
            </li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($userPermissions['general'] == 1) : ?>
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i>
            <div data-i18n="Tables">គ្រប់គ្រងបញ្ចីឯកសារចេញចូលនាយកដ្ឋានកិច្ចការទូទៅ</div>
          </a>
          <ul class="menu-sub">
            <!-- Submenu Items for general -->
            <li class="menu-item <?php echo ($activePage === 'ingeneral.php') ? 'active' : ''; ?>">
              <a href="ingeneral.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div data-i18n="Login Documents">បញ្ចីឯកសារចូល</div>
              </a>
            </li>
            <li class="menu-item <?php echo ($activePage === 'outgeneral.php') ? 'active' : ''; ?>">
              <a href="outgeneral.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div data-i18n="Logout Documents">បញ្ចីឯកសារចេញ</div>
              </a>
            </li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($userPermissions['audit1'] == 1) : ?>
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i>
            <div data-i18n="Tables">គ្រប់គ្រងបញ្ចីឯកសារចេញចូលនាយកដ្ឋានសវនកម្មទី១</div>
          </a>
          <ul class="menu-sub">
            <!-- Submenu Items for audit1 -->
            <li class="menu-item <?php echo ($activePage === 'inaudit1.php') ? 'active' : ''; ?>">
              <a href="inaudit1.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div data-i18n="Login Documents">បញ្ចីឯកសារចូល</div>
              </a>
            </li>
            <li class="menu-item <?php echo ($activePage === 'outaudit1.php') ? 'active' : ''; ?>">
              <a href="outaudit1.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div data-i18n="Logout Documents">បញ្ចីឯកសារចេញ</div>
              </a>
            </li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($userPermissions['audit2'] == 1) : ?>
        <li class="menu-item">
          <a href="javascript:void(0)" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons bx bx-grid-alt"></i>
            <div data-i18n="Tables">គ្រប់គ្រងបញ្ចីឯកសារចេញចូលនាយកដ្ឋានសវនកម្មទី២</div>
          </a>
          <ul class="menu-sub">
            <!-- Submenu Items for audit2 -->
            <li class="menu-item <?php echo ($activePage === 'inaudit2.php') ? 'active' : ''; ?>">
              <a href="inaudit2.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div data-i18n="Login Documents">បញ្ចីឯកសារចូល</div>
              </a>
            </li>
            <li class="menu-item <?php echo ($activePage === 'outaudit2.php') ? 'active' : ''; ?>">
              <a href="outaudit2.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-table"></i>
                <div data-i18n="Logout Documents">បញ្ចីឯកសារចេញ</div>
              </a>
            </li>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</aside>