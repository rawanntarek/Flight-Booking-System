<?php
// send_company_reply.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    exit();
}

require_once '../config/db_config.php';

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id    = isset($_POST['message_id'])    ? intval($_POST['message_id'])    : 0;
    $passenger_id  = isset($_POST['passenger_id'])  ? intval($_POST['passenger_id'])  : 0;
    $reply_content = isset($_POST['reply_content']) ? sanitize_input($_POST['reply_content']) : '';
    
    if ($message_id <= 0 || $passenger_id <= 0 || empty($reply_content)) {
        $error = "Invalid data provided.";
        if ($isAjax) {
            http_response_code(400);
            echo json_encode(['error' => $error]);
        } else {
            $_SESSION['reply_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
        exit();
    }

    $company_id = $_SESSION['user_id'];

    // Check original message belongs to this company and is status='Sent'
    $verify_sql = "
        SELECT message_id 
        FROM messages
        WHERE message_id = ?
          AND sender_id = ?
          AND receiver_id = ?
          AND status = 'Sent'
    ";
    if ($verify_stmt = $conn->prepare($verify_sql)) {
        $verify_stmt->bind_param("iii", $message_id, $passenger_id, $company_id);
        $verify_stmt->execute();
        $verify_stmt->store_result();
        if ($verify_stmt->num_rows === 0) {
            $error = "Message not found or already replied.";
            if ($isAjax) {
                http_response_code(404);
                echo json_encode(['error' => $error]);
            } else {
                $_SESSION['reply_error'] = $error;
                header("Location: ../../html/company_home.html");
            }
            $verify_stmt->close();
            exit();
        }
        $verify_stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
        if ($isAjax) {
            http_response_code(500);
            echo json_encode(['error' => $error]);
        } else {
            $_SESSION['reply_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
        exit();
    }

    // BEGIN TRANSACTION
    $conn->begin_transaction();
    try {
        // 1) Insert new row for the reply
        $insert_sql = "
           INSERT INTO messages (sender_id, receiver_id, message_content, status)
           VALUES (?, ?, ?, 'Sent')
        ";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iis", $company_id, $passenger_id, $reply_content);
        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to send reply: " . $insert_stmt->error);
        }
        $insert_stmt->close();

        // 2) Mark the original message as 'Replied'
        $update_sql = "
           UPDATE messages
           SET status = 'Replied'
           WHERE message_id = ?
        ";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $message_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update original message status.");
        }
        $update_stmt->close();

        // COMMIT
        $conn->commit();

        $response['success'] = "Reply sent successfully!";
        if ($isAjax) {
            echo json_encode($response);
        } else {
            $_SESSION['reply_success'] = $response['success'];
            header("Location: ../../html/company_home.html");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        if ($isAjax) {
            http_response_code(500);
            echo json_encode(['error' => $error]);
        } else {
            $_SESSION['reply_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
    }

    $conn->close();
    exit();
} else {
    $error = "Invalid request method.";
    if ($isAjax) {
        http_response_code(405);
        echo json_encode(['error' => $error]);
    } else {
        $_SESSION['reply_error'] = $error;
        header("Location: ../../html/company_home.html");
    }
    exit();
}
?>
