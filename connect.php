<?php


// اقرأ بيانات قاعدة البيانات من متغيرات البيئة (Railway) أو استخدم قيم افتراضية للتطوير المحلي
$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$dbname = $_ENV['MYSQLDATABASE'] ?? 'railway'?? 'saltuk';
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




// قراءة إعدادات من المتغيرات البيئية مع قيم افتراضية مناسبة لـ XAMPP
// $host = $_ENV['MYSQLHOST'] ?? '127.0.0.1';
// $port = $_ENV['MYSQLPORT'] ?? '3306';
// $dbname = $_ENV['MYSQLDATABASE'] ?? 'saltuk'; // جدولت اسم القاعدة المحلية هنا
// $username = $_ENV['MYSQLUSER'] ?? 'root';
// $password = $_ENV['MYSQLPASSWORD'] ?? '';

// // DSN مع المنفذ و charset
// $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

// // خيارات PDO موصى بها
// $options = [
//     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//     PDO::ATTR_EMULATE_PREPARES => false,
//     PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
// ];

// $countrowinpage = 9;

// try {
//     $con = new PDO($dsn, $username, $password, $options);

//     // CORS headers (مناسب أثناء التطوير، راجع الأمان قبل الإنتاج)
//     header("Access-Control-Allow-Origin: *");
//     header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
//     header("Access-Control-Allow-Methods: POST, OPTIONS, GET, PUT, DELETE");

//     // تعامل سريع مع preflight request
//     if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//         http_response_code(200);
//         exit;
//     }

//     // تضمين الدوال (تأكد أن الملف لا يطبع أي شيء)
//     include_once __DIR__ . "/functions.php";

// } catch (PDOException $e) {
//     http_response_code(500);
//     // أثناء التطوير اطبع الرسالة، أما في الإنتاج سجّلها وارجع رسالة عامة
//     echo json_encode([
//         'error' => 'Database connection failed',
//         'message' => $e->getMessage()
//     ], JSON_UNESCAPED_UNICODE);
//     exit;
// }
