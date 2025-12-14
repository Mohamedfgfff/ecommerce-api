<?php
include "../../connect.php";

$orderid = filterRequest("order_id");
$status  = filterRequest("status"); // 1=Approve, 2=Decline

// Check current order status
$stmt = $con->prepare("SELECT order_status FROM orders_services WHERE orders_services_id = ?");
$stmt->execute(array($orderid));
$currentStatus = $stmt->fetchColumn();

if ($currentStatus != "pending" && $currentStatus != "approved" && $currentStatus != "pending_approval") {
    echo json_encode(array("status" => "failure", "message" => "Cannot cancel order because it is $currentStatus"));
    exit;
}

$data = array("order_status" => $status);

updateData("orders_services", $data, "orders_services_id = $orderid");
