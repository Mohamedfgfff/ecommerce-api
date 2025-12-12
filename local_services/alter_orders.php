<?php
include "../connect.php";

try {
    // Add order_address_id
    $con->exec("ALTER TABLE orders_services ADD COLUMN order_address_id INT NOT NULL AFTER order_note");

    // Check if lat/lng exist and drop them? Or just leave them for safety? 
    // Usually better to leave columns but make them nullable if we want safety, or drop them if we are sure.
    // Given the user request "replace", I'll drop them to be clean, or at least ensure the new column is there.

    // $con->exec("ALTER TABLE orders_services DROP COLUMN order_lat");
    // $con->exec("ALTER TABLE orders_services DROP COLUMN order_lng");

    echo json_encode(array("status" => "success", "message" => "Table orders_services updated successfully"));
} catch (PDOException $e) {
    echo json_encode(array("status" => "fail", "message" => $e->getMessage()));
}
