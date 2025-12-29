<?php
include "../../connect.php";

$userid = filterRequest("userid");

if ($userid == null) {
    echo json_encode(array("status" => "fail", "message" => "userid is required"));
    exit;
}

getAllData("service_requests", "user_id = $userid");
