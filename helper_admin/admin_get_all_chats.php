<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../connect.php";

$date = $_GET['date'] ?? null;        // REQUIRED in format YYYY-MM-DD
$platform = $_GET['platform'] ?? null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
$offset = ($page - 1) * $limit;

if (!$date) {
    echo json_encode(['status'=>'error','message'=>'date is required in format YYYY-MM-DD']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['status'=>'error','message'=>'date must be in YYYY-MM-DD format']);
    exit;
}

try {
    // Build base WHERE using last message date (DATE of the last message's created_at)
    $whereParts = ["DATE(c.updated_at) = ?"];
    $params = [$date];

    if ($platform) {
        $whereParts[] = "c.platform = ?";
        $params[] = $platform;
    }

    $whereSql = implode(" AND ", $whereParts);

    // NOTE: LIMIT and OFFSET are injected as integers (safe because we cast them)
    $sql = "
      SELECT 
        c.id AS chat_id,
        c.user_id,
        c.platform,
        c.reference_id,
        (SELECT message FROM messages m WHERE m.chat_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message,
        (SELECT created_at FROM messages m WHERE m.chat_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message_time,
        (SELECT sender_type FROM messages m WHERE m.chat_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_sender,
        (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id AND m.sender_type = 'user' AND m.is_read = 0) AS unread_count,
        c.created_at,
        c.updated_at
      FROM chats c
      WHERE {$whereSql}
      ORDER BY last_message_time DESC, c.updated_at DESC
      LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success','chats'=>$rows]);

} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
