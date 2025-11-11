<?php

include_once "../connect.php";
include_once "../functions.php";

$user_id = filterrequest("user_id");
$favorite_platform = filterrequest("favorite_platform");

$stem = $con->prepare("SELECT 
    favorite_id, 
    favorite_product_id as productId, 
    product_title, 
    product_image, 
    product_price,
    favorite_platform 
    FROM `favorites` WHERE `favorite_user_id` = ? AND `favorite_platform` = ?");

$stem->execute(array($user_id , $favorite_platform));

$data = $stem->fetchAll(PDO::FETCH_ASSOC);

$count = $stem->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "success", "data" => [])); 
}

?>