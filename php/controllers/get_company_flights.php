<?php
// get_company_flights.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Prepare SQL statement to fetch all flights for the company
$query = "SELECT flight_id, name FROM flights WHERE company_id = ? AND status != 'Cancelled'";

if ($stmt = $conn->prepare($query)) {
    $company_id = $_SESSION['user_id'];
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $flights = [];
    while ($row = $result->fetch_assoc()) {
        $flights[] = $row;
    }

    echo json_encode(['flights' => $flights]);
    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: Unable to prepare statement.']);
}

$conn->close();
?>
