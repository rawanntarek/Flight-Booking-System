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

    // Check if the email and password fields are not empty
    if (!empty($email) && !empty($password)) {

        // Sanitize input to prevent SQL injection
        $email = mysqli_real_escape_string($conn, $email);
        $password = mysqli_real_escape_string($conn, $password);

        // SQL query to fetch user details from database
        $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
        $result = mysqli_query($conn, $sql);

        // Check if the user exists in the database
        if (mysqli_num_rows($result) == 1) {
            // Fetch user data
            $user = mysqli_fetch_assoc($result);

            // Store user details in session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];

            // Redirect to the dashboard or homepage after successful login
            header("Location: ../dashboard.php");  // Change this to your desired page
            exit();
        } else {
            // If login fails, display an error message
            $error_message = "Invalid email or password.";
        }

    } else {
        // Display error if fields are empty
        $error_message = "Please fill in both fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/Login.css">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        
        <!-- Display error message if login fails -->
        <?php if (isset($error_message)) { echo "<p style='color:red;'>$error_message</p>"; } ?>

        <form action="login.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="registration.php">Register</a></p>
    </div>
</body>
</html>
