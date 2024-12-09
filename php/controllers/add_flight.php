<?php
// add_flight.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    } else {
        header("Location: ../../html/Login.html");
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
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $from_location = isset($_POST['from_location']) ? sanitize_input($_POST['from_location']) : '';
    $to_location = isset($_POST['to_location']) ? sanitize_input($_POST['to_location']) : '';
    $fees = isset($_POST['fees']) ? floatval($_POST['fees']) : 0.0;
    $max_passengers = isset($_POST['max_passengers']) ? intval($_POST['max_passengers']) : 0;
    $start_time = isset($_POST['start_time']) ? sanitize_input($_POST['start_time']) : '';
    $end_time = isset($_POST['end_time']) ? sanitize_input($_POST['end_time']) : '';

    // Basic validation
    if (empty($name) || empty($from_location) || empty($to_location) || $fees <= 0 || $max_passengers <= 0 || empty($start_time) || empty($end_time)) {
        $error = "Please fill in all required fields with valid data.";
        if ($isAjax) {
            http_response_code(400); // Bad Request
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['add_flight_error'] = $error;
            header("Location: ../../html/add_flight.html");
        }
        exit();
    }

    // Additional validation: Ensure end_time is after start_time
    if (strtotime($end_time) <= strtotime($start_time)) {
        $error = "End Time must be after Start Time.";
        if ($isAjax) {
            http_response_code(400); // Bad Request
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['add_flight_error'] = $error;
            header("Location: ../../html/add_flight.html");
        }
        exit();
    }

    // Prepare SQL statement to insert new flight
    $query = "INSERT INTO flights (company_id, name, from_location, to_location, fees, max_passengers, flight_time, start_time, end_time, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($query)) {
        $company_id = $_SESSION['user_id'];
        $flight_time = $start_time; // Define flight_time as start_time or adjust as needed
        $capacity = $max_passengers; // Set capacity equal to max_passengers

        // Correct bind_param string to match 10 parameters
        $stmt->bind_param("isssdisssi", $company_id, $name, $from_location, $to_location, $fees, $max_passengers, $flight_time, $start_time, $end_time, $capacity);
        
        if ($stmt->execute()) {
            $success = "Flight added successfully!";
            $stmt->close();
            $conn->close();
            if ($isAjax) {
                echo json_encode(['success' => $success]);
            } else {
                $_SESSION['add_flight_success'] = $success;
                header("Location: ../../html/company_home.html");
            }
            exit();
        } else {
            // Log the error on the server
            error_log("Add Flight Error: " . $stmt->error);

            $error = "Database error. Please try again later.";
            $stmt->close();
            $conn->close();
            if ($isAjax) {
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => $error]);
            } else {
                $_SESSION['add_flight_error'] = $error;
                header("Location: ../../html/add_flight.html");
            }
            exit();
        }
    } else {
        // Log the error on the server
        error_log("Database Error: Unable to prepare statement in add_flight.php");

        $error = "Database error. Please try again later.";
        $conn->close();
        if ($isAjax) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => $error]);
        } else {
            $_SESSION['add_flight_error'] = $error;
            header("Location: ../../html/add_flight.html");
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
        $_SESSION['add_flight_error'] = $error;
        header("Location: ../../html/add_flight.html");
    }
    exit();
}
?>
