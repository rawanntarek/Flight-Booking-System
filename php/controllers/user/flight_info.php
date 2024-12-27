<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: ../../../html/login.html");
    exit();
}

// Include the database configuration file
require_once '../../config/db_config.php';

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Initialize variables
$flight_id = 0;
$flight = null;
$errors = [];
$success = "";

// Check if 'flight_id' is provided via GET
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['flight_id'])) {
    $flight_id = intval($_GET['flight_id']);

    // Prepare SQL statement to fetch flight details
// flight_info.php

$flightSql = "SELECT flight_id, name, from_location, to_location, fees, start_time, end_time, completed, status
              FROM flights
              WHERE flight_id = ?";

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flight Information</title>
    <link rel="stylesheet" href="../../../css/userFlightDetails.css"> <!-- Ensure this path is correct -->
   </head>
<body>
    <div>
        <div >
            <div >
                <div>
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

                        <!-- Display success message -->
                        <?php if (!empty($success)): ?>
                            <div class="success">
                                <p><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Display flight details if available -->
                        <?php if ($flight): ?>
                            <div class="flight-details">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Attribute</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td data-label="Attribute"><strong>Flight ID</strong></td>
                                            <td data-label="Details"><?php echo htmlspecialchars($flight['flight_id']); ?></td>
                                        </tr>
                                        <tr>
                                            <td data-label="Attribute"><strong>Name</strong></td>
                                            <td data-label="Details"><?php echo htmlspecialchars($flight['name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td data-label="Attribute"><strong>From</strong></td>
                                            <td data-label="Details"><?php echo htmlspecialchars($flight['from_location']); ?></td>
                                        </tr>
                                        <tr>
                                            <td data-label="Attribute"><strong>To</strong></td>
                                            <td data-label="Details"><?php echo htmlspecialchars($flight['to_location']); ?></td>
                                        </tr>
                                        <tr>
                                            <td data-label="Attribute"><strong>Fees</strong></td>
                                            <td data-label="Details">$<?php echo htmlspecialchars(number_format($flight['fees'], 2)); ?></td>
                                        </tr>
                                        <tr>
                                            <td data-label="Attribute"><strong>Start Time</strong></td>
                                            <td data-label="Details"><?php echo htmlspecialchars($flight['start_time']); ?></td>
                                        </tr>
                                        <tr>
                                            <td data-label="Attribute"><strong>End Time</strong></td>
                                            <td data-label="Details"><?php echo htmlspecialchars($flight['end_time']); ?></td>
                                        </tr>
                                        <tr>
                                            <td data-label="Attribute"><strong>Status</strong></td>
                                            <td data-label="Details"><?php echo ($flight['completed']) ? 'Completed' : 'Upcoming'; ?></td>
                                        </tr>

                                        <tr>
                                            <td data-label="Attribute"><strong>Status</strong></td>
                                            <td data-label="Details">
                                                <?php 
                                                if ($flight['status'] === 'Cancelled') {
                                                    echo 'Cancelled';
                                                } elseif ($flight['completed']) {
                                                    echo 'Completed';
                                                } else {
                                                    echo 'Active';
                                                }
                                                ?>
                                            </td>
                                        </tr>


                                    </tbody>
                                </table>
                            </div>

                        <!-- Booking Section -->
<?php if ($flight['status'] === 'Cancelled'): ?>
    <div class="book-section">
        <h3>This flight is cancelled</h3>
        <p>You cannot book a cancelled flight.</p>
    </div>
<?php elseif ($flight['completed']): ?>
    <div class="book-section">
        <h3>This flight is completed</h3>
        <p>You cannot book a completed flight.</p>
    </div>
<?php else: ?>
    <!-- Normal booking form here -->
    <div class="book-section" style="display:none;">   
        <h3>Book This Flight</h3>
        <form method="POST" action="../controllers/user/book_flight.php">
            <input type="hidden" name="flight_id" value="<?php echo htmlspecialchars($flight['flight_id']); ?>">
            <?php if ($_SESSION['user_type'] === 'Passenger'): ?>
                <label for="payment_method">Payment Method:</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="" disabled selected>Select Payment Method</option>
                    <option value="balance">Pay from Account Balance</option>
                    <option value="cash">Pay Cash</option>
                </select>
            <?php elseif ($_SESSION['user_type'] === 'Company'): ?>
                <!-- etc. -->
            <?php endif; ?>
            <input type="submit" value="Book Flight">
        </form>
    </div>
<?php endif; ?>
                            
                        <?php endif; ?>

                        <!-- Back to Search Results Link -->
                        <a href="javascript:history.back()" class="back-link">← Back to Search Results</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
