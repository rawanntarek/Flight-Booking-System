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
require_once '../../config/db_config.php';

// Prepare SQL statement to fetch company details
$query = "SELECT users.name, users.email, users.account_balance, companies.bio, companies.address FROM users JOIN companies ON users.user_id = companies.user_id WHERE users.user_id = ?";
if ($stmt = $conn->prepare($query)) {
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $email, $account_balance,$bio,$address);
    $stmt->fetch();

    $response = [
        'company' => [
            'name' => $name,
            'email' => $email,
            'account_balance' => $account_balance,
            'bio' => $bio, 
            'address' => $address
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
