<?php
include "../connect.php";

$search = filterRequest("search");

// Search by name or description
$stmt = $con->prepare("SELECT * FROM local_services WHERE service_name LIKE ? OR service_desc LIKE ?");
$searchValue = "%" . $search . "%";
$stmt->execute(array($searchValue, $searchValue));

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "failure"));
}
