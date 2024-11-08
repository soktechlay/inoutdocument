<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
} // Start session at the beginning of the script
include('../config/dbconn.php'); // Include database connection
include('../models/UserModel.php'); // Include UserModel
include('../models/SystemSettingsModel.php'); // Include SystemSettingsModel

class AuthController
{
  private $userModel;
  private $systemSettingsModel;

  public function __construct($dbh)
  {
    $this->userModel = new UserModel($dbh); // Initialize UserModel
    $this->systemSettingsModel = new SystemSettingsModel($dbh); // Initialize SystemSettingsModel
  }

  public function login()
{
   

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $loginType = $_POST['login_type'];
        if ($loginType == 'login') {
            if (!empty($_POST["username"]) && !empty($_POST["password"])) {
                $username = $_POST["username"];
                $password = $_POST["password"];

                // Fetch user or admin data based on username
                $user = $this->userModel->getUserByUsername($username);
                $admin = $this->userModel->getAdminByUsername($username);
                $account = $user ?: $admin;
                $accountType = $user ? 'user' : ($admin ? 'supperadmin' : null);

                if ($account) {
                    // Check account status and password
                    if ($account['Status'] == 'locked') {
                        sleep(1);
                        header('Location: ../views/auth/account-locked.php');
                        exit;
                    } elseif (password_verify($password, $account['Password'])) {
                        // Check if 2FA is enabled
                        if (isset($account['authenticator_enabled']) && $account['authenticator_enabled'] == 1) {
                            $_SESSION['temp_userid'] = $account['id'];
                            $_SESSION['temp_username'] = $account['UserName'];
                            $_SESSION['temp_role'] = $account['RoleName'];
                            $_SESSION['temp_usertype'] = $accountType;
                            $_SESSION['temp_secret'] = $account['TwoFASecret'];
                            sleep(1);
                            header('Location: ../views/auth/2fa.php');
                            exit;
                        } else {
                            // Set session variables for logged-in user
                            $_SESSION['userid'] = $account['id'];
                            $_SESSION['role'] = $account['RoleName'];
                            $_SESSION['permission'] = $account['PermissionId'];
                            $_SESSION['username'] = $this->getFullName($account);
                           

                            // Redirect based on role
                            $roleToDashboard = [
                                'ប្រធានអង្គភាព' => '../views/admin/dashboard.php',
                                'អនុប្រធានអង្គភាព' => '../views/admin/dashboard.php',
                                'ប្រធាននាយកដ្ឋាន' => '../views/manager/dashboard.php',
                                'អនុប្រធាននាយកដ្ឋាន' => '../views/manager/dashboard.php',
                                'ប្រធានការិយាល័យ' => '../views/office_manager/dashboard.php',
                                'អនុប្រធានការិយាល័យ' => '../views/office_manager/dashboard.php',
                                'supperadmin' => '../views/supperadmin/dashboard.php'
                            ];

                            $role = $_SESSION['role'];
                            $redirectUrl = $roleToDashboard[$role] ?? '../views/user/dashboard.php';
                            header('Location: ' . $redirectUrl);
                            exit;
                        }
                    } else {
                        sleep(1);
                        $_SESSION['error'] = 'ឈ្មោះមន្ត្រី ឬ ពាក្យសម្ងាត់ មិនត្រឹមត្រូវ';
                        header('Location: ../views/auth/login.php');
                        exit;
                    }
                } else {
                    sleep(1);
                    $_SESSION['error'] = 'ឈ្មោះមន្ត្រី ឬ ពាក្យសម្ងាត់ មិនត្រឹមត្រូវ';
                    header('Location: ../views/auth/login.php');
                    exit;
                }
            } else {
                sleep(1);
                $_SESSION['error'] = 'សូមបញ្ចូលឈ្មោះមន្ត្រី និង ពាក្យសម្ងាត់';
                header('Location: ../views/auth/login.php');
                exit;
            }
        } else {
            sleep(1);
            $_SESSION['error'] = "ប្រភេទការចូលមិនត្រឹមត្រូវ";
            header('Location: ../views/auth/login.php');
            exit;
        }
    } else {
        header('Location: ../views/auth/login.php');
        exit;
    }
}


  private function getFullName($account)
  {
    $honorific = $account['Honorific'] ?? '';
    $firstName = $account['FirstName'] ?? '';
    $lastName = $account['LastName'] ?? '';
    return trim("$honorific $firstName $lastName");
  }
}

// Initialize database connection and controller
$authController = new AuthController($dbh);
$authController->login();
