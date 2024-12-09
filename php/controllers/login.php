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
        // Redirect back with an error message
        $_SESSION['login_error'] = "Please fill in all required fields.";
        header("Location: ../../html/Login.html");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = "Invalid email format.";
        header("Location: ../../html/Login.html");
        exit();
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

                // Redirect based on user_type
                if ($user_type === 'Passenger') {
                    header("Location: ../../html/dashboard.html");
                } elseif ($user_type === 'Company') {
                    header("Location: ../../html/company_home.html");
                } else {
                    // In case user_type is neither 'Passenger' nor 'Company'
                    $_SESSION['login_error'] = "Unknown user type.";
                    header("Location: ../../html/Login.html");
                }
                exit();
            } else {
                // Password is incorrect
                $_SESSION['login_error'] = "Invalid email or password.";
                $stmt->close();
                $conn->close();
                header("Location: ../../html/Login.html");
                exit();
            }
        } else {
            // No user found with the provided email
            $_SESSION['login_error'] = "Invalid email or password.";
            $stmt->close();
            $conn->close();
            header("Location: ../../html/Login.html");
            exit();
        }
    } else {
        // Failed to prepare the SQL statement
        $_SESSION['login_error'] = "Database error: Unable to prepare statement.";
        $conn->close();
        header("Location: ../../html/Login.html");
        exit();
    }
} else {
    // If the form was not submitted via POST, redirect to the login page
    header("Location: ../../html/Login.html");
    exit();
}
?>
