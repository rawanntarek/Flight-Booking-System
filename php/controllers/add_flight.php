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
    // $flight_id = isset($_POST['flight_id']) ? sanitize_input($_POST['flight_id']) : ''; // If flight ID is manual
    $itinerary = isset($_POST['itinerary']) ? sanitize_input($_POST['itinerary']) : '';
    $fees = isset($_POST['fees']) ? floatval($_POST['fees']) : 0.0;
    $max_passengers = isset($_POST['max_passengers']) ? intval($_POST['max_passengers']) : 0;
    $flight_time = isset($_POST['flight_time']) ? sanitize_input($_POST['flight_time']) : '';

    // Basic validation
    if (empty($name) || empty($itinerary) || $fees <= 0 || $max_passengers <= 0 || empty($flight_time)) {
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

    // Prepare SQL statement to insert new flight
    // Assuming flight_id is auto-incremented in the database
    $query = "INSERT INTO flights (company_id, name, itinerary, fees, max_passengers, flight_time) VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($query)) {
        $company_id = $_SESSION['user_id'];
        $stmt->bind_param("issdis", $company_id, $name, $itinerary, $fees, $max_passengers, $flight_time);
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
            $error = "Failed to add flight: " . $stmt->error;
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
        $error = "Database error: Unable to prepare statement.";
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
