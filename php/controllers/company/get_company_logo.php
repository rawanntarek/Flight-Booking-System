<?php
session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    exit();
}

// Include the database configuration file
require_once '../../config/db_config.php';

// Function to adjust the image path
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

// Prepare SQL statement to fetch company logo
$query = "SELECT logo_path FROM companies WHERE user_id = ?";

if ($stmt = $conn->prepare($query)) {
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($logo_path);
    $stmt->fetch();

    // Sanitize and adjust the logo path
    if ($logo_path) {
        $logo_path = filter_var($logo_path, FILTER_SANITIZE_STRING);
        $adjusted_logo_path = adjust_image_path($logo_path);

        // Final logo URL relative to the public folder
        $logo_url = $adjusted_logo_path;
    } else {
        // Logo not found, use default logo
        $logo_url = '/uploads/logos/default_logo.png';
    }

    // Prepare the response
    $response = [
        'logo_url' => $logo_url,
    ];

    echo json_encode($response);
    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: Unable to prepare statement.']);
}

$conn->close();
?>
