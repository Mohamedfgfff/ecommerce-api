<?php

include_once "../connect.php";
include_once "../functions.php";

$user_id = filterrequest("user_id");
$product_id = filterrequest("product_id");

$stem = $con->prepare("DELETE FROM `favorites` WHERE `favorite_user_id` = ? AND `favorite_product_id` = ?");

$stem->execute(array($user_id, $product_id));

$count = $stem->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success"));
} else {
    echo json_encode(array("status" => "fail"));
}

?>