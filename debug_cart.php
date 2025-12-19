<?php
include_once "connect.php";
$dsn = "mysql:host=localhost;dbname=saltuk;charset=utf8";
$con = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $con->prepare("SELECT * FROM cart WHERE cart_user_id = 1 AND cart_product_id = '1005010401426485'");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
