<?php
include "../../connect.php";

try {
    // Get the order ID from the request
    $orderid = filterRequest("orderid");

    if (empty($orderid)) {
        echo json_encode(array("status" => "failure", "message" => "orderid is required"));
        exit;
    }

    // Prepare the SQL query to fetch order details along with service, user, and address information
    $stmt = $con->prepare("
        SELECT 
            orders_services.*, 
            local_services.service_name, 
            local_services.service_image, 
            local_services.service_city AS service_location_city,
            local_services.service_desc,
            local_services.service_price,
            local_services.service_phone AS service_contact_phone,
            users.user_name,
            users.user_email,
            users.user_phone AS user_personal_phone,
            addresses.address_title,
            addresses.city AS address_city,
            addresses.street AS address_street,
            addresses.building_number,
            addresses.floor,
            addresses.apartment,
            addresses.latitude,
            addresses.longitude,
            addresses.phone AS address_contact_phone
        FROM orders_services 
        INNER JOIN local_services ON orders_services.order_service_id = local_services.service_id
        INNER JOIN users ON orders_services.order_user_id = users.user_id
        LEFT JOIN addresses ON orders_services.order_address_id = addresses.address_id
        WHERE orders_services.orders_services_id = ?
    ");

    $stmt->execute(array($orderid));
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Successfully found the order
        echo json_encode(array("status" => "success", "data" => $data));
    } else {
        // Order not found
        echo json_encode(array("status" => "failure", "message" => "Order not found"));
    }
} catch (PDOException $e) {
    echo json_encode(array("status" => "failure", "message" => $e->getMessage()));
}
