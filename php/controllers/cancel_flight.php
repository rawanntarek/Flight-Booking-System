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
    // Retrieve and sanitize flight_id
    $flight_id = isset($_POST['flight_id']) ? intval($_POST['flight_id']) : 0;

    if ($flight_id <= 0) {
        $error = "Invalid flight ID.";
        if ($isAjax) {
            http_response_code(400); // Bad Request
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['cancel_flight_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Verify that the flight belongs to the company
        $verify_query = "SELECT flight_id FROM flights WHERE flight_id = ? AND company_id = ?";
        if ($verify_stmt = $conn->prepare($verify_query)) {
            $company_id = $_SESSION['user_id'];
            $verify_stmt->bind_param("ii", $flight_id, $company_id);
            $verify_stmt->execute();
            $verify_stmt->store_result();
            if ($verify_stmt->num_rows !== 1) {
                throw new Exception("Flight not found or does not belong to your company.");
            }
            $verify_stmt->close();
        } else {
            throw new Exception("Database error: Unable to prepare statement.");
        }

        // Fetch all registered passengers for the flight
        $passengers_query = "SELECT passengers.passenger_id, passengers.name, passengers.email, flight_passengers.fee_paid FROM passengers JOIN flight_passengers ON passengers.passenger_id = flight_passengers.passenger_id WHERE flight_passengers.flight_id = ? AND flight_passengers.status = 'Registered'";
        if ($passengers_stmt = $conn->prepare($passengers_query)) {
            $passengers_stmt->bind_param("i", $flight_id);
            $passengers_stmt->execute();
            $passengers_result = $passengers_stmt->get_result();
            $passengers = [];
            while ($row = $passengers_result->fetch_assoc()) {
                $passengers[] = $row;
            }
            $passengers_stmt->close();
        } else {
            throw new Exception("Database error: Unable to prepare passengers statement.");
        }

        // Refund each passenger's fee
        foreach ($passengers as $passenger) {
            $refund_query = "UPDATE users SET account_balance = account_balance + ? WHERE user_id = ?";
            if ($refund_stmt = $conn->prepare($refund_query)) {
                $fee = $passenger['fee_paid'];
                $passenger_id = $passenger['passenger_id'];
                $refund_stmt->bind_param("di", $fee, $passenger_id);
                if (!$refund_stmt->execute()) {
                    throw new Exception("Failed to refund passenger ID: " . $passenger_id);
                }
                $refund_stmt->close();
            } else {
                throw new Exception("Database error: Unable to prepare refund statement.");
            }
        }

        // Update flight status to cancelled
        $cancel_query = "UPDATE flights SET status = 'Cancelled' WHERE flight_id = ?";
        if ($cancel_stmt = $conn->prepare($cancel_query)) {
            $cancel_stmt->bind_param("i", $flight_id);
            if (!$cancel_stmt->execute()) {
                throw new Exception("Failed to cancel the flight.");
            }
            $cancel_stmt->close();
        } else {
            throw new Exception("Database error: Unable to prepare cancel flight statement.");
        }

        // Commit transaction
        $conn->commit();

        // Send emails to passengers (optional; requires mail server configuration)
        /*
        foreach ($passengers as $passenger) {
            $to = $passenger['email'];
            $subject = "Flight Cancellation Notice";
            $message = "Dear " . $passenger['name'] . ",\n\nWe regret to inform you that the flight \"" . $flight['name'] . "\" has been cancelled. Your fee of $" . $passenger['fee_paid'] . " has been refunded to your account.\n\nBest regards,\nTrain Booking System";
            $headers = "From: no-reply@trainbookingsystem.com";

            mail($to, $subject, $message, $headers);
        }
        */

        $response['success'] = "Flight cancelled and passengers refunded successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        if ($isAjax) {
            http_response_code(500); // Internal Server Error
            $response['error'] = $error;
        } else {
            $_SESSION['cancel_flight_error'] = $error;
            header("Location: ../../html/company_home.html");
        }
        echo json_encode($response);
        exit();
    }

    // Close the database connection
    $conn->close();

    // Send success response
    if ($isAjax) {
        echo json_encode($response);
    } else {
        $_SESSION['cancel_flight_success'] = $response['success'];
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
        $_SESSION['cancel_flight_error'] = $error;
        header("Location: ../../html/company_home.html");
    }
    exit();
}
?>
