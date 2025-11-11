<?php

include_once "../connect.php";
include_once "../functions.php";

$query = strtolower(trim(filterrequest("query")));
$platform = filterrequest("platform");

if (!$query || !$platform) {
    echo json_encode(array("status" => "fail", "message" => "Missing required fields"));
    exit();
}

// 🔎 نبحث عن الكاش الصالح (لسه ما انتهتش صلاحيته)
$stmt = $con->prepare("SELECT `data` FROM `search_cash`
    WHERE `query` = ? AND `platform` = ? AND `expire_at` > NOW()
    LIMIT 1");
$stmt->execute(array($query, $platform));
$cache = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cache) {
    echo json_encode(array(
        "status" => "success",
        "source" => "cache",
        "data" => json_decode($cache['data'], true)
    ));
} else {
    echo json_encode(array("status" => "empty", "message" => "Cache expired or not found"));
}

?>
