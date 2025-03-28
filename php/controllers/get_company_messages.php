<?php
// get_company_messages.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// The currently logged-in company's ID
$company_id = $_SESSION['user_id'];

/*
    Fetch all messages where receiver_id = this company.
    That means these were sent by some Passenger to the Company.
    We join with the users table to get the passenger’s name.
*/
$sql = "
    SELECT
        m.message_id,
        m.sender_id AS passenger_id,
        u.name AS passenger_name,
        m.message_content,
        m.timestamp,
        m.status
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.receiver_id = ?
    ORDER BY m.timestamp DESC
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    echo json_encode(['messages' => $messages]);
    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}

$conn->close();
