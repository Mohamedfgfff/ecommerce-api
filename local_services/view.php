<?php
include "../connect.php";

$userid = filterRequest("usersid"); // Optional: if we want to check favs later

// Pagination
$page = filterRequest("page");
$pagesize = filterRequest("pagesize");

if (empty($page)) $page = 1;
if (empty($pagesize)) $pagesize = 20;

$offset = ($page - 1) * $pagesize;

// Get Total Count
$countStmt = $con->prepare("SELECT COUNT(*) as count FROM local_services WHERE 1 = 1");
$countStmt->execute();
$totalCount = $countStmt->fetchColumn();

// Get Data
$stmt = $con->prepare("SELECT * FROM local_services WHERE 1 = 1 LIMIT $pagesize OFFSET $offset");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = ceil($totalCount / $pagesize);

if ($totalCount > 0) {
    echo json_encode(array(
        "status" => "success",
        "count" => $totalCount,
        "total_pages" => $totalPages,
        "current_page" => $page,
        "next_page" => ($page < $totalPages) ? $page + 1 : null,
        "data" => $data
    ));
} else {
    echo json_encode(array("status" => "failure"));
}
