<?php
include "../../connect.php";

$serviceid = filterRequest("serviceid");

if ($serviceid == null) {
    echo json_encode(array("status" => "fail", "message" => "serviceid is required"));
    exit;
}

getAllData("service_requests", "service_id = $serviceid");
