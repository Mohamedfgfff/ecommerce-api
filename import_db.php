<?php
// إعداد مهلة التنفيذ لتكون مفتوحة (للملفات الكبيرة)
set_time_limit(0);

// استيراد ملف الاتصال بالقاعدة
// هذا الملف ينشئ المتغير $con وهو كائن PDO
require "connect.php";

$sqlFile = 'saltuk.sql';

// التحقق من وجود ملف SQL
if (!file_exists($sqlFile)) {
    die("<h1>Error: SQL file '$sqlFile' not found in the current directory.</h1>");
}

echo "<html><head><title>Database Import</title><style>body{font-family:sans-serif;padding:20px;line-height:1.6;} .success{color:green;} .error{color:red;}</style></head><body>";
echo "<h2>Datebase Import Tool</h2>";
echo "<p>Reading <code>$sqlFile</code>...</p>";

// قراءة محتوى الملف
$sql = file_get_contents($sqlFile);

try {
    // تنفيذ استعلامات SQL
    // ملاحظة: قد يتطلب هذا تفعيل تعدد الاستعلامات (PDO::MYSQL_ATTR_MULTI_STATEMENTS) في بعض الحالات،
    // لكنه غالباً يعمل افتراضياً مع exec() للنصوص البسيطة، أو لأن connect.php لا يعطله صراحة.

    // إذا كان الملف يحتوي على أوامر START TRANSACTION وغيرها، ستنفذ ككتلة واحدة.
    $con->exec($sql);

    echo "<h3 class='success'>✅ Success!</h3>";
    echo "<p>Database tables have been created/imported successfully.</p>";
} catch (PDOException $e) {
    echo "<h3 class='error'>❌ Import Failed</h3>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
