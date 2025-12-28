<?php
include "../../connect.php";

$userid    = filterRequest("userid");
$serviceid = filterRequest("serviceid");
$note      = filterRequest("note");
$price     = filterRequest("quoted_price");
$addressid = filterRequest("addressid"); // New

if ($price == null) {
    echo json_encode(array("status" => "fail", "message" => "Price is required"));
    exit;
}

insertData("service_requests", [
    "user_id" => $userid,
    "service_id" => $serviceid,
    "note" => $note,
    "address_id" => $addressid,
    "quoted_price" => $price,
    "status" => "new"
]);
