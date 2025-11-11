<?php
include_once "../connect.php";
include_once "../functions.php";

header('Content-Type: application/json; charset=utf-8');

try {
    $address_id = filterrequest("address_id");
    $user_id = filterrequest("user_id");

    if ($address_id !== null) {
        $stmt = $con->prepare("SELECT * FROM `addresses` WHERE `address_id` = ?");
        $stmt->execute([$address_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            echo json_encode(["status" => "success", "data" => $data]);
        } else {
            echo json_encode(["status" => "success", "data" => null]);
        }
        exit;
    }

    if ($user_id !== null) {
        $stmt = $con->prepare("SELECT * FROM `addresses` WHERE `user_id` = ? ORDER BY `is_default` DESC, `created_at` DESC");
        $stmt->execute([$user_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $data]);
        exit;
    }

    // if neither provided, return all addresses (optional; remove if not desired)
    $stmt = $con->prepare("SELECT * FROM `addresses` ORDER BY `user_id`, `is_default` DESC, `created_at` DESC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["status" => "success", "data" => $data]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
