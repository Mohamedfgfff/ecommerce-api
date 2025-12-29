<?php
include "../../connect.php";

$userid = filterRequest("userid");

getAllData("service_requests", "user_id = $userid");
