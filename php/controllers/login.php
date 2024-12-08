<?php
// Start the session
session_start();

// Include the database configuration file
require_once '../config/db_config.php';

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Password will be verified, so no need to sanitize here

    // Basic validation
    if (empty($email) || empty($password)) {
        // You can redirect back with an error message or handle it as per your design
        die("Please fill in all required fields.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Prepare SQL statement to retrieve user by email
    $query = "SELECT user_id, name, email, password, user_type, account_balance FROM users WHERE email = ?";

    if ($stmt = $conn->prepare($query)) {
        // Bind the email parameter to the SQL statement
        $stmt->bind_param("s", $email);
        
        // Execute the prepared statement
        $stmt->execute();
        
        // Store the result to check if the user exists
        $stmt->store_result();

        // Check if a user with the provided email exists
        if ($stmt->num_rows == 1) {
            // Bind the result variables
            $stmt->bind_result($user_id, $name, $email_db, $hashed_password, $user_type, $account_balance);
            $stmt->fetch();

            // Verify the password using password_verify
            if (password_verify($password, $hashed_password)) {
                // Password is correct; set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email_db;
                $_SESSION['user_type'] = $user_type;
                $_SESSION['account_balance'] = $account_balance;

                // Close the statement and connection
                $stmt->close();
                $conn->close();

                // Redirect to dashboard.html
                header("Location: ../../html/dashboard.html");
                exit();
            } else {
                // Password is incorrect
                $stmt->close();
                $conn->close();
                die("Invalid email or password.");
            }
        } else {
            // No user found with the provided email
            $stmt->close();
            $conn->close();
            die("Invalid email or password.");
        }
    } else {
        // Failed to prepare the SQL statement
        die("Database error: Unable to prepare statement.");
    }
} else {
    // If the form was not submitted via POST, redirect to the login page
    header("Location: ../../html/Login.html");
    exit();
}
?>
