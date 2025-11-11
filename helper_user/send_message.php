<?php
header('Content-Type: application/json; charset=utf-8');
include_once "../connect.php"; // تأكد المسار صحيح
include_once "../notifcation/sendnotfication.php";
try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) $data = $_POST;

    // مدخلات
    $chat_id    = isset($data['chat_id']) ? (int)$data['chat_id'] : null;
    $reply_to   = isset($data['reply_to']) ? (int)$data['reply_to'] : null;
    $user_id    = isset($data['user_id']) ? (int)$data['user_id'] : null;
    $admin_id   = isset($data['admin_id']) ? (int)$data['admin_id'] : null;
    $platform   = $data['platform'] ?? 'home';
    $sender_type= $data['sender_type'] ?? null; // 'user' or 'admin'
    $sender_name= $data['sender_name'] ?? null;
    $message    = trim($data['message'] ?? '');
    $device_tokens = $data['device_tokens'] ?? null;
    $reference_id  = $data['reference_id'] ?? null;
    $image_link  = $data['image_link'] ?? null;

    if (!$sender_type || $message === '') {
        echo json_encode(["status"=>"error","message"=>"sender_type and message are required"]);
        exit;
    }

    // 1) إذا الرسالة رد على رسالة قديمة (reply_to) و chat_id مش موجود: استخرج chat_id من الرسالة الأصلية
    if ($reply_to && !$chat_id) {
        $q = $con->prepare("SELECT chat_id, user_id FROM messages WHERE id = ? LIMIT 1");
        $q->execute([$reply_to]);
        $orig = $q->fetch(PDO::FETCH_ASSOC);
        if ($orig) {
            $chat_id = (int)$orig['chat_id'];
            if (!$user_id && !empty($orig['user_id'])) {
                $user_id = (int)$orig['user_id'];
            }
        }
    }

    // 2) لو مفيش chat_id — أنشئ chat جديد (بمعطيات المرسل)
    if (!$chat_id) {
        $insChat = $con->prepare("INSERT INTO chats (user_id, admin_id, platform, reference_id, last_message, last_sender_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        // لو المرسل admin و ما فيش user_id ممكن تترك user_id NULL
        $insChat->execute([
            $user_id ?: null,
            $admin_id ?: null,
            $platform,
            $reference_id ?: null,
            $message,
            $sender_type
        ]);
        $chat_id = (int)$con->lastInsertId();
    } else {
        // لو chat_id موجود بنحدّث last_message و updated_at لاحقًا بعد إدخال الرسالة
    }

    // 3) إدخال الرسالة
    $stmt = $con->prepare("INSERT INTO messages
        (chat_id, user_id, admin_id, platform, sender_type, sender_name, message, is_read, is_replied, reply_to, reference_id, device_tokens,image_link, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?,?, ?, NOW())");
    $stmt->execute([
        $chat_id,
        $user_id ?: null,
        $admin_id ?: null,
        $platform,
        $sender_type,
        $sender_name ?: null,
        $message,
        $reply_to ?: null,
        $reference_id ?: null,
        $device_tokens ?: null,
        $image_link 
    ]);
    $msg_id = (int)$con->lastInsertId();

    // 4) إذا المرسل admin & reply_to موجود -> حدث الرسالة الأصلية كـ replied
    if ($sender_type === 'admin' && $reply_to) {
        $u = $con->prepare("UPDATE messages SET is_replied = 1, replied_at = NOW() WHERE id = ?");
        $u->execute([$reply_to]);
    }

    // 5) حدّث chats: last_message, last_sender_type, updated_at
    $up = $con->prepare("UPDATE chats SET last_message = ?, last_sender_type = ?, updated_at = NOW() WHERE id = ?");
    $up->execute([$message, $sender_type, $chat_id]);

    // 6) جلب السطر المدخل للرد
    $fetch = $con->prepare("SELECT * FROM messages WHERE id = ?");
    $fetch->execute([$msg_id]);
    $row = $fetch->fetch(PDO::FETCH_ASSOC);
    $notif;
    if($sender_type=='user'){
      $notif =  sendFcmV1("adminsaltuk","اشعار من المستخدم","$message","","",true);

    }else{
     $notif =   sendFcmV1($device_tokens,"اشعار من الدعم","$message","","",false);

    }

    echo json_encode([
        "status" => "success",
        "chat_id" => $chat_id,
        "message_id" => $msg_id,
        // "notifcation" => $notif,
        "data" => $row
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}
