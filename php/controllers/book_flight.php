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

    // Fetch flight details
    $flightSql = "SELECT flight_id, fees, completed FROM flights WHERE flight_id = ?";
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

    // If flight is found, proceed
    if (isset($flight)) {
        if ($flight['completed']) {
            $errors[] = "Cannot book a completed flight.";
        }

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
            $errors[] = "Database error: Unable to prepare statement.";
        }
    }

    // Determine payment method
    $user_type = $_SESSION['user_type'];
    if ($user_type === 'Company') {
        if (empty($payment_method) || !in_array($payment_method, ['balance', 'cash'])) {
            $errors[] = "Invalid payment method selected.";
        }
    } elseif ($user_type === 'Passenger') {
        // For Passengers, payment method is always 'balance'
        $payment_method = 'balance';
    } else {
        $errors[] = "Unknown user type.";
    }

    // If no errors, proceed with booking
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            if ($user_type === 'Passenger' || ($user_type === 'Company' && $payment_method === 'balance')) {
                // For Passenger or Company paying from balance

                // Check if user has sufficient balance
                $user_id = $_SESSION['user_id'];
                $fee = floatval($flight['fees']);

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

                    // Deduct the fee from account_balance
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
                } else {
                    throw new Exception("Database error: Unable to prepare balance check statement.");
                }

                // Insert into flight_passengers with status 'Registered'
                $insertBookingSql = "INSERT INTO flight_passengers (flight_id, user_id, status) VALUES (?, ?, 'Registered')";
                if ($stmt = $conn->prepare($insertBookingSql)) {
                    $stmt->bind_param("ii", $flight_id, $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create booking.");
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error: Unable to prepare booking statement.");
                }

                // Commit transaction
                $conn->commit();

                $success = "Flight booked successfully and $" . number_format($fee, 2) . " has been deducted from your account balance.";
            } elseif ($user_type === 'Company' && $payment_method === 'cash') {
                // For Company paying cash

                // Insert into flight_passengers with status 'Pending'
                $insertBookingSql = "INSERT INTO flight_passengers (flight_id, user_id, status) VALUES (?, ?, 'Pending')";
                if ($stmt = $conn->prepare($insertBookingSql)) {
                    $stmt->bind_param("ii", $flight_id, $_SESSION['user_id']);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create booking.");
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Database error: Unable to prepare booking statement.");
                }

                // Commit transaction
                $conn->commit();

                $success = "Flight booked successfully. Please proceed to pay cash at the designated location.";
            } else {
                throw new Exception("Invalid booking process.");
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
    <link rel="stylesheet" href="../../css/Registration.css"> <!-- Reusing Registration.css for styling -->
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

        <!-- Back to Flight Info Link -->
        <a href="../../html/dashboard.html" class="button">Back to Flight Info</a>
    </div>
</body>
</html>
