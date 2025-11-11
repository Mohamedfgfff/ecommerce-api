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

    // Collect possible fields (only update provided ones)
    $allowed = [
        "user_id", "address_title", "city", "street", "building_number",
        "floor", "apartment", "latitude", "longitude", "phone", "is_default"
    ];

    $sets = [];
    $params = [];

    foreach ($allowed as $field) {
        $val = filterrequest($field);
        if ($val !== null) {
            // cast is_default
            if ($field === "is_default") {
                $val = (int)$val;
                $val = ($val !== 0) ? 1 : 0;
            }
            $sets[] = "`$field` = ?";
            $params[] = $val;
        }
    }

    if (count($sets) === 0) {
        echo json_encode(["status" => "error", "message" => "no_fields_to_update"]);
        exit;
    }

    // If is_default will be set to 1, we must unset other defaults for that user
    $isDefaultPos = array_search("`is_default` = ?", $sets);
    if ($isDefaultPos !== false && $params[$isDefaultPos] == 1) {
        // get user_id: if provided in this request use it, otherwise fetch from DB
        $user_id = null;
        foreach ($allowed as $i => $field) {
            if ($field === "user_id") {
                // find if user_id was provided in params (we need to map)
                // simpler: check filterrequest directly
                $tmp = filterrequest("user_id");
                if ($tmp !== null) $user_id = $tmp;
                break;
            }
        }
        if ($user_id === null) {
            $q = $con->prepare("SELECT `user_id` FROM `addresses` WHERE `address_id` = ?");
            $q->execute([$address_id]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if ($row) $user_id = $row['user_id'];
        }
        if ($user_id !== null) {
            $unset = $con->prepare("UPDATE `addresses` SET `is_default` = 0 WHERE `user_id` = ? AND `address_id` != ?");
            $unset->execute([$user_id, $address_id]);
        }
    }

    $params[] = $address_id;
    $sql = "UPDATE `addresses` SET " . implode(", ", $sets) . " WHERE `address_id` = ?";

    $stmt = $con->prepare($sql);
    $stmt->execute($params);

    // return updated row
    $fetch = $con->prepare("SELECT * FROM `addresses` WHERE `address_id` = ?");
    $fetch->execute([$address_id]);
    $data = $fetch->fetch(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
