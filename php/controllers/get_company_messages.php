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

// Prepare SQL statement to fetch messages sent by passengers to the company
$query = "SELECT messages.message_id, passengers.name AS passenger_name, messages.message_content FROM messages JOIN passengers ON messages.sender_id = passengers.passenger_id WHERE messages.receiver_id = ? AND messages.status = 'Unread' ORDER BY messages.timestamp DESC";

if ($stmt = $conn->prepare($query)) {
    $company_id = $_SESSION['user_id'];
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
    echo json_encode(['error' => 'Database error: Unable to prepare statement.']);
}

$conn->close();
?>
