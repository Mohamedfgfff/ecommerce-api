<?php
// اقرأ بيانات قاعدة البيانات من متغيرات البيئة (Railway) أو استخدم قيم افتراضية للتطوير المحلي
$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$dbname = $_ENV['MYSQLDATABASE'] ?? 'railway';
$username = $_ENV['MYSQLUSER'] ?? 'root';     // ✅ اسم المتغير: $username
$password = $_ENV['MYSQLPASSWORD'] ?? '';     // ✅ اسم المتغير: $password

// إعداد DSN
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

// خيارات PDO
$option = [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

$countrowinpage = 9;

try {
    $con = new PDO($dsn, $username, $password, $option); // ✅ نفس الأسماء
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Methods: POST, OPTIONS, GET");

    include "functions.php";

    if (!isset($notAuth)) {
        // checkAuthenticate();
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => $e->getMessage()
    ]);
}