<?php
// secure_cart_check.php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// اجعل الـ includes لا يطبعوا شيء (نلتقط أي إخراج غير مقصود)
ob_start();
include_once "../connect.php";
include_once "../functions.php";
$extra = ob_get_clean();
if (!empty($extra)) {
    file_put_contents(__DIR__ . '/debug_extra_output.log', date('c') . " - EXTRA OUTPUT:\n" . $extra . "\n\n", FILE_APPEND);
    // لا نطبع الـ $extra حتى لا نكسر JSON
}

// اقرأ خام الـ request (نقبل JSON أو form data)
$raw = file_get_contents('php://input');
$jsonInput = null;
if ($raw !== false && $raw !== '') {
    $jsonInput = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $jsonInput = null; // لو مش JSON صالح نتجاهل
    }
}

// دالة مساعدة: ترجع من JSON أولًا ثم من filterrequest() ثم من $_POST/$_REQUEST
function get_input($key, $jsonInput) {
    if (is_array($jsonInput) && array_key_exists($key, $jsonInput)) return $jsonInput[$key];
    if (function_exists('filterrequest')) {
        $v = filterrequest($key);
        if ($v !== null && $v !== '') return $v;
    }
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_REQUEST[$key])) return $_REQUEST[$key];
    return null;
}

// اقرأ الحقول المطلوبة
$user_id    = get_input('user_id', $jsonInput);
$product_id = get_input('product_id', $jsonInput);
$attributes_raw = get_input('attributes', $jsonInput);

// تعامل مع attributes: قد تكون JSON-string أو مصفوفة
$attributes = null;
if (is_array($attributes_raw)) {
    $attributes = $attributes_raw;
} elseif (is_string($attributes_raw) && $attributes_raw !== '') {
    $decoded = json_decode($attributes_raw, true);
    if (json_last_error() === JSON_ERROR_NONE) $attributes = $decoded;
    else $attributes = $attributes_raw; // نخزنها كنص لو مو JSON
}

// تحقق من الحقول المطلوبة
if (empty($user_id) || empty($product_id) || ($attributes === null || $attributes === '')) {
    // لا نطبع شيء آخر — فقط JSON
    echo json_encode(["status" => "fail", "message" => "Missing required fields"], JSON_UNESCAPED_UNICODE);
    exit;
}

// تأكد من وجود اتصال DB صالح
if (!isset($con) || !($con instanceof PDO)) {
    file_put_contents(__DIR__ . '/debug_sql_errors.log', date('c') . " - DB connection missing or invalid\n", FILE_APPEND);
    echo json_encode(["status" => "fail", "message" => "Server error"], JSON_UNESCAPED_UNICODE);
    exit;
}

// إذا attributes مصفوفة، خزّنها كسلسلة JSON للمقارنة في WHERE
$attributes_to_check = is_array($attributes) ? json_encode($attributes, JSON_UNESCAPED_UNICODE) : $attributes;

try {
    $stmt = $con->prepare("
        SELECT 
            (SELECT `cart_quantity` 
             FROM `cart` 
             WHERE `cart_user_id` = ? 
             AND `cart_product_id` = ? 
             AND `cart_attributes` = ? 
             LIMIT 1) AS cart_quantity, 
        EXISTS(
             SELECT 1 
             FROM `favorites` 
             WHERE `favorite_user_id` = ? 
             AND `favorite_product_id` = ?) AS in_favorite
    ");

    $stmt->execute([$user_id, $product_id, $attributes_to_check, $user_id, $product_id]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $quantity    = (int)($result['cart_quantity'] ?? 0);
    $in_favorite = (bool)($result['in_favorite'] ?? false);

    echo json_encode([
        "status" => "success",
        "cart_quantity" => $quantity,
        "in_favorite" => $in_favorite
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // مؤقتا فقط أثناء التصحيح: عرض رسالة الخطأ الحقيقية
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "status" => "fail",
        "message" => "Server error",
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
