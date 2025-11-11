<?php

include_once "../connect.php";
include_once "../functions.php";

$user_id = filterrequest("user_id");
$product_id = filterrequest("product_id");
$product_title = filterrequest("product_title");
$product_image = filterrequest("product_image");
$product_price = filterrequest("product_price");
$cart_quantity = filterrequest("quantity");
$cart_attributes = jsonrequest("attributes"); 
$cart_available_quantity = filterrequest("available_quantity");
$platform = filterrequest("platform");
$cart_tier = jsonrequest("cart_tier");


// 2. التحقق من أن جميع الحقول المطلوبة موجودة (تم تصحيح أسماء المتغيرات)
if (!$user_id || !$product_id || !$cart_quantity || !$cart_attributes) {
    echo json_encode(array("status" => "fail", "message" => "Missing required fields"));
    exit();
}

// 3. التحقق مما إذا كان المنتج موجودًا بالفعل (تم تصحيح اسم المتغير)
$checkStmt = $con->prepare("SELECT * FROM `cart` WHERE `cart_user_id` = ? AND `cart_product_id` = ? AND `cart_attributes` = ?");
$checkStmt->execute(array($user_id, $product_id, $cart_attributes));
$existing_item = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existing_item) {
    // 4. إذا كان موجودًا، قم بتحديث الكمية (تم تصحيح اسم المتغير)
    // $new_quantity = $existing_item['cart_quantity'] + $cart_quantity; 
    $cart_id = $existing_item['cart_id'];

    $updateStmt = $con->prepare("UPDATE `cart` SET 
        `cart_quantity` = ?,
        `cart_price` = ?,
        `cart_product_title` = ?,
        `cart_product_image` = ?,
        `cart_available_quantity` = ?
        WHERE `cart_id` = ?");
    
    // (تم تصحيح اسم المتغير)
    $updateStmt->execute(array($cart_quantity, $product_price, $product_title, $product_image, $cart_available_quantity, $cart_id));
    
    $updateCount = $updateStmt->rowCount();

    if ($updateCount > 0) {
        echo json_encode(array("status" => "success", "message" => "edit"));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Failed to update quantity"));
    }

} else {
    // 5. إذا لم يكن موجودًا، قم بإضافته (تم تصحيح أسماء المتغيرات)
    $insertStmt = $con->prepare("INSERT INTO `cart`
        (`cart_user_id`, `cart_product_id`, `cart_product_title`, `cart_product_image`, `cart_price`, `cart_quantity`, `cart_attributes`, `cart_platform`, `cart_available_quantity` , `cart_tier`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ? ,?)");

    $insertStmt->execute(array($user_id, $product_id, $product_title, $product_image, $product_price, $cart_quantity, $cart_attributes, $platform, $cart_available_quantity , $cart_tier));

    $insertCount = $insertStmt->rowCount();

    if ($insertCount > 0) {
        echo json_encode(array("status" => "success", "message" => "add"));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Failed to add product"));
    }
}

?>