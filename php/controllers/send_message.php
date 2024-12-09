<?php
// send_message.php

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../html/login.html");
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $sender_id = $_SESSION['user_id'];
    $receiver_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
    $message_content = isset($_POST['message_content']) ? sanitize_input($_POST['message_content']) : '';

    // Basic validation
    if ($receiver_id <= 0 || empty($message_content)) {
        // Redirect back with an error
        $_SESSION['message_error'] = "Invalid message or company selection.";
        header("Location: dashboard.php");
        exit();
    }

    // Verify that the receiver is a company
    $company_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND user_type = 'Company'");
    if ($company_stmt) {
        $company_stmt->bind_param("i", $receiver_id);
        $company_stmt->execute();
        $company_stmt->store_result();
        if ($company_stmt->num_rows === 0) {
            $_SESSION['message_error'] = "Selected receiver is not a valid company.";
            $company_stmt->close();
            header("Location: dashboard.php");
            exit();
        }
        $company_stmt->close();
    } else {
        $_SESSION['message_error'] = "Database error: " . $conn->error;
        header("Location: dashboard.php");
        exit();
    }

    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_content) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iis", $sender_id, $receiver_id, $message_content);
        if ($stmt->execute()) {
            // Success
            $_SESSION['message_success'] = "Message sent successfully!";
        } else {
            // Execution failed
            $_SESSION['message_error'] = "Failed to send message: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Preparation failed
        $_SESSION['message_error'] = "Database error: " . $conn->error;
    }

    // Close the database connection
    $conn->close();

    // Redirect back to the dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Invalid request method
    header("Location: dashboard.php");
    exit();
}
?>
