<?php
// get_login_error.php

session_start();

// Check if there's a login error
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    // Clear the error after fetching
    unset($_SESSION['login_error']);
    echo json_encode(['error' => $error]);
} else {
    echo json_encode([]);
}
?>
