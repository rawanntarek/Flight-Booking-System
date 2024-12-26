<?php
// Start the session
session_start();

// Include the database configuration file
require_once '../../config/db_config.php';

// Check if the user is logged in and is a passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Passenger') {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle file upload for photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];
        $fileType = $_FILES['photo']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed file extensions
        $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg'];
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory where the file will be stored
            $uploadFileDir = '../../uploads/passengers/photos/';
            // Create the directory if it doesn't exist
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            // Generate a unique name for the file
            $newFileName = $user_id . '_photo.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // File is successfully uploaded
                $photo_path = $dest_path;
            } else {
                die("There was an error moving the uploaded photo.");
            }
        } else {
            die("Photo upload failed. Allowed file types: " . implode(',', $allowedfileExtensions));
        }
    } else {
        die("Error uploading the photo.");
    }

    // Handle file upload for passport
    if (isset($_FILES['passport']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['passport']['tmp_name'];
        $fileName = $_FILES['passport']['name'];
        $fileSize = $_FILES['passport']['size'];
        $fileType = $_FILES['passport']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed file extensions
        $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg', 'pdf'];
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory where the file will be stored
            $uploadFileDir = '../../uploads/passengers/passports/';
            // Create the directory if it doesn't exist
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            // Generate a unique name for the file
            $newFileName = $user_id . '_passport.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // File is successfully uploaded
                $passport_path = $dest_path;
            } else {
                die("There was an error moving the uploaded passport.");
            }
        } else {
            die("Passport upload failed. Allowed file types: " . implode(',', $allowedfileExtensions));
        }
    } else {
        die("Error uploading the passport.");
    }

    // Insert the passenger information into the database
    $insert_query = "INSERT INTO passengers (user_id, photo_path, passport_path) VALUES (?, ?, ?)";
    
    if ($stmt = $conn->prepare($insert_query)) {
        $stmt->bind_param("iss", $user_id, $photo_path, $passport_path);
        
        if ($stmt->execute()) {
            // Passenger information successfully inserted
            $stmt->close();
            $conn->close();

            // Redirect to a success page or dashboard
            header("Location: ../../../html/Login.html?registration=complete");
            exit();
        } else {
            // Insertion failed
            $stmt->close();
            $conn->close();
            die("Failed to save passenger information. Please try again.");
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
