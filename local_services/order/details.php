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
            users.user_phone,
            addresses.address_title,
            addresses.city,
            addresses.street,
            addresses.building_number,
            addresses.floor,
            addresses.apartment,
            addresses.latitude,
            addresses.longitude,
            addresses.phone
        FROM orders_services 
        INNER JOIN local_services ON orders_services.order_service_id = local_services.service_id
        INNER JOIN users ON orders_services.order_user_id = users.user_id
        LEFT JOIN addresses ON orders_services.order_address_id = addresses.address_id
        WHERE orders_services.orders_services_id = ?
    ");

    $stmt->execute(array($orderid));
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Structure the data
        $structuredData = array(
            "order" => array(
                "order_id" => $data['orders_services_id'],
                "user_id" => $data['order_user_id'],
                "service_id" => $data['order_service_id'],
                "status" => $data['order_status'],
                "note" => $data['order_note'],
                "address_id" => $data['order_address_id'],
                "create_at" => $data['order_create_at']
            ),
            "service" => array(
                "name" => $data['service_name'],
                "image" => $data['service_image'],
                "city" => $data['service_location_city'],
                "desc" => $data['service_desc'],
                "price" => $data['service_price'],
                "phone" => $data['service_contact_phone']
            ),
            "user" => array(
                "name" => $data['user_name'],
                "email" => $data['user_email'],
                "phone" => $data['user_phone']
            ),
            "address" => array(
                "title" => $data['address_title'],
                "city" => $data['city'],
                "street" => $data['street'],
                "building_number" => $data['building_number'],
                "floor" => $data['floor'],
                "apartment" => $data['apartment'],
                "latitude" => $data['latitude'],
                "longitude" => $data['longitude'],
                "phone" => $data['phone']
            )
        );

        echo json_encode(array("status" => "success", "data" => $structuredData));
    } else {
        // Order not found
        echo json_encode(array("status" => "failure", "message" => "Order not found"));
    }
} catch (PDOException $e) {
    echo json_encode(array("status" => "failure", "message" => $e->getMessage()));
}
