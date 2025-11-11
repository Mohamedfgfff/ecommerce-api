<?php

include_once "../connect.php";
include_once "../functions.php";

$user_id = filterrequest("user_id");
$product_id = filterrequest("product_id");
$product_title = filterrequest("product_title");
$product_image = filterrequest("product_image");
$product_price = filterrequest("product_price");
$favorite_platform = filterrequest("favorite_platform");
$goods_sn = filterrequest("goods_sn");
$category_id = filterrequest("category_id");

if (!$user_id || !$product_id || !$product_title || !$product_image || !$product_price|| !$favorite_platform) {
    echo json_encode(array("status" => "fail", "message" => "Missing required fields"));
    exit();
}

$checkStmt = $con->prepare("SELECT * FROM `favorites` WHERE `favorite_user_id` = ? AND `favorite_product_id` = ?");
$checkStmt->execute(array($user_id, $product_id));
$count = $checkStmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "fail", "message" => "Product already in favorites"));
} else {
    $stem = $con->prepare("INSERT INTO `favorites`
        (`favorite_user_id`, `favorite_product_id`, `product_title`, `product_image`, `product_price`,`favorite_platform`,`goods_sn`,`category_id`) 
        VALUES (?, ?, ?, ?, ?,?,?,?)");

    $stem->execute(array($user_id, $product_id, $product_title, $product_image, $product_price,$favorite_platform ,$goods_sn,$category_id));

    $insertCount = $stem->rowCount();

    if ($insertCount > 0) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Failed to add product"));
    }
}

?>
