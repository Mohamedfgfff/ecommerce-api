<?php
// اجعل الاستجابة JSON
header('Content-Type: application/json; charset=utf-8');

// لا تطبع أخطاء إلى المخرجات العادية (يمكن تفعيلها أثناء التطوير مؤقتًا)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// افتح buffer لالتقاط أي إخراج غير مقصود من includes
ob_start();

// includes — تأكد أن هذه الملفات لا تحتوي على إخراج/echo أو BOM
include_once "../connect.php";
include_once "../functions.php";

// التقط أي إخراج حصل أثناء الـ includes
$extraOutput = ob_get_clean(); // هذا يحوي أي HTML أو نص طُبع بالخطأ

// لو في إخراج زائد، سجّله ولا ترسله للمستخدم في الإنتاج
if (!empty($extraOutput)) {
    // سجل الإخراج لملف لوج (تأكد أن مجلد الكتابة مسموح)
    file_put_contents(__DIR__ . '/debug_extra_output.log', date('Y-m-d H:i:s') . " - EXTRA OUTPUT:\n" . $extraOutput . "\n\n", FILE_APPEND);
    // لا نطبع $extraOutput لأن ذلك يكسر JSON
}

// اقرأ JSON إن وُجد
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

// مساعدة لقراءة المفتاح من JSON أو $_POST/$_REQUEST
function get_request($key, $input) {
    if (is_array($input) && array_key_exists($key, $input)) {
        return $input[$key];
    }
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_REQUEST[$key])) return $_REQUEST[$key];
    return null;
}

function send_json($arr) {
    // تأكد أن لا يوجد أي خروج آخر؛ تنظيف الbuffer إن وُجد
    if (ob_get_length()) ob_clean();
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = get_request("user_id", $input);
$product_id = get_request("product_id", $input);
$product_title = get_request("product_title", $input);
$product_image = get_request("product_image", $input);
$product_price = get_request("product_price", $input);
$favorite_platform = get_request("favorite_platform", $input);
$goods_sn = get_request("goods_sn", $input);
$category_id = get_request("category_id", $input);

if (!$user_id || !$product_id || !$product_title || !$product_image || !$product_price || !$favorite_platform) {
    send_json(["status" => "fail", "message" => "Missing required fields"]);
}

// بقية المنطق: تحقق من الوجود ثم الإدخال
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
    // سجّل الخطأ بدل طباعته للمستخدم
    file_put_contents(__DIR__ . '/debug_sql_errors.log', date('Y-m-d H:i:s') . " - PDO ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);
    send_json(["status" => "fail", "message" => "Server error"]);
}
