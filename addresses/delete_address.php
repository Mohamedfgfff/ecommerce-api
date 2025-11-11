<?php
include_once "../connect.php";
include_once "../functions.php";

header('Content-Type: application/json; charset=utf-8');

try {
    $address_id = filterrequest("address_id");
    if ($address_id === null) {
        echo json_encode(["status" => "error", "message" => "address_id_required"]);
        exit;
    }

    $stmt = $con->prepare("DELETE FROM `addresses` WHERE `address_id` = ?");
    $stmt->execute([$address_id]);
    $count = $stmt->rowCount();

    if ($count > 0) {
        echo json_encode(["status" => "success", "message" => "deleted"]);
    } else {
        echo json_encode(["status" => "error", "message" => "not_found"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
