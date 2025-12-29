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
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "failure"));
}
