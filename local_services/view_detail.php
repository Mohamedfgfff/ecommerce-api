<?php
include "../connect.php";

$serviceid = filterRequest("service_id");

getAllData("local_services", "service_id = ?", array($serviceid));
