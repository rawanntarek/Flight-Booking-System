<?php
// get_company_details.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Prepare SQL statement to fetch company details
$query = "SELECT name, email, account_balance FROM users WHERE user_id = ?";

if ($stmt = $conn->prepare($query)) {
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $email, $account_balance);
    $stmt->fetch();

    $response = [
        'company' => [
            'name' => $name,
            'email' => $email,
            'account_balance' => $account_balance
        ]
    ];

    echo json_encode($response);
    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: Unable to prepare statement.']);
}

$conn->close();
?>
