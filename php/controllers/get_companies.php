<?php
// get_companies.php

require_once '../config/db_config.php';

// Check database connection
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Fetch all companies
$query = "SELECT user_id, name FROM users WHERE user_type = 'Company'";
$result = $conn->query($query);

if ($result) {
    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }

    echo json_encode(['companies' => $companies]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
}

$conn->close();
?>
