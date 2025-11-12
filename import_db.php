<?php
// تأكد إنك بتستخدمه من المتصفح مش من API
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('For security, add ?run=1 to URL to execute.');
}

// مسار ملف SQL
$sqlFile = __DIR__ . '/saltuk.sql';

if (!file_exists($sqlFile)) {
    die('❌ File saltuk.sql not found!');
}

$sql = file_get_contents($sqlFile);
if (!$sql) {
    die('❌ Failed to read SQL file.');
}

// فصل الأوامر
$commands = explode(';', $sql);
$commands = array_filter(array_map('trim', $commands));

// احصل على بيانات الاتصال من المتغيرات البيئية
$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$db   = $_ENV['MYSQLDATABASE'] ?? 'railway';
$user = $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['MYSQLPASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database: $db<br><br>";

    foreach ($commands as $cmd) {
        if (!empty($cmd)) {
            $pdo->exec($cmd);
            echo "✓ Executed: " . htmlspecialchars(substr($cmd, 0, 60)) . "...<br>";
        }
    }

    echo "<br>🎉 Done! Database imported successfully.";

} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}
?>