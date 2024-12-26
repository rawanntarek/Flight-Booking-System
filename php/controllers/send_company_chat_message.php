<?php
// send_company_chat_message.php

session_start();

// Must be a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

require_once '../config/db_config.php';

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id      = $_SESSION['user_id'];
    $passenger_id    = isset($_POST['passenger_id']) ? (int) $_POST['passenger_id'] : 0;
    $message_content = isset($_POST['message_content']) ? sanitize_input($_POST['message_content']) : '';

    if ($passenger_id <= 0 || empty($message_content)) {
        $error = "Invalid data provided.";
        http_response_code(400);
        echo json_encode(['error' => $error]);
        exit();
    }

    // Ensure passenger_id is actually a passenger
    $check_sql = "SELECT user_id FROM users WHERE user_id = ? AND user_type = 'Passenger'";
    if ($check_stmt = $conn->prepare($check_sql)) {
        $check_stmt->bind_param("i", $passenger_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid passenger.']);
            $check_stmt->close();
            exit();
        }
        $check_stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
        exit();
    }

    // Insert the new message
    $insert_sql = "INSERT INTO messages (sender_id, receiver_id, message_content, status) VALUES (?, ?, ?, 'Sent')";
    if ($insert_stmt = $conn->prepare($insert_sql)) {
        $insert_stmt->bind_param("iis", $company_id, $passenger_id, $message_content);
        if ($insert_stmt->execute()) {
            echo json_encode(['success' => 'Message sent.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send message: ' . $insert_stmt->error]);
        }
        $insert_stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }

    $conn->close();
    exit();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
    exit();
}
?>
