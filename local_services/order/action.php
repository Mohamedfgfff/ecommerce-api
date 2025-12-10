<?php
include "../../connect.php";

$orderid = filterRequest("order_id");
$status  = filterRequest("status"); // 1=Approve, 2=Decline

$data = array("order_status" => $status);

updateData("orders_services", $data, "orders_services_id = $orderid");
