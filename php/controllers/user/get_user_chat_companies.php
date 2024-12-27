<?php
// get_user_chat_companies.php

session_start();

// Check if the user is logged in and presumably a Passenger (or any user allowed to chat)
if (!isset($_SESSION['user_id']) /* || $_SESSION['user_type'] !== 'Passenger' */) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized. Please log in as a user.']);
    exit();
}

// Include DB config
require_once '../../config/db_config.php';

// The current user (Passenger)
$user_id = $_SESSION['user_id'];

// We'll fetch all distinct user_ids from messages that are 'Company'
$sql = "
    SELECT DISTINCT c.user_id, c.name, c.email
    FROM messages m
    JOIN users c ON (
       (m.sender_id = c.user_id AND m.receiver_id = ?)
       OR
       (m.receiver_id = c.user_id AND m.sender_id = ?)
    )
    WHERE c.user_type = 'Company'
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $companies = [];
    while ($row = $result->fetch_assoc()) {
        // e.g. row = [ 'user_id'=>12, 'name'=>'Some Company', 'email'=>'comp@gmail.com' ]
        $companies[] = $row;
    }

    echo json_encode(['companies' => $companies]);
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $conn->error]);
}

$conn->close();
