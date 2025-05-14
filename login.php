<?php
require_once 'Models/Login.php';

$view = new stdClass();
$view->pageTitle = 'Login';
$view->errorMessage = '';
$view->email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $view->email = htmlspecialchars($email);

    if ($email && $password) {
        $authResult = Login::authenticate($email, $password);

        if (is_array($authResult)) {
            session_start();
            $_SESSION['user_id'] = $authResult['id'];
            $_SESSION['firstname'] = $authResult['first_name'];
            $_SESSION['lastname'] = $authResult['last_name'];
            $_SESSION['username'] = $authResult['username'];

            switch ($authResult['role_id']) {
                case 1:
                    $_SESSION['role'] = 'Admin';
                    header('Location: dashboard_admin.php');
                    exit;
                case 2:
                    $_SESSION['role'] = 'HomeOwner';
                    header('Location: home_homeowner.php');
                    exit;
                case 3:
                    $_SESSION['role'] = 'RentalUser';
                    header('Location: index.php');
                    exit;
                default:
                    $_SESSION['role'] = 'Guest';
                    header('Location: index.php');
                    exit;
            }
        } else {
            switch ($authResult) {
                case 'email_not_found':
                    $view->errorMessage = 'Email does not exist.';
                    break;
                case 'incorrect_password':
                    $view->errorMessage = 'Incorrect password.';
                    break;
                case 'account_pending':
                    $view->errorMessage = 'Your account is pending approval. An administrator must approve your account before you can log in.';
                    break;
                case 'account_suspended':
                    $view->errorMessage = 'Your account has been suspended.';
                    break;
                default:
                    $view->errorMessage = 'Login failed.';
            }
        }
    } else {
        $view->errorMessage = 'Please fill in both fields.';
    }
}

require_once 'Views/login.phtml';
