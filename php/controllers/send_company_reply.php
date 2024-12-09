<?php
// send_company_reply.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
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
    $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
    $passenger_id = isset($_POST['passenger_id']) ? intval($_POST['passenger_id']) : 0;
    $reply_content = isset($_POST['reply_content']) ? sanitize_input($_POST['reply_content']) : '';

    // Basic validation
    if ($message_id <= 0 || $passenger_id <= 0 || empty($reply_content)) {
        $error = "Invalid data provided.";
        if ($isAjax) {
            http_response_code(400); // Bad Request
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['reply_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Verify that the message belongs to the company and is unread
        $verify_query = "SELECT message_id FROM messages WHERE message_id = ? AND receiver_id = ? AND status = 'Unread'";
        if ($verify_stmt = $conn->prepare($verify_query)) {
            $company_id = $_SESSION['user_id'];
            $verify_stmt->bind_param("ii", $message_id, $company_id);
            $verify_stmt->execute();
            $verify_stmt->store_result();
            if ($verify_stmt->num_rows !== 1) {
                throw new Exception("Message not found or already replied.");
            }
            $verify_stmt->close();
        } else {
            throw new Exception("Database error: Unable to prepare statement.");
        }

        // Insert the reply into the replies table (assuming such a table exists)
        // If not, you might want to include reply content in the messages table or another appropriate table
        $insert_reply_query = "INSERT INTO replies (message_id, responder_id, reply_content, timestamp) VALUES (?, ?, ?, NOW())";
        if ($insert_reply_stmt = $conn->prepare($insert_reply_query)) {
            $insert_reply_stmt->bind_param("iis", $message_id, $company_id, $reply_content);
            if (!$insert_reply_stmt->execute()) {
                throw new Exception("Failed to insert reply: " . $insert_reply_stmt->error);
            }
            $insert_reply_stmt->close();
        } else {
            throw new Exception("Database error: Unable to prepare reply insertion statement.");
        }

        // Update the message status to 'Replied'
        $update_message_query = "UPDATE messages SET status = 'Replied' WHERE message_id = ?";
        if ($update_message_stmt = $conn->prepare($update_message_query)) {
            $update_message_stmt->bind_param("i", $message_id);
            if (!$update_message_stmt->execute()) {
                throw new Exception("Failed to update message status.");
            }
            $update_message_stmt->close();
        } else {
            throw new Exception("Database error: Unable to prepare message status update statement.");
        }

        // Commit transaction
        $conn->commit();

        // Optionally, send an email notification to the passenger (requires mail server configuration)
        /*
        // Fetch passenger email
        $passenger_email_query = "SELECT email, name FROM passengers WHERE passenger_id = ?";
        if ($passenger_email_stmt = $conn->prepare($passenger_email_query)) {
            $passenger_email_stmt->bind_param("i", $passenger_id);
            $passenger_email_stmt->execute();
            $passenger_email_stmt->bind_result($passenger_email, $passenger_name);
            $passenger_email_stmt->fetch();
            $passenger_email_stmt->close();

            // Send email
            $to = $passenger_email;
            $subject = "Reply to Your Message";
            $message = "Dear " . $passenger_name . ",\n\n" . $reply_content . "\n\nBest regards,\nTrain Booking System";
            $headers = "From: no-reply@trainbookingsystem.com";

            mail($to, $subject, $message, $headers);
        }
        */

        $response['success'] = "Reply sent successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        if ($isAjax) {
            http_response_code(500); // Internal Server Error
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['reply_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
        exit();
    }

    // Close the database connection
    $conn->close();

    // Send success response
    if ($isAjax) {
        echo json_encode($response);
    } else {
        $_SESSION['reply_success'] = $response['success'];
        header("Location: ../../html/company_home.html");
    }

    exit();
} else {
    // Invalid request method
    $error = "Invalid request method.";
    if ($isAjax) {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => $error]);
    } else {
        $_SESSION['reply_error'] = $error;
        header("Location: ../../html/company_home.html");
    }
    exit();
}
?>
