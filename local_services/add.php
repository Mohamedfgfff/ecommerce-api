<?php
include "../connect.php";

$name   = filterRequest("name");
$desc   = filterRequest("desc");
$image  = filterRequest("image");
$price  = filterRequest("price");
$city   = filterRequest("city");
$phone  = filterRequest("phone");

$data = array(
    "service_name"  => $name,
    "service_desc"  => $desc,
    "service_image" => $image,
    "service_price" => $price,
    "service_city"  => $city,
    "service_phone" => $phone
);

insertData("local_services", $data);
