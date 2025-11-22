<?php
// get_orders.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once "../connect.php"; // يجب أن يعرّف $con كـ PDO

function send_json($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_GET + $_POST;

$user_id  = isset($input['user_id']) ? intval($input['user_id']) : null;
$order_id = isset($input['order_id']) ? intval($input['order_id']) : null;
$status   = isset($input['status']) ? trim($input['status']) : null;
$limit    = isset($input['limit']) ? intval($input['limit']) : 100;
$offset   = isset($input['offset']) ? intval($input['offset']) : 0;

if (!$user_id) {
    send_json(['status'=>'error','error'=>'missing_user_id','message'=>'Parameter user_id is required'], 400);
}

if (!isset($con) || !($con instanceof PDO)) {
    send_json(['status'=>'error','error'=>'server_error','message'=>'Database connection not available'], 500);
}

try {
    if ($order_id) {
        // جلب طلب واحد — شامل العناصر والعنوان والكوبون (إن وجد)
        $stmt = $con->prepare("
            SELECT o.*, 
                   a.address_id, a.address_title, a.city AS address_city, a.street AS address_street,
                   a.building_number, a.floor, a.apartment, a.latitude, a.longitude, a.phone AS address_phone,
                   c.coupon_name, c.coupon_discount
            FROM orders o
            LEFT JOIN addresses a ON o.address_id = a.address_id
            LEFT JOIN coupons c ON o.coupon_id = c.coupon_id
            WHERE o.order_id = ? AND o.user_id = ? LIMIT 1
        ");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            send_json(['status'=>'error','error'=>'not_found','message'=>'Order not found for this user'], 404);
        }

        // جلب العناصر
        $it = $con->prepare("SELECT item_id, product_id, product_platform, product_title, product_link, product_image, product_price, quantity, attributes FROM order_items WHERE order_id = ?");
        $it->execute([$order_id]);
        $items = $it->fetchAll(PDO::FETCH_ASSOC);

        // جهز بيانات العنوان بصيغة موحدة (null إذا مش موجود)
        $address = null;
        if (!empty($order['address_id'])) {
            $address = [
                'address_id' => (int)$order['address_id'],
                'title' => $order['address_title'] ?? null,
                'city' => $order['address_city'] ?? null,
                'street' => $order['address_street'] ?? null,
                'building_number' => $order['building_number'] ?? null,
                'floor' => $order['floor'] ?? null,
                'apartment' => $order['apartment'] ?? null,
                'latitude' => $order['latitude'] !== null ? (float)$order['latitude'] : null,
                'longitude' => $order['longitude'] !== null ? (float)$order['longitude'] : null,
                'phone' => $order['address_phone'] ?? null,
            ];
        }

        // بنبني الـ response
        $orderResponse = [
            'order_id' => (int)$order['order_id'],
            'user_id' => (int)$order['user_id'],
            'status' => $order['status'],
            'subtotal' => (float)$order['subtotal'],
            'discount_amount' => (float)$order['discount_amount'],
            'shipping_amount' => (float)$order['shipping_amount'],
            'total_amount' => (float)$order['total_amount'],
            'payment_method' => $order['payment_method'],
            'payment_status' => $order['payment_status'],
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
            'address' => $address,
            'coupon' => $order['coupon_name'] ? [
                'coupon_name' => $order['coupon_name'],
                'coupon_discount' => $order['coupon_discount']
            ] : null,
            'items' => $items
        ];

        send_json(['status'=>'success','data'=>$orderResponse], 200);
    } else {
        // جلب قائمة الأوردرات للمستخدم مع إمكانية فلترة بالحالة
        $params = [$user_id];
        $sql = "SELECT order_id, status, subtotal, discount_amount, shipping_amount, total_amount, payment_status, created_at FROM orders WHERE user_id = ?";

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $con->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $list = array_map(function($r){
            return [
                'order_id' => (int)$r['order_id'],
                'status' => $r['status'],
                'subtotal' => (float)$r['subtotal'],
                'discount_amount' => (float)$r['discount_amount'],
                'shipping_amount' => (float)$r['shipping_amount'],
                'total_amount' => (float)$r['total_amount'],
                'payment_status' => $r['payment_status'],
                'created_at' => $r['created_at']
            ];
        }, $rows);

        send_json(['status'=>'success','data'=>$list], 200);
    }

} catch (PDOException $e) {
    send_json(['status'=>'error','error'=>'server_error','message'=>'Database query failed','details'=>$e->getMessage()], 500);
}
