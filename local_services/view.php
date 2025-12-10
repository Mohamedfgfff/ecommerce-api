<?php
include "../connect.php";

$userid = filterRequest("usersid"); // Optional: if we want to check favs later

// Pagination
$page = filterRequest("page");
$pagesize = filterRequest("pagesize");

if (empty($page)) $page = 1;
if (empty($pagesize)) $pagesize = 10;

$offset = ($page - 1) * $pagesize;

$stmt = $con->prepare("SELECT * FROM local_services WHERE 1 = 1 LIMIT $pagesize OFFSET $offset");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "failure"));
}
