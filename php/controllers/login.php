<?php
// Include database configuration
include('../config/db_config.php');

// Start session to manage user login state
session_start();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the fields are not empty
    if (empty($email) || empty($password)) {
        echo "Please fill all the fields!";
    } else {
        // Prepare the query to check if the user exists in the database
        $sql = "SELECT * FROM users WHERE email = ?";

        // Prepare statement
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email); // Bind email to the query
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();

        // Check if user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start the session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];

                // Redirect to dashboard or home page after successful login
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Incorrect password!";
            }
        } else {
            echo "No user found with this email address!";
        }
    }
}
?>

