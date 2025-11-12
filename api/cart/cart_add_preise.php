<?php

include_once ".../connect.php";
include_once ".../functions.php"; // افترض أن هذا الملف يحتوي على filterrequest و jsonrequest

// --- استقبال البيانات ---
$user_id = filterrequest("user_id");
$product_id = filterrequest("product_id");
$cart_attributes = jsonrequest("attributes"); // استقبال الـ attributes كـ JSON

// <<-- استقبال الكمية المتاحة من Flutter -->>
$live_available_quantity = filterrequest("available_quantity"); 


// التحقق من الحقول الأساسية
if (!$user_id || !$product_id || !$cart_attributes || $live_available_quantity === null) {
    echo json_encode(array("status" => "fail", "message" => "Missing required fields"));
    exit();
}

// ابحث عن المنتج المحدد في السلة
$checkStmt = $con->prepare("SELECT * FROM `cart` WHERE `cart_user_id` = ? AND `cart_product_id` = ? AND `cart_attributes` = ?");
$checkStmt->execute(array($user_id, $product_id, $cart_attributes));
$existing_item = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existing_item) {
    // وجدنا المنتج، الآن لنتحقق من المخزون
        $cart_id = $existing_item['cart_id'];
    $current_quantity = $existing_item['cart_quantity'];

       // 1. تحديث الكمية المتاحة في قاعدة البيانات أولاً
    $updateStockStmt = $con->prepare("UPDATE `cart` SET `cart_available_quantity` = ? WHERE `cart_id` = ?");
    $updateStockStmt->execute(array($live_available_quantity, $cart_id));

    // ==========================================================
    // >> هنا شرط التحقق الجديد <<
    // ==========================================================
    // نقارن الكمية الحالية بالكمية المتاحة التي أرسلها التطبيق
    if ($current_quantity >= $live_available_quantity) {
        // إذا كانت الكمية الحالية تساوي أو تزيد عن المتاح، أرجع رسالة خطأ
        echo json_encode(array("status" => "fail", "message" => "full"));
        exit(); // أوقف التنفيذ
    }

    // إذا كان هناك مخزون متاح، قم بزيادة الكمية
    $new_quantity = $current_quantity + 1; 
    $cart_id = $existing_item['cart_id'];

    $updateStmt = $con->prepare("UPDATE `cart` SET 
        `cart_quantity` = ?
        WHERE `cart_id` = ?");
    
    $updateStmt->execute(array($new_quantity, $cart_id));
    
    $updateCount = $updateStmt->rowCount();

    if ($updateCount > 0) {
        echo json_encode(array("status" => "success", "message" => "edit"));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Failed to update quantity"));
    }

} else {
    // إذا لم يتم العثور على المنتج في السلة أصلاً
    echo json_encode(array("status" => "fail", "message" => "Product not found in cart"));
}

?>