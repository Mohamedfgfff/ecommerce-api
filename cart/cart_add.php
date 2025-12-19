<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// includes — تأكد أن connect.php يُنشئ $con كـ PDO وأن functions.php لا يطبع أي شيء
include_once "../connect.php";
include_once "../functions.php";

// اقرأ الـ raw input (نقبل JSON)
$raw = file_get_contents('php://input');
if ($raw !== '' && $raw !== false) {
    // سجّل مؤقتاً أثناء التطوير إن احتجت
    // file_put_contents(__DIR__ . '/debug_raw_body.log', date('c') . " - RAW: " . $raw . "\n", FILE_APPEND);
    $jsonInput = json_decode($raw, true);
} else {
    $jsonInput = null;
}

// مساعدة: ترجع قيمة من JSON أولاً، ثم من $_POST/$_REQUEST عبر filterrequest()
function get_input($key, $jsonInput)
{
    // إذا JSON موجود وخانة موجودة
    if (is_array($jsonInput) && array_key_exists($key, $jsonInput)) {
        return $jsonInput[$key];
    }
    // حاول استخدام دالتك الحالية (filterrequest) كـ fallback
    if (function_exists('filterrequest')) {
        $v = filterrequest($key);
        if ($v !== null && $v !== '') return $v;
    }
    // أخيراً حاول $_POST/$_REQUEST مباشرة
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_REQUEST[$key])) return $_REQUEST[$key];
    return null;
}

// اقرأ الحقول
$user_id = get_input('user_id', $jsonInput);
$product_id = get_input('product_id', $jsonInput);
$product_title = get_input('product_title', $jsonInput);
$product_image = get_input('product_image', $jsonInput);
$product_price = get_input('product_price', $jsonInput);
$cart_quantity = get_input('quantity', $jsonInput);
$cart_attributes_raw = get_input('attributes', $jsonInput);
$cart_available_quantity = get_input('available_quantity', $jsonInput);
$platform = get_input('platform', $jsonInput);
$cart_tier_raw = get_input('cart_tier', $jsonInput);
$goods_sn = get_input("goods_sn", $jsonInput);
$category_id = get_input("category_id", $jsonInput);
$product_link = get_input("product_link", $jsonInput);

// تعامُل مع attributes: ممكن تكون JSON-string أو array
$cart_attributes = null;
if (is_array($cart_attributes_raw)) {
    $cart_attributes = $cart_attributes_raw;
} elseif (is_string($cart_attributes_raw) && $cart_attributes_raw !== '') {
    // إذا السلسلة تحتوي JSON مشفّرة
    $decoded = json_decode($cart_attributes_raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $cart_attributes = $decoded;
    } else {
        // خليه كـ string لو مش JSON
        $cart_attributes = $cart_attributes_raw;
    }
}

// نفس الشيء للـ tier (إن لزم)
$cart_tier = null;
if (is_array($cart_tier_raw)) {
    $cart_tier = $cart_tier_raw;
} elseif (is_string($cart_tier_raw) && $cart_tier_raw !== '') {
    $decoded = json_decode($cart_tier_raw, true);
    if (json_last_error() === JSON_ERROR_NONE) $cart_tier = $decoded;
    else $cart_tier = $cart_tier_raw;
}

// تحقق من الحقول المطلوبة
if (empty($user_id) || empty($product_id) || empty($cart_quantity) || ($cart_attributes === null || $cart_attributes === '')) {
    echo json_encode(["status" => "fail", "message" => "Missing required fields"]);
    exit;
}

// تأكد من اتصال DB
if (!isset($con) || !($con instanceof PDO)) {
    file_put_contents(__DIR__ . '/debug_sql_errors.log', date('c') . " - DB connection missing\n", FILE_APPEND);
    echo json_encode(["status" => "fail", "message" => "Server error"]);
    exit;
}

// إذا attributes مصفوفة، خزّنها كسلسلة JSON في العمود
$cart_attributes_to_store = is_array($cart_attributes) ? json_encode($cart_attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $cart_attributes;

// cart_tier كذلك (لو تريد تخزينها)
$cart_tier_to_store = is_array($cart_tier) ? json_encode($cart_tier, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $cart_tier;

try {
    // تحقق هل العنصر موجود بنفس user_id, product_id, attributes
    $checkStmt = $con->prepare("SELECT * FROM `cart` WHERE `cart_user_id` = ? AND `cart_product_id` = ? AND `cart_attributes` = ?");
    $checkStmt->execute([$user_id, $product_id, $cart_attributes_to_store]);
    $existing_item = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_item) {
        $cart_id = $existing_item['cart_id'];
        $updateStmt = $con->prepare("UPDATE `cart` SET 
            `cart_quantity` = ?,
            `cart_price` = ?,
            `cart_product_title` = ?,
            `cart_product_image` = ?,
            `cart_available_quantity` = ?
            WHERE `cart_id` = ?");
        $updateStmt->execute([$cart_quantity, $product_price, $product_title, $product_image, $cart_available_quantity, $cart_id]);
        if ($updateStmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "edit"]);
        } else {
            echo json_encode(["status" => "fail", "message" => "Failed to update quantity"]);
        }
    } else {
        $insertStmt = $con->prepare("INSERT INTO `cart`
            (`cart_user_id`, `cart_product_id`, `cart_product_title`, `cart_product_image`, `cart_price`, `cart_quantity`, `cart_attributes`, `cart_platform`, `cart_available_quantity`, `cart_tier`, `goods_sn`, `category_id`, `product_link`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$user_id, $product_id, $product_title, $product_image, $product_price, $cart_quantity, $cart_attributes_to_store, $platform, $cart_available_quantity, $cart_tier_to_store, $goods_sn, $category_id, $product_link]);
        if ($insertStmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "add"]);
        } else {
            echo json_encode(["status" => "fail", "message" => "Failed to add product"]);
        }
    }
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
