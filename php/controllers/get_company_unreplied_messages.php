<?php
// get_company_unreplied_messages.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    exit();
}

require_once '../config/db_config.php';

$company_id = $_SESSION['user_id'];

/*
    Fetch all messages where:
        receiver_id = this company
        status      = 'Sent'   (i.e., not yet replied)
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
      AND m.status = 'Sent'
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
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
