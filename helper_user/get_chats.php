<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../connect.php";
include_once "../functions.php";

$user_id = filterrequest("user_id");

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
    exit;
}

try {
    // نجيب كل الشاتات اللي تخص المستخدم
    $sql = "SELECT 
                c.id AS chat_id,
                c.platform,
                c.reference_id,
                (SELECT message FROM messages m WHERE m.chat_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message,
                (SELECT created_at FROM messages m WHERE m.chat_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message_time,
                (SELECT sender_type FROM messages m WHERE m.chat_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_sender,
                (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id AND m.sender_type = 'admin' AND m.is_read = 0) AS unread_count
            FROM chats c
            WHERE c.user_id = ?
            ORDER BY last_message_time DESC";

    $stmt = $con->prepare($sql);
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'chats' => $rows]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
