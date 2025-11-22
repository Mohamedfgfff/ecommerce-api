<?php
// create_order.php (supports client discount OR server-side coupon apply)
// POST JSON:
// {
//   "user_id": 55,
//   "platform_code": "alibaba",
//   "items": [...],
//   "coupon_code": "mo",               // اختياري
//   "apply_coupon_now": true,          // إذا true => السيرفر يتحقق ويحسب الخصم (موصى به)
//   "discount_amount": 50.00,          // إذا apply_coupon_now=false وعايز تستخدم قيمة يدوية (غير آمن)
//   "shipping_amount": 10.00,
//   "payment_method": "cash"
// }

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once "../connect.php"; // يجب أن يعرّف $con كـ PDO

function send_json($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// read input
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

$user_id = isset($input['user_id']) ? intval($input['user_id']) : null;
$address_id = isset($input['address_id']) ? intval($input['address_id']) : null;
$items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
$platform_code = isset($input['platform_code']) ? trim($input['platform_code']) : null;
$coupon_code = isset($input['coupon_code']) ? trim($input['coupon_code']) : null;
$apply_coupon_now = isset($input['apply_coupon_now']) ? boolval($input['apply_coupon_now']) : false;
$client_discount_amount = isset($input['discount_amount']) ? floatval($input['discount_amount']) : 0.0;
$shipping_amount = isset($input['shipping_amount']) ? floatval($input['shipping_amount']) : 0.0;
$payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : null;

if (!$user_id || empty($items)) {
    send_json(['status'=>'error','error'=>'missing_parameters','message'=>'Required: user_id and items (non-empty array)'], 400);
}

if (!isset($con) || !($con instanceof PDO)) {
    send_json(['status'=>'error','error'=>'server_error','message'=>'Database connection not available'], 500);
}

try {
    // compute subtotal from items
    $subtotal = 0.0;
    foreach ($items as $it) {
        $price = isset($it['product_price']) ? floatval($it['product_price']) : 0.0;
        $qty = isset($it['quantity']) ? intval($it['quantity']) : 1;
        $subtotal += $price * $qty;
    }
    $subtotal = round($subtotal, 2);

    // default: no discount
    $discountAmount = 0.0;
    $couponIdToSave = null;
    $couponData = null;
    $remainingUses = null;

    if ($coupon_code && $apply_coupon_now) {
        // ---------- Secure server-side coupon validation & compute discount ----------
        // fetch coupon (we assume table name 'coupons' or 'coupon' adjust if needed)
        $stmt = $con->prepare("SELECT coupon_id, coupon_name, coupon_platfrom, coupon_discount, coupon_expired, usage_limit, is_active FROM coupons WHERE coupon_name = ? LIMIT 1");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            send_json(['status'=>'error','error'=>'not_found','message'=>"Coupon with code '{$coupon_code}' not found"], 404);
        }

        if (!isset($coupon['is_active']) || intval($coupon['is_active']) !== 1) {
            send_json(['status'=>'error','error'=>'disabled','message'=>"Coupon '{$coupon['coupon_name']}' is disabled"], 400);
        }

        if (!empty($coupon['coupon_expired'])) {
            try {
                $expiredAt = new DateTime($coupon['coupon_expired']);
                $now = new DateTime('now');
                if ($expiredAt < $now) {
                    send_json(['status'=>'error','error'=>'expired','message'=>"Coupon '{$coupon['coupon_name']}' has expired", 'data'=>['coupon_expired'=>$coupon['coupon_expired']]], 400);
                }
            } catch (Exception $e) {
                send_json(['status'=>'error','error'=>'invalid_expiry_format','message'=>'Coupon expiry date format is invalid on server'], 500);
            }
        }

        // check usage limit (global)
        $usageLimit = isset($coupon['usage_limit']) ? intval($coupon['usage_limit']) : 0;
        if ($usageLimit > 0) {
            $stmt = $con->prepare("SELECT COUNT(*) as cnt FROM coupon_usages WHERE coupon_id = ?");
            $stmt->execute([$coupon['coupon_id']]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $usedCount = isset($r['cnt']) ? intval($r['cnt']) : 0;
            if ($usedCount >= $usageLimit) {
                send_json(['status'=>'error','error'=>'usage_limit_reached','message'=>"Coupon '{$coupon['coupon_name']}' usage limit reached", 'data'=>['usage_limit'=>$usageLimit,'used_count'=>$usedCount]], 400);
            }
            $remainingUses = $usageLimit - $usedCount;
        }

        // check user used?
        $stmt = $con->prepare("SELECT COUNT(*) as cnt FROM coupon_usages WHERE coupon_id = ? AND user_id = ?");
        $stmt->execute([$coupon['coupon_id'], $user_id]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $userUsed = isset($userRow['cnt']) ? intval($userRow['cnt']) : 0;
        if ($userUsed > 0) {
            send_json(['status'=>'error','error'=>'already_used','message'=>"User (id: {$user_id}) has already used coupon '{$coupon['coupon_name']}'", 'data'=>['used_count_by_user'=>$userUsed]], 400);
        }

        // compute discount (assume coupon_discount is percent)
        // compute discount as FIXED AMOUNT (not percent)
$fixedDiscount = isset($coupon['coupon_discount']) ? floatval($coupon['coupon_discount']) : 0.0;
// safety: don't allow discount to exceed subtotal
$discountAmount = round(min($fixedDiscount, $subtotal), 2);


        $couponIdToSave = intval($coupon['coupon_id']);
        $couponData = $coupon;
    } elseif (!$apply_coupon_now && isset($client_discount_amount) && $client_discount_amount > 0) {
        // ---------- Insecure: trust client discount_amount ----------
        // Only do this if you understand the risk.
        $discountAmount = round(floatval($client_discount_amount), 2);
    }
    // else: no coupon, no client discount -> discountAmount = 0

    // compute total
    $total = round($subtotal + floatval($shipping_amount) - $discountAmount, 2);
    if ($total < 0) $total = 0.0;

    // insert order and items inside transaction
    $con->beginTransaction();

    $insertOrderSql = "INSERT INTO orders (user_id, coupon_id,address_id, total_amount, subtotal, shipping_amount, discount_amount, payment_method, status, created_at, updated_at)
                       VALUES (?, ?, ?, ?,?, ?, ?, ?, 'pending_approval', NOW(), NOW())";
    $stmt = $con->prepare($insertOrderSql);
    $ok = $stmt->execute([$user_id, $couponIdToSave, $address_id, $total, $subtotal, $shipping_amount, $discountAmount, $payment_method]);
    if (!$ok) {
        $con->rollBack();
        send_json(['status'=>'error','error'=>'insert_failed','message'=>'Failed to create order'], 500);
    }
    $orderId = $con->lastInsertId();

    $itemStmt = $con->prepare("INSERT INTO order_items (order_id, product_id, product_platform, product_title, product_link, product_image, product_price, quantity, attributes, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    foreach ($items as $it) {
        $pid = isset($it['product_id']) ? $it['product_id'] : '';
        $pplat = isset($it['product_platform']) ? $it['product_platform'] : ($platform_code ?? '');
        $title = isset($it['product_title']) ? $it['product_title'] : '';
        $link = isset($it['product_link']) ? $it['product_link'] : '';
        $image = isset($it['product_image']) ? $it['product_image'] : '';
        $price = isset($it['product_price']) ? floatval($it['product_price']) : 0.0;
        $qty = isset($it['quantity']) ? intval($it['quantity']) : 1;
        $attrs = isset($it['attributes']) ? json_encode($it['attributes'], JSON_UNESCAPED_UNICODE) : null;

        $res = $itemStmt->execute([$orderId, $pid, $pplat, $title, $link, $image, $price, $qty, $attrs]);
        if (!$res) {
            $con->rollBack();
            send_json(['status'=>'error','error'=>'insert_item_failed','message'=>'Failed to insert order item'], 500);
        }
    }

    $con->commit();

    $resp = [
        'status' => 'success',
        'message' => 'Order created (pending approval)',
        'order_id' => (int)$orderId,
        'data' => [
            'subtotal' => $subtotal,
            'shipping_amount' => (float)$shipping_amount,
            'discount_amount' => $discountAmount,
            'total_amount' => $total,
            'coupon_applied_now' => $apply_coupon_now ? true : false,
            'coupon' => $couponData ? [
                'coupon_id' => (int)$couponData['coupon_id'],
                'coupon_name' => $couponData['coupon_name'],
                'coupon_discount' => (int)$couponData['coupon_discount'],
                'remaining_uses' => $remainingUses
            ] : ($coupon_code ? ['note'=>'coupon provided but not applied'] : null),
            'status' => 'pending_approval'
        ]
    ];

    send_json($resp, 201);

} catch (PDOException $e) {
    if ($con->inTransaction()) $con->rollBack();
    send_json(['status'=>'error','error'=>'server_error','message'=>'Database error','details'=>$e->getMessage()], 500);
} catch (Exception $e) {
    if ($con->inTransaction()) $con->rollBack();
    send_json(['status'=>'error','error'=>'server_error','message'=>$e->getMessage()], 500);
}
