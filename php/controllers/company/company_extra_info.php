<?php
// Start the session
session_start();

// Include the database configuration file
require_once '../../config/db_config.php';

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $bio = sanitize_input($_POST['bio']);
    $address = sanitize_input($_POST['address']);
    $location = sanitize_input($_POST['location']);

    // Handle file upload for logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['logo']['tmp_name'];
        $fileName = $_FILES['logo']['name'];
        $fileSize = $_FILES['logo']['size'];
        $fileType = $_FILES['logo']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed file extensions
        $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg'];
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory where the file will be stored
            $uploadFileDir = '../../uploads/logos/';
            // Create the directory if it doesn't exist
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            // Generate a unique name for the file
            $newFileName = $user_id . '_logo.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // File is successfully uploaded
                $logo_path = $dest_path;
            } else {
                die("There was an error moving the uploaded file.");
            }
        } else {
            die("Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions));
        }
    } else {
        die("Error uploading the logo.");
    }

    // Insert the company information into the database
    $insert_query = "INSERT INTO companies (user_id, bio, address, location, logo_path) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($insert_query)) {
        $stmt->bind_param("issss", $user_id, $bio, $address, $location, $logo_path);
        
        if ($stmt->execute()) {
            // Company information successfully inserted
            $stmt->close();
            $conn->close();

            // Redirect to a success page or dashboard
            header("Location: ../../../html/Login.html?registration=complete");
            exit();
        } else {
            // Insertion failed
            $stmt->close();
            $conn->close();
            die("Failed to save company information. Please try again.");
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
