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
$from = $to = "";
$flights = [];
$errors = [];

// Enable detailed error reporting (for development only)
// Comment out or remove these lines in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the form is submitted via GET
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Retrieve and sanitize form inputs
    $from = sanitize_input($_GET['from'] ?? '');
    $to = sanitize_input($_GET['to'] ?? '');

    // No required fields, so no validation needed here

    // Build dynamic SQL query based on inputs
    $searchSql = "
        SELECT flight_id, name, fees, start_time, end_time
        FROM flights
        WHERE completed = 0
    ";

    $conditions = [];
    $params = [];
    $types = "";

    if (!empty($from)) {
        $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(itinerary, '$.from')) LIKE ?";
        $params[] = "%" . $from . "%";
        $types .= "s";
    }

    if (!empty($to)) {
        $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(itinerary, '$.to')) LIKE ?";
        $params[] = "%" . $to . "%";
        $types .= "s";
    }

    if (!empty($conditions)) {
        $searchSql .= " AND " . implode(" AND ", $conditions);
    }

    $searchSql .= " ORDER BY start_time ASC";

    if ($stmt = $conn->prepare($searchSql)) {
        if (!empty($conditions)) {
            $stmt->bind_param($types, ...$params);
        }
        if ($stmt->execute()) {
            $result = $stmt->get_result();

            // Fetch all matching flights
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $flights[] = $row;
                }
            } else {
                $errors[] = "No flights found matching your criteria.";
            }
        } else {
            $errors[] = "Database error: Execution failed. " . $stmt->error;
        }

        $stmt->close();
    } else {
        $errors[] = "Database error: Unable to prepare statement. " . $conn->error;
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
    <link rel="stylesheet" href="../../css/Registration.css"> <!-- Reusing Registration.css for styling -->
    <style>
        .container {
            width: 80%;
            margin: auto;
            padding-top: 30px;
        }
        .errors {
            color: red;
        }
        .success {
            color: green;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        tr:hover {background-color: #f5f5f5;}
        th {
            background-color: #4CAF50;
            color: white;
        }
        a.button {
            padding: 8px 12px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        a.button:hover {
            background-color: #0b7dda;
        }
        .back-link {
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
            color: #2196F3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Search Results</h2>

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

        <!-- Display flights if any -->
        <?php if (!empty($flights)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Flight ID</th>
                        <th>Name</th>
                        <th>Fees ($)</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flights as $flight): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($flight['flight_id']); ?></td>
                            <td><?php echo htmlspecialchars($flight['name']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($flight['fees'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($flight['start_time']); ?></td>
                            <td><?php echo htmlspecialchars($flight['end_time']); ?></td>
                            <td>
                                <a class="button" href="flight_info.php?flight_id=<?php echo urlencode($flight['flight_id']); ?>">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Back to Dashboard Link -->
        <a href="../../html/dashboard.html" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
