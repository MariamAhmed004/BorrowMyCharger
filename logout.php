<?php
session_start(); // Start the session, so we can access session variables

// Destroy session data
session_unset();  // Unset all session variables
session_destroy(); // Destroy the session completely

// Optionally, set the 'role' to 'Guest' if needed for future requests
$_SESSION['role'] = 'Guest'; // Set role as Guest

// Redirect to the login page
header('Location: index.php'); // After logging out, redirect the user to the login page
exit; // Ensure no further code is executed
?>
