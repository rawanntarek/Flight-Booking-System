<?php
// get_companies.php

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in.']);
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Prepare the SQL statement to fetch companies
$stmt = $conn->prepare("SELECT user_id, name FROM users WHERE user_type = 'Company'");
if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database preparation failed: ' . $conn->error]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch all companies
$companies = [];
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}

$stmt->close();
$conn->close();

// Return the list of companies as JSON
header('Content-Type: application/json');
echo json_encode(['companies' => $companies]);
?>
