<?php
// get_conversation.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../config/db_config.php';
$company_id = $_SESSION['user_id'];

// passenger_id in GET
if (!isset($_GET['passenger_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing passenger_id.']);
    exit();
}
$passenger_id = (int) $_GET['passenger_id'];

// Fetch all messages in BOTH directions:
$sql = "
    SELECT 
       m.message_id,
       m.sender_id,
       sender.name AS sender_name,
       m.receiver_id,
       receiver.name AS receiver_name,
       m.message_content,
       m.timestamp,
       m.status
    FROM messages m
    JOIN users sender   ON m.sender_id = sender.user_id
    JOIN users receiver ON m.receiver_id = receiver.user_id
    WHERE 
       (m.sender_id = ? AND m.receiver_id = ?)
       OR
       (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.timestamp ASC
";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("iiii", $company_id, $passenger_id, $passenger_id, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    echo json_encode(['messages' => $messages]);
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
