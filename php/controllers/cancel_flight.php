<?php
// cancel_flight.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Helper to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if this is an AJAX POST request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Return array
$response = [];

// Handle POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve flight_id
    $flight_id = isset($_POST['flight_id']) ? intval($_POST['flight_id']) : 0;

    if ($flight_id <= 0) {
        $error = "Invalid flight ID.";
        if ($isAjax) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => $error]);
        } else {
            $_SESSION['cancel_flight_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1) Verify that the flight belongs to this company
        $company_id = $_SESSION['user_id'];
        $verify_sql = "SELECT flight_id 
                       FROM flights 
                       WHERE flight_id = ? 
                         AND company_id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        if (!$verify_stmt) {
            throw new Exception("Database error (verify_stmt): " . $conn->error);
        }
        $verify_stmt->bind_param("ii", $flight_id, $company_id);
        $verify_stmt->execute();
        $verify_stmt->store_result();
        if ($verify_stmt->num_rows !== 1) {
            throw new Exception("Flight not found or does not belong to your company.");
        }
        $verify_stmt->close();

        // 2) Fetch all registered passengers (with flight fee)
        //    We'll join flight_passengers -> users -> flights (to get fees).
        $passengers_sql = "
            SELECT u.user_id, u.name, u.email, f.fees
            FROM flight_passengers fp
            JOIN users u ON fp.user_id = u.user_id
            JOIN flights f ON fp.flight_id = f.flight_id
            WHERE fp.flight_id = ?
              AND fp.status = 'Registered'
        ";
        $passengers_stmt = $conn->prepare($passengers_sql);
        if (!$passengers_stmt) {
            throw new Exception("Database error (passengers_stmt): " . $conn->error);
        }
        $passengers_stmt->bind_param("i", $flight_id);
        $passengers_stmt->execute();
        $result = $passengers_stmt->get_result();

        // Build an array of passengers to refund
        $passengers = [];
        while ($row = $result->fetch_assoc()) {
            $passengers[] = $row; 
        }
        $passengers_stmt->close();

        // 3) Refund each passenger
        //    Add flight.fees back to their users.account_balance
        foreach ($passengers as $passenger) {
            $refund_sql = "UPDATE users 
                           SET account_balance = account_balance + ? 
                           WHERE user_id = ?";
            $refund_stmt = $conn->prepare($refund_sql);
            if (!$refund_stmt) {
                throw new Exception("Database error (refund_stmt): " . $conn->error);
            }
            $fee     = $passenger['fees'];
            $user_id = $passenger['user_id']; // from the 'users' table
            $refund_stmt->bind_param("di", $fee, $user_id);
            if (!$refund_stmt->execute()) {
                throw new Exception("Failed to refund user_id={$user_id}: " . $refund_stmt->error);
            }
            $refund_stmt->close();
        }

        // 4) Optionally mark flight as "Cancelled"
        //    If you have a `status` column or you can set `completed = 1`.
        //    We'll assume you have a `status` column for "Cancelled"
        $cancel_sql = "UPDATE flights 
                       SET status = 'Cancelled' 
                       WHERE flight_id = ?";
        $cancel_stmt = $conn->prepare($cancel_sql);
        if (!$cancel_stmt) {
            throw new Exception("Database error (cancel_stmt): " . $conn->error);
        }
        $cancel_stmt->bind_param("i", $flight_id);
        if (!$cancel_stmt->execute()) {
            throw new Exception("Failed to cancel the flight: " . $cancel_stmt->error);
        }
        $cancel_stmt->close();

        // 5) Commit the transaction
        $conn->commit();

        // Optionally send emails to each refunded passenger (omitted here)

        // Return success
        $response['success'] = "Flight cancelled and passengers refunded successfully.";
        if ($isAjax) {
            echo json_encode($response);
        } else {
            $_SESSION['cancel_flight_success'] = $response['success'];
            header("Location: ../../html/company_home.html");
        }
        exit();

    } catch (Exception $e) {
        // Something went wrong, rollback
        $conn->rollback();
        $error = $e->getMessage();
        if ($isAjax) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => $error]);
        } else {
            $_SESSION['cancel_flight_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
        exit();
    }

} else {
    // Invalid request method
    $error = "Invalid request method.";
    if ($isAjax) {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => $error]);
    } else {
        $_SESSION['cancel_flight_error'] = $error;
        header("Location: ../../html/company_home.html");
    }
    exit();
}
