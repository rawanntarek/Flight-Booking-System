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

$errors = [];

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_GET['email'] ?? null;
    $bio = $_POST['bio'] ?? null;
    $address = $_POST['address'] ?? null;
    $location = $_POST['location'] ?? null;
    $logo = $_FILES['logo']['name'] ?? null;

    if ($email && $bio && $address && $logo) {
        // Upload the logo
        $upload_dir = '../uploads/';
        $logo_path = $upload_dir . basename($logo);
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
            // Update the database
            $query = "UPDATE users SET bio=?, address=?, location=?, logo_img=? WHERE email=?";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("sssss", $bio, $address, $location, $logo_path, $email);
                if ($stmt->execute()) {
                    echo "<p>Company details updated successfully!</p>";
                } else {
                    $errors[] = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Database error: Unable to prepare statement. " . $conn->error;
            }
        } else {
            $errors[] = "Error uploading logo.";
        }
    } else {
        $errors[] = "Please fill in all fields!";
    }
}

// Close the database connection
$conn->close();
?>
