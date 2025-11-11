<?php

include_once "../connect.php";
include_once "../functions.php";

$user_id = filterrequest("user_id");

$stem = $con->prepare("SELECT 
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

$stem->execute(array($user_id));

$data = $stem->fetchAll(PDO::FETCH_ASSOC);

$count = $stem->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "success", "data" => [])); // نرجع قائمة فارغة
}

?>