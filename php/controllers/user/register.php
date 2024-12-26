<?php
// Start the session
session_start();

// Include the database configuration file
require_once '../../config/db_config.php';

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $email = sanitize_input($_POST['email']);
    $name = sanitize_input($_POST['name']);
    $password = $_POST['password']; // Password will be hashed, so no need to sanitize here
    $telephone = sanitize_input($_POST['telephone']);
    $user_type = sanitize_input($_POST['user_type']);

    // Basic validation
    if (empty($email) || empty($name) || empty($password) || empty($telephone) || empty($user_type)) {
        die("Please fill in all required fields.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Validate user_type
    $allowed_user_types = ['Company', 'Passenger'];
    if (!in_array($user_type, $allowed_user_types)) {
        die("Invalid user type selected.");
    }

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Check if the email already exists
    $check_email_query = "SELECT email FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($check_email_query)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Email already exists
            $stmt->close();
            die("This email is already registered. Please use a different email or login.");
        }
        $stmt->close();
    } else {
        // SQL statement preparation failed
        die("Database error: Unable to prepare statement.");
    }

    // Insert the new user into the database
    $insert_query = "INSERT INTO users (name, email, password, telephone, user_type, account_balance) VALUES (?, ?, ?, ?, ?, 0.00)";
    
    if ($stmt = $conn->prepare($insert_query)) {
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $telephone, $user_type);
        
        if ($stmt->execute()) {
            // Get the inserted user_id
            $user_id = $stmt->insert_id;

            // Store user_id and user_type in session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_type'] = $user_type;

            // Close the statement and connection
            $stmt->close();
            $conn->close();

            // Redirect based on user_type
            if ($user_type === 'Company') {
                header("Location: ../../../html/company_extra_info.html");
                exit();
            } else {
                header("Location: ../../../html/passenger_extra_info.html");
                exit();
            }
        } else {
            // Insertion failed
            $stmt->close();
            $conn->close();
            die("Registration failed. Please try again.");
        }
    } else {
        // SQL statement preparation failed
        die("Database error: Unable to prepare statement.");
    }
} else {
    // If the form was not submitted via POST, redirect to registration page
    header("Location: ../../../html/Registration.html");
    exit();
}
?>
