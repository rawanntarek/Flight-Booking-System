<?php
// get_company_chat_users.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../config/db_config.php';
$company_id = $_SESSION['user_id'];

/*
   We'll find all unique user_ids (Passengers) who have
   either sent a message to this company OR received a message from this company.
   That means messages.sender_id = passenger_id or messages.receiver_id = passenger_id,
   but the passenger is the other side from the company in each conversation.
*/
$sql = "
    SELECT DISTINCT u.user_id, u.name
    FROM messages m
    JOIN users u ON (
        (m.sender_id = u.user_id AND m.receiver_id = ?) 
        OR
        (m.receiver_id = u.user_id AND m.sender_id = ?)
    )
    WHERE u.user_type = 'Passenger'
";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $company_id, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'user_id' => $row['user_id'],
            'name' => $row['name']
        ];
    }
    echo json_encode(['users' => $users]);
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
