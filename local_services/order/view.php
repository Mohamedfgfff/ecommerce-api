<?php
include "../../connect.php";

try {
    $userid = filterRequest("userid");
    if (empty($userid)) {
        echo json_encode(array("status" => "failure", "message" => "userid is required"));
        exit;
    }

    // Join with local_services to get service name/image
    $stmt = $con->prepare("
        SELECT orders_services.*, local_services.service_name, local_services.service_image, local_services.service_city 
        FROM orders_services 
        INNER JOIN local_services ON orders_services.order_service_id = local_services.service_id
        WHERE orders_services.order_user_id = ?
        ORDER BY orders_services.orders_services_id DESC
    ");

    $stmt->execute(array($userid));
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = $stmt->rowCount();

    if ($count > 0) {
        echo json_encode(array("status" => "success", "data" => $data));
    } else {
        echo json_encode(array("status" => "failure"));
    }
} catch (PDOException $e) {
    echo json_encode(array("status" => "failure", "message" => $e->getMessage()));
}
