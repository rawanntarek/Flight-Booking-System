<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: ../../html/login.html");
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Initialize variables
$flight_id = 0;
$flight = null;
$errors = [];

// Check if 'flight_id' is provided via GET
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['flight_id'])) {
    $flight_id = intval($_GET['flight_id']);

    // Prepare SQL statement to fetch flight details
    $flightSql = "SELECT flight_id, name, itinerary, fees, start_time, end_time, completed FROM flights WHERE flight_id = ?";

    if ($stmt = $conn->prepare($flightSql)) {
        $stmt->bind_param("i", $flight_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $flight = $result->fetch_assoc();
        } else {
            $errors[] = "Flight not found.";
        }

        $stmt->close();
    } else {
        $errors[] = "Database error: Unable to prepare statement.";
    }
} else {
    $errors[] = "Invalid request.";
}

// Close the database connection
$conn->close();

// Decode itinerary JSON
if ($flight && isset($flight['itinerary'])) {
    $itinerary = json_decode($flight['itinerary'], true);
} else {
    $itinerary = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flight Information</title>
    <link rel="stylesheet" href="../../css/Registration.css"> <!-- Reusing Registration.css for styling -->
    <link rel="stylesheet" href="../../css/flight_info.css"> <!-- Reusing Registration.css for styling -->
</head>
<body>
    <div class="container">
        <h2>Flight Information</h2>

        <!-- Display error messages -->
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Display flight details if available -->
        <?php if ($flight): ?>
            <div class="flight-details">
                <h3><?php echo htmlspecialchars($flight['name']); ?></h3>
                <p><strong>Flight ID:</strong> <?php echo htmlspecialchars($flight['flight_id']); ?></p>
                <p><strong>From:</strong> <?php echo htmlspecialchars($itinerary['from'] ?? 'N/A'); ?></p>
                <p><strong>To:</strong> <?php echo htmlspecialchars($itinerary['to'] ?? 'N/A'); ?></p>
                <p><strong>Fees:</strong> $<?php echo htmlspecialchars($flight['fees']); ?></p>
                <p><strong>Start Time:</strong> <?php echo htmlspecialchars($flight['start_time']); ?></p>
                <p><strong>End Time:</strong> <?php echo htmlspecialchars($flight['end_time']); ?></p>
                <p><strong>Status:</strong> <?php echo ($flight['completed']) ? 'Completed' : 'Upcoming'; ?></p>
            </div>
        <?php endif; ?>

        <!-- Back to Search Results Link -->
        <a href="javascript:history.back()" class="back-link">← Back to Search Results</a>
    </div>
</body>
</html>
