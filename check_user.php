<?php
require_once 'Models/Register.php';

header('Content-Type: application/json');

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');

$register = new Register();

// Initialize response array
$response = [
    'usernameExists' => false,
    'emailExists' => false
];

// Check username
if (!empty($username)) {
    $usernameExists = $register->checkSpecificField('username', $username);
    $response['usernameExists'] = $usernameExists;
}

// Check email
if (!empty($email)) {
    $emailExists = $register->checkSpecificField('email', $email);
    $response['emailExists'] = $emailExists;
}

echo json_encode($response);
exit;