<?php
// أمن: يتطلب ?run=1 لتشغيل الاستيراد من المتصفح
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('For security, add ?run=1 to URL to execute.');
}

// خيارات: ?replace=1 -> DROP TABLE if exists, otherwise rename existing table as backup
$replace = isset($_GET['replace']) && ($_GET['replace'] == '1' || strtolower($_GET['replace']) === 'true');

// مسار ملف SQL
$sqlFile = __DIR__ . '/order_items.sql';
if (!file_exists($sqlFile)) {
    die('❌ File order_items.sql not found!');
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die('❌ Failed to read SQL file.');
}

// فصل الأوامر بطريقة آمنة: نحاول إزالة التعليقات الكبيرة ثم نفصل عند الفاصلة المنقوطة
$commands = array_filter(array_map('trim', preg_split('/;(?=(?:[^\'"]|["\'][^\'"]*["\'])*$)/', $sql)));

// بيانات الاتصال — استخدم المتغيرات البيئية إن وُجدت
$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$db   = $_ENV['MYSQLDATABASE'] ?? 'railway';
$user = $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['MYSQLPASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "✅ Connected to database: " . htmlspecialchars($db) . "<br><br>";

    // تحقق إن كان جدول favorites موجوداً
    $tableName = 'order_items';
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
    $stmt->execute([$db, $tableName]);
    $exists = (int) $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

    if ($exists) {
        if ($replace) {
            // احذف الجدول الحالي
            echo "⚠ Table '$tableName' exists and replace=1 specified -> Dropping table...<br>";
            $pdo->exec("DROP TABLE IF EXISTS `$tableName`;");
            echo "✓ Dropped table `$tableName`.<br><br>";
        } else {
            // أنشئ نسخة احتياطية بإعادة التسمية
            $backupName = $tableName . '_backup_' . date('Ymd_His');
            echo "⚠ Table '$tableName' exists. Renaming to `$backupName` (backup) before import...<br>";
            $pdo->exec("RENAME TABLE `$tableName` TO `$backupName`;");
            echo "✓ Renamed to `$backupName`.<br><br>";
        }
    } else {
        echo "ℹ Table '$tableName' does not exist. Proceeding to import.<br><br>";
    }

    // تنفيذ الأوامر داخل TRANSACTION (إن أمكن)
    $pdo->beginTransaction();
    foreach ($commands as $cmd) {
        if ($cmd === '') continue;
        // تجنب تنفيذ تعابير BEGIN/COMMIT داخل الملف (نظرًا لاستخدامنا للترانزاكشن هنا)
        $trimmed = ltrim($cmd);
        if (preg_match('/^(BEGIN|COMMIT|ROLLBACK)/i', $trimmed)) {
            continue;
        }

        // تنفيذ الأمر
        $pdo->exec($cmd);
        echo "✓ Executed: " . htmlspecialchars(substr($cmd, 0, 120)) . (strlen($cmd) > 120 ? "..." : "") . "<br>";
    }
    $pdo->commit();

    echo "<br>🎉 Done! SQL imported successfully.";

} catch (Exception $e) {
    // محاولة التراجع إن كانت الترانزاكشن مفتوحة
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<br>❌ Error: " . htmlspecialchars($e->getMessage());

    // سجّل الخطأ لملف (اختياري)
    file_put_contents(__DIR__ . '/import_error.log', date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\nSQL snippet: " . substr($sql, 0, 2000) . "\n\n", FILE_APPEND);
    exit;
}
