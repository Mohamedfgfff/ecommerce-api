<?php
include "../connect.php";

$search = filterRequest("search");

// تنظيف النص
$search = trim($search);
if (empty($search)) {
    echo json_encode(array("status" => "failure", "message" => "Empty search term"));
    exit;
}

// أولًا: جرب البحث بالجملة الكاملة
$stmt = $con->prepare("
    SELECT * FROM local_services 
    WHERE service_name LIKE ? OR service_desc LIKE ?
");
$searchValue = "%{$search}%";
$stmt->execute([$searchValue, $searchValue]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
    exit;
}

// ثانيًا: تقسيم الجملة إلى كلمات
$allWords = explode(' ', $search);

// تصفية الكلمات: نحتفظ فقط بالكلمات التي طولها >= 2 حرف
$words = array_filter($allWords, function ($word) {
    $word = trim($word);
    return strlen($word) >= 2;
});

// إذا لم تبقَ أي كلمة قابلة للبحث
if (empty($words)) {
    echo json_encode(array("status" => "failure", "message" => "Search term too short"));
    exit;
}

// ثالثًا: ابحث بكل كلمة منفصلة
$conditions = [];
foreach ($words as $word) {
    $conditions[] = "(service_name LIKE ? OR service_desc LIKE ?)";
}

$stmt = $con->prepare(
    "
    SELECT * FROM local_services 
    WHERE " . implode(' OR ', $conditions)
);

$params = [];
foreach ($words as $word) {
    $params[] = "%{$word}%";
    $params[] = "%{$word}%";
}

$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
    exit;
}

// رابعًا: إذا فشل كل شيء، جرب البحث بأي جزء من الكلمة (مثل: "ورش" → ابحث عن "رش")
// نستخدم تقنية "substring search" - نبحث عن أي جزء من النص

// نأخذ أول 2 أحرف من البحث (إن وجدت) ونبحث بها
if (strlen($search) >= 2) {
    $subSearch = substr($search, 0, 2); // أول حرفين
    $stmt = $con->prepare("
        SELECT * FROM local_services 
        WHERE service_name LIKE ? OR service_desc LIKE ?
    ");
    $searchValue = "%{$subSearch}%";
    $stmt->execute([$searchValue, $searchValue]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = $stmt->rowCount();

    if ($count > 0) {
        echo json_encode(array("status" => "success", "data" => $data));
        exit;
    }
}


// إذا لم ينجح أي شيء
echo json_encode(array("status" => "failure", "message" => "No results found"));
