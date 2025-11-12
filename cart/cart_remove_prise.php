<?php

include_once "../connect.php";
include_once "../functions.php";

$user_id = filterrequest("user_id");
$product_id = filterrequest("product_id");
$cart_attributes = jsonrequest("attributes"); 




if (!$user_id || !$product_id) {
    echo json_encode(array("status" => "fail", "message" => "Missing required fields"));
    exit();
}

$checkStmt = $con->prepare("SELECT * FROM `cart` WHERE `cart_user_id` = ? AND `cart_product_id` = ? AND `cart_attributes` = ?");
$checkStmt->execute(array($user_id, $product_id, $cart_attributes));
$existing_item = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existing_item) {
    $new_quantity = $existing_item['cart_quantity'] - 1; 
    $cart_id = $existing_item['cart_id'];

    $updateStmt = $con->prepare("UPDATE `cart` SET 
        `cart_quantity` = ?
        WHERE `cart_id` = ?");
    
    $updateStmt->execute(array($new_quantity,  $cart_id));
    
    $updateCount = $updateStmt->rowCount();

    if ($updateCount > 0) {
        echo json_encode(array("status" => "success", "message" => "edit"));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Failed to update quantity"));
    }

} 

?>