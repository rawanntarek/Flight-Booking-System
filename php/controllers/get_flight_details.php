<?php
// get_flight_details.php

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

// Get flight_id from GET parameters
$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;

if ($flight_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid flight ID.']);
    exit();
}

// Prepare SQL statement to fetch flight details
$query = "SELECT flight_id, name, itinerary, flight_time FROM flights WHERE flight_id = ? AND company_id = ?";

if ($stmt = $conn->prepare($query)) {
    $company_id = $_SESSION['user_id'];
    $stmt->bind_param("ii", $flight_id, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $flight = $result->fetch_assoc();

        // Fetch pending passengers
        $pending_query = "SELECT passengers.name FROM passengers JOIN flight_passengers ON passengers.passenger_id = flight_passengers.passenger_id WHERE flight_passengers.flight_id = ? AND flight_passengers.status = 'Pending'";
        if ($pending_stmt = $conn->prepare($pending_query)) {
            $pending_stmt->bind_param("i", $flight_id);
            $pending_stmt->execute();
            $pending_result = $pending_stmt->get_result();
            $pending_passengers = [];
            while ($row = $pending_result->fetch_assoc()) {
                $pending_passengers[] = $row;
            }
            $pending_stmt->close();
        } else {
            $pending_passengers = [];
        }

        // Fetch registered passengers
        $registered_query = "SELECT passengers.name FROM passengers JOIN flight_passengers ON passengers.passenger_id = flight_passengers.passenger_id WHERE flight_passengers.flight_id = ? AND flight_passengers.status = 'Registered'";
        if ($registered_stmt = $conn->prepare($registered_query)) {
            $registered_stmt->bind_param("i", $flight_id);
            $registered_stmt->execute();
            $registered_result = $registered_stmt->get_result();
            $registered_passengers = [];
            while ($row = $registered_result->fetch_assoc()) {
                $registered_passengers[] = $row;
            }
            $registered_stmt->close();
        } else {
            $registered_passengers = [];
        }

        // Prepare response
        $response = [
            'flight' => [
                'flight_id' => $flight['flight_id'],
                'name' => $flight['name'],
                'itinerary' => $flight['itinerary'],
                'flight_time' => $flight['flight_time']
            ],
            'pending_passengers' => $pending_passengers,
            'registered_passengers' => $registered_passengers
        ];

        echo json_encode($response);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Flight not found.']);
    }

    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: Unable to prepare statement.']);
}

$conn->close();
?>
