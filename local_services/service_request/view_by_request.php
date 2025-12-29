<?php
include "../../connect.php";

$requestid = filterRequest("requestid");

if ($requestid == null) {
    echo json_encode(array("status" => "fail", "message" => "requestid is required"));
    exit;
}

// SQL query to fetch request details joined with user, service, and address info
$stmt = $con->prepare("
    SELECT 
        sr.*,
        u.user_name, u.user_email, u.user_phone,
        ls.service_name, ls.service_desc, ls.service_image, ls.service_price, ls.service_city, ls.service_phone AS service_contact_phone,
        addr.address_title, addr.city AS address_city, addr.street, addr.building_number, addr.floor, addr.apartment, addr.latitude, addr.longitude, addr.phone AS address_phone
    FROM service_requests sr
    LEFT JOIN users u ON sr.user_id = u.user_id
    LEFT JOIN local_services ls ON sr.service_id = ls.service_id
    LEFT JOIN addresses addr ON sr.address_id = addr.address_id
    WHERE sr.request_id = ?
");

$stmt->execute([$requestid]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = $stmt->rowCount();
if ($count > 0) {
    $structuredData = [];
    foreach ($data as $row) {
        $structuredData[] = [
            "request_id"   => $row['request_id'],
            "status"       => $row['status'],
            "quoted_price" => $row['quoted_price'],
            "note"         => $row['note'],
            "created_at"   => $row['created_at'],
            "user" => [
                "name"  => $row['user_name'],
                "email" => $row['user_email'],
                "phone" => $row['user_phone'],
            ],
            "service" => [
                "id"            => $row['service_id'],
                "name"          => $row['service_name'],
                "description"   => $row['service_desc'],
                "image"         => $row['service_image'],
                "price"         => $row['service_price'],
                "city"          => $row['service_city'],
                "contact_phone" => $row['service_contact_phone'],
            ],
            "address" => [
                "id"              => $row['address_id'],
                "title"           => $row['address_title'],
                "city"            => $row['address_city'],
                "street"          => $row['street'],
                "building_number" => $row['building_number'],
                "floor"           => $row['floor'],
                "apartment"       => $row['apartment'],
                "latitude"        => $row['latitude'],
                "longitude"       => $row['longitude'],
                "phone"           => $row['address_phone'],
            ],
        ];
    }
    echo json_encode(array("status" => "success", "data" => $structuredData));
} else {
    echo json_encode(array("status" => "failure"));
}
