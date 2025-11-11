<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../connect.php";
include_once "../functions.php";

$chat_id = filterrequest("chat_id");

if (!$chat_id) {
    echo json_encode(['status' => 'error', 'message' => 'chat_id is required']);
    exit;
}

try {
    $sql = "SELECT 
                id,
                chat_id,
                sender_type,
                sender_name,
                image_link,
                message,
                is_read,
                is_replied,
                reply_to,
                created_at
            FROM messages
            WHERE chat_id = ?
            ORDER BY created_at ASC";

    $stmt = $con->prepare($sql);
    $stmt->execute([$chat_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
