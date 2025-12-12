<?php
include "../../connect.php";

$userid    = filterRequest("userid");
$serviceid = filterRequest("serviceid");
$note      = filterRequest("note");
$lat       = filterRequest("lat"); // Deprecated
$lng       = filterRequest("lng"); // Deprecated
$addressid = filterRequest("addressid"); // New

$data = array(
    "order_user_id"    => $userid,
    "order_service_id" => $serviceid,
    "order_note"       => $note,
    "order_address_id" => $addressid, // Changed
    "order_status"     => 0 // Pending
);

insertData("orders_services", $data);
