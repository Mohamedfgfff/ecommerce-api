<?php
include "../connect.php";

$name   = filterRequest("name");
$desc   = filterRequest("desc");
$price  = filterRequest("price");
$city   = filterRequest("city");
$phone  = filterRequest("phone");

// Handle Image Upload
$imageName = imageUpload("file");

if ($imageName != 'fail') {
    // Construct Full URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }
    $host = $_SERVER['HTTP_HOST'];
    $fullUrl = "$protocol://$host/upload/" . $imageName;

    $data = array(
        "service_name"  => $name,
        "service_desc"  => $desc,
        "service_image" => $fullUrl,
        "service_price" => $price,
        "service_city"  => $city,
        "service_phone" => $phone
    );

    insertData("local_services", $data);
} else {
    echo json_encode(array("status" => "fail", "message" => "Image Upload Failed"));
}
