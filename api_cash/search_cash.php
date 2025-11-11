<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

include_once "../connect.php";
include_once "../functions.php";

// 🟢 القيم اللي جايه من الريكوست
$query = strtolower(trim(filterrequest("query"))); // نخليها lowercase عشان تكون موحدة
$platform = filterrequest("platform");
$data = $_POST["data"] ?? ''; // استقبلها كـ string صريح بدون filterrequest
$ttlHours = filterrequest("ttlHours") ?: 24; // عدد الساعات اللي الكاش يفضل صالح فيها

if (!$query || !$platform || !$data) {
    echo json_encode(array("status" => "fail", "message" => "Missing required fields"));
    exit();
}

// 🕒 نحسب وقت الانتهاء
$expire_at = date("Y-m-d H:i:s", strtotime("+$ttlHours hours"));

// 🔍 نتحقق هل فيه كاش موجود للكلمة والمنصة
$checkStmt = $con->prepare("SELECT * FROM `search_cash` WHERE `query` = ? AND `platform` = ?");
$checkStmt->execute(array($query, $platform));
$exists = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    // ✅ لو الكاش موجود بالفعل نحدثه (نخليه آخر بيانات)
    $updateStmt = $con->prepare("UPDATE `search_cash`
        SET `data` = ?, `created_at` = NOW(), `expire_at` = ?
        WHERE `id` = ?");
    $updateStmt->execute(array($data, $expire_at, $exists['id']));
    
    echo json_encode(array("status" => "updated"));
} else {
    // 🆕 لو مفيش كاش نضيفه
    $insertStmt = $con->prepare("INSERT INTO `search_cash`
        (`query`, `platform`, `data`, `created_at`, `expire_at`)
        VALUES (?, ?, ?, NOW(), ?)");
    $insertStmt->execute(array($query, $platform, $data, $expire_at));

    if ($insertStmt->rowCount() > 0) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Failed to insert cache"));
    }
}

?>
