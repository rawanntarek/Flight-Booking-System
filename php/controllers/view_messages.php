<?php
// view_messages.php

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../html/login.html");
    exit();
}

// Include the database configuration file
require_once '../config/db_config.php';

// Retrieve the user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch messages where the user is either the sender or receiver
$stmt = $conn->prepare("
    SELECT 
        m.message_id,
        m.sender_id,
        m.receiver_id,
        m.message_content,
        m.timestamp,
        m.status,
        s.name AS sender_name,
        r.name AS receiver_name
    FROM 
        messages m
    JOIN 
        users s ON m.sender_id = s.user_id
    JOIN 
        users r ON m.receiver_id = r.user_id
    WHERE 
        m.sender_id = ? OR m.receiver_id = ?
    ORDER BY 
        m.timestamp DESC
");
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    $stmt->close();
} else {
    // Handle error
    die("Database error: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Messages</title>
    <link rel="stylesheet" href="../../css/Registration.css">
    <style>
        .container {
            width: 80%;
            margin: auto;
            padding-top: 30px;
        }
        .messages {
            margin-top: 20px;
        }
        .message {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .message.sent {
            background-color: #e6f7ff;
            text-align: right;
        }
        .message.received {
            background-color: #f9f9f9;
            text-align: left;
        }
        .message .meta {
            font-size: 0.9em;
            color: #555;
        }
        .message .content {
            margin-top: 5px;
        }
        .back-link {
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
            color: #2196F3;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Your Messages</h2>

        <div class="messages">
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $msg): ?>
                    <?php
                        // Determine message type
                        $is_sent = $msg['sender_id'] == $user_id;
                        $message_class = $is_sent ? 'sent' : 'received';
                        $sender = $is_sent ? 'You' : htmlspecialchars($msg['sender_name']);
                        $receiver = $is_sent ? htmlspecialchars($msg['receiver_name']) : 'You';
                    ?>
                    <div class="message <?php echo $message_class; ?>">
                        <div class="meta">
                            <?php echo $is_sent ? "To: " . $receiver : "From: " . $sender; ?> | 
                            <?php echo htmlspecialchars($msg['timestamp']); ?>
                        </div>
                        <div class="content">
                            <?php echo nl2br(htmlspecialchars($msg['message_content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No messages found.</p>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
