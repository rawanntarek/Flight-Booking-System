<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: ../../html/login.html");
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Function to sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Initialize variables
$name = $email = $telephone = $user_type = "";
$errors = [];
$success = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $telephone = sanitize_input($_POST['telephone'] ?? '');
    $user_type = sanitize_input($_POST['user_type'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    if (empty($user_type) || !in_array($user_type, ['Company', 'Passenger'])) {
        $errors[] = "Invalid user type selected.";
    }

    // If no errors, proceed to update the user information
    if (empty($errors)) {
        // Check if the new email is already taken by another user
        $checkEmailSql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        if ($stmt = $conn->prepare($checkEmailSql)) {
            $stmt->bind_param("si", $email, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Email is already in use by another account.";
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: Unable to prepare statement.";
        }

        // If still no errors, proceed to update
        if (empty($errors)) {
            // If password is being updated, hash it
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $updateSql = "UPDATE users SET name = ?, email = ?, password = ?, telephone = ?, user_type = ? WHERE user_id = ?";
            } else {
                $updateSql = "UPDATE users SET name = ?, email = ?, telephone = ?, user_type = ? WHERE user_id = ?";
            }

            if ($stmt = $conn->prepare($updateSql)) {
                if (!empty($password)) {
                    $stmt->bind_param("sssssi", $name, $email, $hashed_password, $telephone, $user_type, $_SESSION['user_id']);
                } else {
                    $stmt->bind_param("ssssi", $name, $email, $telephone, $user_type, $_SESSION['user_id']);
                }

                if ($stmt->execute()) {
                    $success = "Profile updated successfully.";

                    // Update session variables if email or name changed
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $_SESSION['user_type'] = $user_type;
                } else {
                    $errors[] = "Failed to update profile. Please try again.";
                }

                $stmt->close();
            } else {
                $errors[] = "Database error: Unable to prepare statement.";
            }
        }
    }
} else {
    // If GET request, fetch current user data to pre-fill the form
    $fetchSql = "SELECT name, email, telephone, user_type FROM users WHERE user_id = ?";
    if ($stmt = $conn->prepare($fetchSql)) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($name, $email, $telephone, $user_type);
        $stmt->fetch();
        $stmt->close();
    } else {
        $errors[] = "Database error: Unable to prepare statement.";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../../css/Registration.css"> <!-- Reusing Registration.css for styling -->
    <style>
        /* Additional styling can be added here */
        .container {
            width: 50%;
            margin: auto;
            padding-top: 50px;
        }
        .message {
            color: green;
        }
        .errors {
            color: red;
        }
        label {
            display: inline-block;
            width: 150px;
            margin-top: 10px;
        }
        input, select {
            width: 60%;
            padding: 8px;
            margin-top: 10px;
        }
        input[type="submit"] {
            width: auto;
            padding: 10px 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Profile</h2>

        <!-- Display success message -->
        <?php if (!empty($success)): ?>
            <p class="message"><?php echo $success; ?></p>
        <?php endif; ?>

        <!-- Display error messages -->
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required><br>

            <label for="telephone">Telephone:</label>
            <input type="text" name="telephone" id="telephone" value="<?php echo htmlspecialchars($telephone); ?>"><br>

            <label for="user_type">User Type:</label>
            <select name="user_type" id="user_type" required>
                <option value="">Select</option>
                <option value="Company" <?php if ($user_type == 'Company') echo 'selected'; ?>>Company</option>
                <option value="Passenger" <?php if ($user_type == 'Passenger') echo 'selected'; ?>>Passenger</option>
            </select><br>

            <label for="password">New Password:</label>
            <input type="password" name="password" id="password"><br>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password"><br>

            <input type="submit" value="Update Profile">
        </form>

        <br>
        <a href="../../html/dashboard.html">Back to Dashboard</a>
    </div>
</body>
</html>
