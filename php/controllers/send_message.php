<?php
// send_message.php

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Unauthorized access. Please log in.']);
    } else {
        header("Location: ../../html/login.html");
    }
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Determine if the request is AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Initialize response array
$response = [];

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $sender_id = $_SESSION['user_id'];
    $receiver_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
    $message_content = isset($_POST['message_content']) ? sanitize_input($_POST['message_content']) : '';

    // Basic validation
    if ($receiver_id <= 0 || empty($message_content)) {
        $error = "Invalid message or company selection.";
        if ($isAjax) {
            http_response_code(400); // Bad Request
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['message_error'] = $error;
            header("Location: ../../html/dashboard.html");
        }
        exit();
    }

    // Verify that the receiver is a company
    $company_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND user_type = 'Company'");
    if ($company_stmt) {
        $company_stmt->bind_param("i", $receiver_id);
        $company_stmt->execute();
        $company_stmt->store_result();
        if ($company_stmt->num_rows === 0) {
            $error = "Selected receiver is not a valid company.";
            if ($isAjax) {
                http_response_code(400); // Bad Request
                $response['error'] = $error;
                echo json_encode($response);
            } else {
                $_SESSION['message_error'] = $error;
                $company_stmt->close();
                header("Location: ../../html/dashboard.html");
            }
            exit();
        }
        $company_stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
        if ($isAjax) {
            http_response_code(500); // Internal Server Error
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['message_error'] = $error;
            header("Location: dashboard.html");
        }
        exit();
    }

    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_content) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iis", $sender_id, $receiver_id, $message_content);
        if ($stmt->execute()) {
            // Success
            $success = "Message sent successfully!";
            if ($isAjax) {
                $response['success'] = $success;
                echo json_encode($response);
            } else {
                $_SESSION['message_success'] = $success;
            }
        } else {
            // Execution failed
            $error = "Failed to send message: " . $stmt->error;
            if ($isAjax) {
                http_response_code(500); // Internal Server Error
                $response['error'] = $error;
                echo json_encode($response);
            } else {
                $_SESSION['message_error'] = $error;
            }
        }
        $stmt->close();
    } else {
        // Preparation failed
        $error = "Database error: " . $conn->error;
        if ($isAjax) {
            http_response_code(500); // Internal Server Error
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['message_error'] = $error;
        }
    }

    // Close the database connection
    $conn->close();

    if (!$isAjax) {
        // Redirect back to the dashboard
        header("Location: ../../html/dashboard.html");
    }

    exit();
} else {
    // Invalid request method
    $error = "Invalid request method.";
    if ($isAjax) {
        http_response_code(405); // Method Not Allowed
        $response['error'] = $error;
        echo json_encode($response);
    } else {
        $_SESSION['message_error'] = $error;
        header("Location: ../../html/dashboard.html");
    }
    exit();
}
?>
