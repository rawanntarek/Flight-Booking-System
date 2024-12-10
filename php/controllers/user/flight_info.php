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
    $flightSql = "SELECT flight_id, name, from_location, to_location, fees, start_time, end_time, completed FROM flights WHERE flight_id = ?";

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
    <!-- Link to the combined CSS -->
    <link rel="stylesheet" href="../../../css/styles.css"> <!-- Ensure this path is correct -->
    <!-- Google Fonts (If using Open Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="limiter">
        <div class="container-table100">
            <div class="wrap-table100">
                <div class="table100">
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
                                    </tbody>
                                </table>
                            </div>

                            <!-- Booking Section -->
                            <div class="book-section">
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
                                        <!-- For Companies, payment method is handled differently or not required -->
                                        <!-- If Companies need to select payment method, uncomment below -->
                                        <!--
                                        <label for="payment_method">Payment Method:</label>
                                        <select name="payment_method" id="payment_method" required>
                                            <option value="" disabled selected>Select Payment Method</option>
                                            <option value="balance">Pay from Account Balance</option>
                                            <option value="cash">Pay Cash</option>
                                        </select>
                                        -->
                                        <!-- If not needed, you can remove the payment_method field or set a default value in book_flight.php -->
                                    <?php endif; ?>

                                    <input type="submit" value="Book Flight">
                                </form>
                            </div>
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
