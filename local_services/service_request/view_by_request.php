<?php
include "../../connect.php";

$requestid = filterRequest("requestid");

if ($requestid == null) {
    echo json_encode(array("status" => "fail", "message" => "requestid is required"));
    exit;
}

getAllData("service_requests", "request_id = $requestid");
