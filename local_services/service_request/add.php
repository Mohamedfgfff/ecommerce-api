<?php
include "../../connect.php";

$userid    = filterRequest("userid");
$serviceid = filterRequest("serviceid");
$note      = filterRequest("note");
$price     = filterRequest("pricequoted");
$addressid = filterRequest("addressid"); // New


insertData("service_requests", [
    "user_id" => $userid,
    "service_id" => $serviceid,
    "note" => $note,
    "address_id" => $addressid,
    "quoted_price" => $price,
    "status" => "new"
]);
