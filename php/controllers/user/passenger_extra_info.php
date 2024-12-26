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
    $photo = $_FILES['photo']['name'] ?? null;
    $passport = $_FILES['passport']['name'] ?? null;

    if ($email && $photo && $passport) {
        // Upload the photo and passport
        $upload_dir = '../uploads/';
        $photo_path = $upload_dir . basename($photo);
        $passport_path = $upload_dir . basename($passport);
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path) && move_uploaded_file($_FILES['passport']['tmp_name'], $passport_path)) {
            // Update the database
            $query = "UPDATE users SET photo=?, passport_img=? WHERE email=?";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("sss", $photo_path, $passport_path, $email);
                if ($stmt->execute()) {
                    echo "<p>Passenger details updated successfully!</p>";
                } else {
                    $errors[] = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Database error: Unable to prepare statement. " . $conn->error;
            }
        } else {
            $errors[] = "Error uploading photo or passport.";
        }
    } else {
        $errors[] = "Please fill in all fields!";
    }
}

// Close the database connection
$conn->close();
?>
