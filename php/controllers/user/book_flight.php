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
$payment_method = '';
$errors = [];
$success = "";

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $flight_id = intval($_POST['flight_id'] ?? 0);
    $payment_method = sanitize_input($_POST['payment_method'] ?? '');

    // Basic validation
    if ($flight_id <= 0) {
        $errors[] = "Invalid Flight ID.";
    }

    // Fetch flight details (including 'status' if you have that column)
    $flightSql = "SELECT flight_id, fees, completed, status FROM flights WHERE flight_id = ?";
    if ($stmt = $conn->prepare($flightSql)) {
        $stmt->bind_param("i", $flight_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $flight = $result->fetch_assoc();

            // 1) If the flight is cancelled, reject booking
            if (isset($flight['status']) && $flight['status'] === 'Cancelled') {
                $errors[] = "Cannot book a cancelled flight.";
            }

            // 2) If the flight is completed (completed=1), reject booking
            if ($flight['completed'] == 1) {
                $errors[] = "Cannot book a completed flight.";
            }
        } else {
            $errors[] = "Flight not found.";
        }

        $stmt->close();
    } else {
        $errors[] = "Database error: Unable to prepare statement for flight details.";
    }

    // If flight is found, proceed
    if (isset($flight) && empty($errors)) {
        // Check if the user has already booked this flight
        $checkBookingSql = "SELECT * FROM flight_passengers WHERE flight_id = ? AND user_id = ?";
        if ($stmt = $conn->prepare($checkBookingSql)) {
            $stmt->bind_param("ii", $flight_id, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "You have already booked this flight.";
            }

            $stmt->close();
        } else {
            $errors[] = "Database error: Unable to prepare statement for existing booking check.";
        }
    }

    // Determine payment method
    $user_type = $_SESSION['user_type'];
    if ($user_type === 'Company' || $user_type === 'Passenger') {
        if (empty($payment_method) || !in_array($payment_method, ['balance', 'cash'])) {
            $errors[] = "Invalid payment method selected.";
        }
    } else {
        $errors[] = "Unknown user type.";
    }

    // If no errors so far, proceed with booking
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // We'll handle Passenger or Company similarly, except that both can pay by 'cash' or 'balance'.
            $user_id = $_SESSION['user_id'];
            $fee = floatval($flight['fees']);

            if ($payment_method === 'balance') {
                // 1) Check & deduct from user balance
                $balanceSql = "SELECT account_balance FROM users WHERE user_id = ?";
                if ($stmt = $conn->prepare($balanceSql)) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->bind_result($account_balance);
                    $stmt->fetch();
                    $stmt->close();

                    if ($account_balance < $fee) {
                        throw new Exception("Insufficient account balance.");
                    }

                    // Deduct the fee
                    $new_balance = $account_balance - $fee;
                    $updateBalanceSql = "UPDATE users SET account_balance = ? WHERE user_id = ?";
                    if ($stmt = $conn->prepare($updateBalanceSql)) {
                        $stmt->bind_param("di", $new_balance, $user_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to update account balance.");
                        }
                        $stmt->close();
                    } else {
                        throw new Exception("Database error: Unable to prepare balance update statement.");
                    }

                    // 2) Insert into flight_passengers as 'Registered'
                    $insertBookingSql = "INSERT INTO flight_passengers (flight_id, user_id, status, payment_method)
                                         VALUES (?, ?, 'Registered', 'balance')";
                    if ($stmt = $conn->prepare($insertBookingSql)) {
                        $stmt->bind_param("ii", $flight_id, $user_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to create booking.");
                        }
                        $stmt->close();
                    } else {
                        throw new Exception("Database error: Unable to prepare booking statement.");
                    }

                    // Commit
                    $conn->commit();
                    $success = "Flight booked successfully and $".number_format($fee, 2)." has been deducted from your account balance.";
                } else {
                    throw new Exception("Database error: Unable to prepare balance check statement.");
                }
            } elseif ($payment_method === 'cash') {
                // Insert with status 'Pending'
                $insertBookingSql = "INSERT INTO flight_passengers (flight_id, user_id, status, payment_method)
                                     VALUES (?, ?, 'Pending', 'cash')";
                if ($stmt = $conn->prepare($insertBookingSql)) {
                    $stmt->bind_param("ii", $flight_id, $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create booking.");
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error: Unable to prepare booking statement.");
                }

                // Commit
                $conn->commit();
                $success = "Flight booked successfully. Please proceed to pay cash at the designated location.";
            } else {
                throw new Exception("Invalid payment method.");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Flight</title>
    <link rel="stylesheet" href="../../../css/Registration.css"> <!-- Reusing Registration.css for styling -->
    <style>
        .container {
            width: 60%;
            margin: auto;
            padding-top: 30px;
            text-align: center;
        }
        .errors {
            color: red;
        }
        .success {
            color: green;
        }
        a.button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        a.button:hover {
            background-color: #0b7dda;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Book Flight</h2>

        <!-- Display success message -->
        <?php if (!empty($success)): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

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

        <!-- Back to Flight Info or Dashboard link -->
        <a href="../../../html/dashboard.html" class="button">Back to Dashboard</a>
    </div>
</body>
</html>
