<?php

require_once 'Models/Register.php';

$response = ['usernameExists' => false, 'emailExists' => false];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $register = new Register();

    // Check if the username exists
    if ($register->checkUserExists($username, null)) {
        $response['usernameExists'] = true;
    }

    // Check if the email exists
    if ($register->checkUserExists(null, $email)) {
        $response['emailExists'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);