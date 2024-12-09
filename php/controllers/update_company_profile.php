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
require_once '../config/db_config.php';

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

    // Determine which field to update
    $fields_to_update = [];
    $params = [];
    $types = '';

    if ($name !== null && $email !== null) {
        // Both fields are being updated; reject the request
        $error = "Please update either Name or Email, not both at the same time.";
        if ($isAjax) {
            http_response_code(400); // Bad Request
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['update_profile_error'] = $error;
            header("Location: ../../html/company_profile.html");
        }
        exit();
    }

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
                header("Location: ../../html/company_profile.html");
            }
            exit();
        }
        $fields_to_update[] = "name = ?";
        $params[] = $name;
        $types .= "s";
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
                header("Location: ../../html/company_profile.html");
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
                header("Location: ../../html/company_profile.html");
            }
            exit();
        }

        $fields_to_update[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }

    if (empty($fields_to_update)) {
        // No fields to update
        $error = "No data provided to update.";
        if ($isAjax) {
            http_response_code(400);
            $response['error'] = $error;
            echo json_encode($response);
        } else {
            $_SESSION['update_profile_error'] = $error;
            header("Location: ../../html/company_profile.html");
        }
        exit();
    }

    // Prepare the SQL statement
    $query = "UPDATE users SET " . implode(", ", $fields_to_update) . " WHERE user_id = ?";
    $types .= "i"; // For user_id
    $params[] = $_SESSION['user_id'];

    if ($stmt = $conn->prepare($query)) {
        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            // Update session variables
            if ($name !== null) {
                $_SESSION['name'] = $name;
            }
            if ($email !== null) {
                $_SESSION['email'] = $email;
            }

            $success = "Profile updated successfully.";
            $stmt->close();
            $conn->close();
            if ($isAjax) {
                echo json_encode(['success' => $success]);
            } else {
                $_SESSION['update_profile_success'] = $success;
                header("Location: ../../html/company_profile.html");
            }
            exit();
        } else {
            $error = "Failed to update profile: " . $stmt->error;
            $stmt->close();
            $conn->close();
            if ($isAjax) {
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => $error]);
            } else {
                $_SESSION['update_profile_error'] = $error;
                header("Location: ../../html/company_profile.html");
            }
            exit();
        }
    } else {
        $error = "Database error: Unable to prepare statement.";
        $conn->close();
        if ($isAjax) {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => $error]);
        } else {
            $_SESSION['update_profile_error'] = $error;
            header("Location: ../../html/company_profile.html");
        }
        exit();
    }
} else {
    // Invalid request method
    $error = "Invalid request method.";
    if ($isAjax) {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => $error]);
    } else {
        $_SESSION['update_profile_error'] = $error;
        header("Location: ../../html/company_profile.html");
    }
    exit();
}
?>
