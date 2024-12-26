<?php
// update_company_profile.php

session_start();

// Check if the user is logged in and is a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access. Please log in as a company.']);
    exit();
}

// Include the database configuration file
require_once '../../config/db_config.php';

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Determine if the request is AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Initialize response array
$response = [];

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check which field is being updated
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : null;
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : null;
    $bio = isset($_POST['bio']) ? sanitize_input($_POST['bio']) : null;
    $address = isset($_POST['address']) ? sanitize_input($_POST['address']) : null;

    // Determine which field to update
    $users_fields_to_update = [];
    $companies_fields_to_update = [];

    if ($name !== null) {
        // Update name
        if (empty($name)) {
            $error = "Name cannot be empty.";
            if ($isAjax) {
                http_response_code(400);
                $response['error'] = $error;
                echo json_encode($response);
            } else {
                $_SESSION['update_profile_error'] = $error;
                header("Location: ../../../html/company_profile.html");
            }
            exit();
        }
        $users_fields_to_update[] = "name = '$name'";
    }

    if ($email !== null) {
        // Update email
        if (empty($email)) {
            $error = "Email cannot be empty.";
            if ($isAjax) {
                http_response_code(400);
                $response['error'] = $error;
                echo json_encode($response);
            } else {
                $_SESSION['update_profile_error'] = $error;
                header("Location: ../../../html/company_profile.html");
            }
            exit();
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
            if ($isAjax) {
                http_response_code(400);
                $response['error'] = $error;
                echo json_encode($response);
            } else {
                $_SESSION['update_profile_error'] = $error;
                header("Location: ../../../html/company_profile.html");
            }
            exit();
        }

        $users_fields_to_update[] = "email = '$email'";
    }

    if ($bio !== null) {
        // Update bio
        if (empty($bio)) {
            $error = "Bio cannot be empty.";
            if ($isAjax) {
                http_response_code(400);
                $response['error'] = $error;
                echo json_encode($response);
            } else {
                $_SESSION['update_profile_error'] = $error;
                header("Location: ../../../html/company_profile.html");
            }
            exit();
        }
        $companies_fields_to_update[] = "bio = '$bio'";
    }

    if ($address !== null) {
        // Update address
        if (empty($address)) {
            $error = "Address cannot be empty.";
            if ($isAjax) {
                http_response_code(400);
                $response['error'] = $error;
                echo json_encode($response);
            } else {
                $_SESSION['update_profile_error'] = $error;
                header("Location: ../../../html/company_profile.html");
            }
            exit();
        }
        $companies_fields_to_update[] = "address = '$address'";
    }

    if (empty($users_fields_to_update) && empty($companies_fields_to_update)) {
        // No fields to update
        $error = "No data provided to update.";
        if ($isAjax) {
            http_response_code(400);
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['update_profile_error'] = $error;
            header("Location: ../../../html/company_profile.html");
        }
        exit();
    }

    // Construct the query for the users table if needed
    if (!empty($users_fields_to_update)) {
        $query = "UPDATE users SET " . implode(", ", $users_fields_to_update) . " WHERE user_id = " . $_SESSION['user_id'];
        
        if ($conn->query($query) === FALSE) {
            $error = "Failed to update profile in users table: " . $conn->error;
            $conn->close();
            if ($isAjax) {
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => $error]);
            } else {
                $_SESSION['update_profile_error'] = $error;
                header("Location: ../../../html/company_profile.html");
            }
            exit();
        }
    }

    // Construct the query for the companies table if needed
    if (!empty($companies_fields_to_update)) {
        $query = "UPDATE companies SET " . implode(", ", $companies_fields_to_update) . " WHERE user_id = " . $_SESSION['user_id'];
        
        if ($conn->query($query) === FALSE) {
            $error = "Failed to update profile in companies table: " . $conn->error;
            $conn->close();
            if ($isAjax) {
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => $error]);
            } else {
                $_SESSION['update_profile_error'] = $error;
                header("Location: ../../../html/company_profile.html");
            }
            exit();
        }
    }

    // If update is successful
    $success = "Profile updated successfully.";
    $conn->close();
    if ($isAjax) {
        echo json_encode(['success' => $success]);
    } else {
        $_SESSION['update_profile_success'] = $success;
        header("Location: ../../../html/company_profile.html");
    }
    exit();
} else {
    // Invalid request method
    $error = "Invalid request method.";
    if ($isAjax) {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => $error]);
    } else {
        $_SESSION['update_profile_error'] = $error;
        header("Location: ../../../html/company_profile.html");
    }
    exit();
}
?>

