<?php
include_once "../connect.php";
include_once "../functions.php";

header('Content-Type: application/json; charset=utf-8');
$input = file_get_contents('php://input');
$data = json_decode($input, true);

try {
   

if (!$data) {
    $data = $_POST; // fallback لو كانت request form-data
}

// استخدم القيم من المصفوفة
$user_id = $data["user_id"] ?? null;
$address_title = $data["address_title"] ?? null;
$city = $data["city"] ?? null;
$street = $data["street"] ?? null;
$building_number = $data["building_number"] ?? null;
$floor = $data["floor"] ?? null;
$apartment = $data["apartment"] ?? null;
$latitude = $data["latitude"] ?? null;
$longitude = $data["longitude"] ?? null;
$phone = $data["phone"] ?? null;
$is_default = $data["is_default"] ?? 0;

    if ($user_id === null || $address_title === null || $city === null || $street === null || $latitude === null || $longitude === null) {
        echo json_encode(["status" => "error", "message" => "missing_required_fields"]);
        exit;
    }

    // Normalize is_default
    $is_default = ($is_default === null) ? 0 : (int)$is_default;
    if ($is_default !== 0) $is_default = 1;

    // If setting default, unset other defaults of this user
    if ($is_default === 1) {
        $stmt0 = $con->prepare("UPDATE `addresses` SET `is_default` = 0 WHERE `user_id` = ?");
        $stmt0->execute([$user_id]);
    }

    $stmt = $con->prepare("INSERT INTO `addresses` 
        (`user_id`, `address_title`, `city`, `street`, `building_number`, `floor`, `apartment`, `latitude`, `longitude`, `phone`, `is_default`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $user_id,
        $address_title,
        $city,
        $street,
        ($building_number === null ? null : $building_number),
        ($floor === null ? null : $floor),
        ($apartment === null ? null : $apartment),
        $latitude,
        $longitude,
        ($phone === null ? null : $phone),
        $is_default
    ]);

    $insertedId = $con->lastInsertId();

    // fetch inserted row to return
    $fetch = $con->prepare("SELECT * FROM `addresses` WHERE `address_id` = ?");
    $fetch->execute([$insertedId]);
    $data = $fetch->fetch(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
