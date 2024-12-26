<?php
// php/controllers/user/dashboard.php

// Start the session
session_start();

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, return an error response
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

// Include the database configuration file
require_once '../../config/db_config.php';

// Initialize variables
$user_id = $_SESSION['user_id'];
$user_name = '';
$photo_path = '';

// Fetch user name and user_type from users table
$userSql = "SELECT name, user_type FROM users WHERE user_id = ?";
if ($stmt = $conn->prepare($userSql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $user_type);
    if ($stmt->fetch()) {
        $user_name = $name;
    } else {
        // User not found
        $user_name = "Unknown User";
        $user_type = '';
    }
    $stmt->close();
} else {
    // Handle error
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: Unable to prepare user query']);
    exit();
}

// Function to adjust image paths
function adjust_image_path($db_path) {
    // Check if the path starts with '../../uploads/'
    $search = '../../uploads/';
    $replace = '../php/uploads/';

    if (strpos($db_path, $search) === 0) {
        return str_replace($search, $replace, $db_path);
    } else {
        // If the path does not start with '../../uploads/', return it as is
        return $db_path;
    }
}

// Fetch photo_path or logo_path based on user_type
if ($user_type === 'Passenger') {
    $passengerSql = "SELECT photo_path FROM passengers WHERE user_id = ?";
    if ($stmt = $conn->prepare($passengerSql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($photo);
        if ($stmt->fetch()) {
            // Sanitize the photo path to prevent directory traversal
            $photo = filter_var($photo, FILTER_SANITIZE_STRING);
            // Adjust the path
            $adjusted_photo_path = adjust_image_path($photo);
            // Final photo path relative to dashboard.html
            $photo_path = $adjusted_photo_path;
        } else {
            // Photo not found, use default avatar
            $photo_path = '../php/uploads/images/default_avatar.png';
        }
        $stmt->close();
    } else {
        // Handle error, use default avatar
        $photo_path = '../php/uploads/images/default_avatar.png';
    }
} elseif ($user_type === 'Company') {
    $companySql = "SELECT logo_path FROM companies WHERE user_id = ?";
    if ($stmt = $conn->prepare($companySql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($logo);
        if ($stmt->fetch()) {
            // Sanitize the logo path to prevent directory traversal
            $logo = filter_var($logo, FILTER_SANITIZE_STRING);
            // Adjust the path
            $adjusted_logo_path = adjust_image_path($logo);
            // Final logo path relative to dashboard.html
            $photo_path = $adjusted_logo_path;
        } else {
            // Logo not found, use default logo
            $photo_path = '../php/uploads/images/default_logo.png';
        }
        $stmt->close();
    } else {
        // Handle error, use default logo
        $photo_path = '../php/uploads/images/default_logo.png';
    }
} else {
    // Unknown user_type, use default logo
    $photo_path = '../php/uploads/images/default_logo.png';
}

// Close the database connection
$conn->close();

// Return the data as JSON
echo json_encode([
    'user_name' => htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'),
    'photo_path' => htmlspecialchars($photo_path, ENT_QUOTES, 'UTF-8')
]);
?>
