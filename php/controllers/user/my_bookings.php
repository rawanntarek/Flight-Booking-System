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
$user_id = $_SESSION['user_id'];
$bookings = [];
$errors = [];

// Enable detailed error reporting (for development only)
// Comment out or remove these lines in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch user's bookings
$bookingSql = "
    SELECT 
        f.flight_id, 
        f.name, 
        f.from_location, 
        f.to_location, 
        f.fees, 
        f.start_time, 
        f.end_time, 
        f.status AS flight_status,  -- <---
        f.completed, 
        fp.status AS booking_status,
        fp.payment_method
    FROM flights f
    JOIN flight_passengers fp ON f.flight_id = fp.flight_id
    WHERE fp.user_id = ?
    ORDER BY f.start_time DESC
";

if ($stmt = $conn->prepare($bookingSql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else {
        $errors[] = "Database error: Execution failed. " . $stmt->error;
    }
    $stmt->close();
} else {
    // Capture and display the specific error
    $errors[] = "Database error: Unable to prepare statement. " . $conn->error;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings</title>
    <link rel="stylesheet" href="../../../css/mybookings.css">
</head>
<body>
    <div class="container">
        <h2>My Bookings</h2>

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

        <!-- Display bookings if any -->
        <?php if (!empty($bookings)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Flight ID</th>
                        <th>Name</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Fees ($)</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Booking Status</th>
                        <th>Payment Method</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
    <td><?php echo htmlspecialchars($booking['flight_id']); ?></td>
    <td><?php echo htmlspecialchars($booking['name']); ?></td>
    <td><?php echo htmlspecialchars($booking['from_location']); ?></td>
    <td><?php echo htmlspecialchars($booking['to_location']); ?></td>
    <td><?php echo htmlspecialchars(number_format($booking['fees'], 2)); ?></td>
    <td><?php echo htmlspecialchars($booking['start_time']); ?></td>
    <td><?php echo htmlspecialchars($booking['end_time']); ?></td>
    <td><?php 
        // The flight's status
        if ($booking['flight_status'] === 'Cancelled') {
            echo '<span style="color:red;">Cancelled</span>';
        } elseif ($booking['completed']) {
            echo 'Completed';
        } else {
            echo 'Active';
        }
    ?></td>
    <td><?php
        // The user's booking status:
        if ($booking['booking_status'] === 'Registered') {
            echo '<span class="status-registered">Registered</span>';
        } elseif ($booking['booking_status'] === 'Pending') {
            echo '<span class="status-pending">Pending</span>';
        } else {
            echo htmlspecialchars($booking['booking_status']);
        }
    ?></td>
    <td><?php echo htmlspecialchars(ucfirst($booking['payment_method'])); ?></td>
    <td>
        <a href="flight_info.php?flight_id=<?php echo urlencode($booking['flight_id']); ?>">View Details</a>
    </td>
</tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no bookings.</p>
        <?php endif; ?>

        <!-- Back to Dashboard Link -->
        <a href="../../../html/dashboard.html" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
