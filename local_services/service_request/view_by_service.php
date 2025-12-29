<?php
include "../../connect.php";

$serviceid = filterRequest("serviceid");

getAllData("service_requests", "service_id = $serviceid");
