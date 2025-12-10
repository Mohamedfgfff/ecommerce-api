<?php
include "../../connect.php";

$userid    = filterRequest("userid");
$serviceid = filterRequest("serviceid");
$note      = filterRequest("note");
$lat       = filterRequest("lat");
$lng       = filterRequest("lng");

$data = array(
    "order_user_id"    => $userid,
    "order_service_id" => $serviceid,
    "order_note"       => $note,
    "order_lat"        => $lat,
    "order_lng"        => $lng,
    "order_status"     => 0 // Pending
);

insertData("orders_services", $data);
