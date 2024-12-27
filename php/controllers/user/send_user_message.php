<?php
// send_user_message.php

session_start();

// Check if logged in (and optionally user_type='Passenger')
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit();
}

require_once '../../config/db_config.php';

// Helper to sanitize
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id         = $_SESSION['user_id']; // current user (passenger)
    $company_id      = isset($_POST['company'])       ? intval($_POST['company'])       : 0;
    // or if your form uses 'company_id' => adapt accordingly
    $message_content = isset($_POST['msg'])           ? sanitize_input($_POST['msg'])   : '';
    // if your form uses 'message_content' => adapt accordingly

    if ($company_id <= 0 || empty($message_content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data provided.']);
        exit();
    }

    // Insert a new row in messages: sender=user, receiver=company
    $sql = "INSERT INTO messages (sender_id, receiver_id, message_content, status) 
            VALUES (?, ?, ?, 'Sent')";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iis", $user_id, $company_id, $message_content);
        if ($stmt->execute()) {
            echo json_encode(['success' => 'Message sent successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Insert failed: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'DB error: ' . $conn->error]);
    }

    $conn->close();
    exit();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
}
