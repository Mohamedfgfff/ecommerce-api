<?php

include_once "../connect.php";
include_once "../functions.php";

$user_id    = filterrequest("user_id");
$product_id = filterrequest("product_id");
$attributes = jsonrequest("attributes");

if (!$user_id || !$product_id || !$attributes) {
    echo json_encode(array("status" => "fail", "message" => "Missing required fields"));
    exit();
}

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

$stmt->execute([$user_id, $product_id, $attributes, $user_id, $product_id]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

$quantity    = (int)($result['cart_quantity'] ?? 0);
$in_favorite = (bool)$result['in_favorite'];

echo json_encode([
    "status" => "success",
    "cart_quantity" => $quantity,
    "in_favorite" => $in_favorite
]);
