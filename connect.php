<?php
// اقرأ بيانات قاعدة البيانات من متغيرات البيئة (Railway) أو استخدم قيم افتراضية للتطوير المحلي
$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$dbname = $_ENV['MYSQLDATABASE'] ?? 'saltuk';
$username = $_ENV['MYSQLUSER'] ?? 'root';
$password = $_ENV['MYSQLPASSWORD'] ?? '';

// إعداد DSN
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

// خيارات PDO
$option = [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

$countrowinpage = 9;

try {
    // إنشاء اتصال PDO
    $con = new PDO($dsn, $username, $password, $option);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // إعداد رؤوس CORS (للسماح بطلبات من أي مصدر)
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    header("Access-Control-Allow-Methods: POST, OPTIONS, GET");

    // تضمين ملف functions.php
    include "functions.php";

    // التحقق من المصادقة (إذا كان مطلوبًا)
    if (!isset($notAuth)) {
        // checkAuthenticate();
    }

} catch (PDOException $e) {
    // في حالة فشل الاتصال، أرسل رسالة خطأ JSON
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => $e->getMessage()
    ]);
}