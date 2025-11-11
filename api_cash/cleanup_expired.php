<?php

include_once "../connect.php";

$stmt = $con->prepare("DELETE FROM `search_cash` WHERE `expire_at` < NOW()");
$stmt->execute();

echo json_encode(array(
    "status" => "success",
    "deleted" => $stmt->rowCount()
));

?>
