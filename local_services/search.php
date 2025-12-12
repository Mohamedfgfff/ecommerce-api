<?php
include "../connect.php";

$search = filterRequest("search");

// تنظيف النص
$search = trim($search);
if (empty($search)) {
    echo json_encode(array("status" => "failure", "message" => "Empty search term"));
    exit;
}

// تقسيم الجملة إلى كلمات
$allWords = explode(' ', $search);

// تصفية الكلمات: نحتفظ فقط بالكلمات التي طولها >= 2 حرف
$words = array_filter($allWords, function ($word) {
    $word = trim($word);
    return strlen($word) >= 2; // ≥ 2 أحرف (يمكنك تغييرها إلى 3 إذا أردت)
});

// إذا لم تبقَ أي كلمة قابلة للبحث
if (empty($words)) {
    echo json_encode(array("status" => "failure", "message" => "Search term too short"));
    exit;
}

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

try {
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = $stmt->rowCount();

    if ($count > 0) {
        echo json_encode(array("status" => "success", "data" => $data));
    } else {
        echo json_encode(array("status" => "failure", "message" => "No results found"));
    }
} catch (PDOException $e) {
    echo json_encode(array("status" => "error", "message" => "Query failed"));
}
