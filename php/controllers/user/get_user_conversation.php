<?php
// get_user_conversation.php

session_start();

// Must be logged in (optionally check for user_type='Passenger')
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit();
}

require_once '../../config/db_config.php';

// The current user's ID
$user_id    = $_SESSION['user_id'];
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;

if ($company_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid company ID.']);
    exit();
}

// Query to get all messages in both directions
$sql = "
    SELECT 
       m.message_id,
       m.sender_id,
       m.receiver_id,
       m.message_content,
       m.timestamp,
       m.status
    FROM messages m
    WHERE 
       (m.sender_id = ? AND m.receiver_id = ?)
       OR
       (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.timestamp ASC
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iiii", $user_id, $company_id, $company_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Send messages + the current_user_id for front-end alignment
    echo json_encode([
        'messages' => $messages,
        'current_user_id' => $user_id
    ]);
    
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $conn->error]);
}

$conn->close();
