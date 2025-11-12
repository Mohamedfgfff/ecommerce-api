<?php

include_once "../connect.php";
include_once "../functions.php";

$user_id = filterrequest("user_id");
$cart_id = filterrequest("cart_id");

$stem = $con->prepare("DELETE FROM `cart` WHERE `cart_user_id` = ? AND `cart_id` = ?");

$stem->execute(array($user_id, $cart_id));

$count = $stem->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success"));
} else {
    echo json_encode(array("status" => "fail"));
}

?>