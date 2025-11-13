<?php
// cart_view.php (مثال اسم الملف)
// تأكد أن الملفات المضمنة لا تحتوي على BOM أو إخراج نصي

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// افتح buffer لالتقاط أي إخراج غير مقصود من includes
ob_start();

// includes (تأكد أن connect.php يُعرّف $con كـ PDO)
include_once "../connect.php";
include_once "../functions.php";

// احصل على أي إخراج غير مقصود وسجّله بدلاً من طباعته
$extra = ob_get_clean();
if (!empty($extra)) {
    file_put_contents(__DIR__ . '/debug_extra_output.log', date('Y-m-d H:i:s') . " - EXTRA OUTPUT:\n" . $extra . "\n\n", FILE_APPEND);
    // لا نطبعها لأن ذلك يكسر JSON
}

// اقرأ JSON إن وُجد (نقبل JSON أو form-urlencoded)
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
    if (ob_get_length()) ob_clean();
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// اقرأ user_id
$user_id = get_request('user_id', $input);

// تحقق من الحقل
if (!$user_id) {
    send_json(['status' => 'fail', 'message' => 'Missing required fields']);
}

// تأكد أن $con موجود وكائن PDO
if (!isset($con) || !($con instanceof PDO)) {
    // سجّل للّوج ثم أعد رسالة ملائمة
    file_put_contents(__DIR__ . '/debug_sql_errors.log', date('Y-m-d H:i:s') . " - DB connection not found or not PDO\n\n", FILE_APPEND);
    send_json(['status' => 'fail', 'message' => 'Server error']);
}

try {
    $stmt = $con->prepare("SELECT 
        cart_id, 
        cart_product_id as productId, 
        cart_product_title, 
        cart_product_image, 
        cart_price,
        cart_quantity,
        cart_attributes,
        cart_available_quantity,
        cart_platform 
        FROM `cart` WHERE `cart_user_id` = ?");
    $stmt->execute([$user_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // نرجع success دائما مع مصفوفة (قد تكون فارغة)
    send_json(['status' => 'success', 'data' => $data]);

} catch (PDOException $e) {
    // سجّل الخطأ ولا تعرضه للعميل في الإنتاج
    file_put_contents(__DIR__ . '/debug_sql_errors.log', date('Y-m-d H:i:s') . " - PDO ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);
    send_json(['status' => 'fail', 'message' => 'Server error']);
}
