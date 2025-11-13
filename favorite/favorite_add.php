<?php
header('Content-Type: application/json; charset=utf-8');
// أثناء التطوير:
ini_set('display_errors', 0);
error_reporting(E_ALL);

// includes — تأكد أن هذه الملفات لا تطبع أي شيء ولا تحتوي BOM
include_once "../connect.php";
include_once "../functions.php";

function send_json($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = filterrequest("user_id");
$product_id = filterrequest("product_id");
$product_title = filterrequest("product_title");
$product_image = filterrequest("product_image");
$product_price = filterrequest("product_price");
$favorite_platform = filterrequest("favorite_platform");
$goods_sn = filterrequest("goods_sn");
$category_id = filterrequest("category_id");

if (!$user_id || !$product_id || !$product_title || !$product_image || !$product_price || !$favorite_platform) {
    send_json(["status" => "fail", "message" => "Missing required fields"]);
}

try {
    $checkStmt = $con->prepare("SELECT COUNT(*) as cnt FROM `favorites` WHERE `favorite_user_id` = ? AND `favorite_product_id` = ?");
    $checkStmt->execute([$user_id, $product_id]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $count = intval($row['cnt'] ?? 0);

    if ($count > 0) {
        send_json(["status" => "fail", "message" => "Product already in favorites"]);
    }

    $stmt = $con->prepare("INSERT INTO `favorites` (`favorite_user_id`, `favorite_product_id`, `product_title`, `product_image`, `product_price`, `favorite_platform`, `goods_sn`, `category_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $product_id, $product_title, $product_image, $product_price, $favorite_platform, $goods_sn, $category_id]);

    if ($stmt->rowCount() > 0) {
        send_json(["status" => "success"]);
    } else {
        send_json(["status" => "fail", "message" => "Failed to add product"]);
    }
} catch (PDOException $e) {
    // تسجيل الخطأ في لوج ولا ترسله للمستخدم في الإنتاج
    // file_put_contents('/path/to/log.txt', $e->getMessage(), FILE_APPEND);
    send_json(["status" => "fail", "message" => "Server error"]);
}
