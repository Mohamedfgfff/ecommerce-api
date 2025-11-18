<?php
// use_coupon.php
// POST JSON { "code": "SUMMER10", "user_id": 55, "order_id": 1001 }
// Response: success + updated remaining_uses or clear error codes.

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once "../connect.php"; // must define $con as PDO

function send_json($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

// read inputs
$code = isset($input['code']) ? trim($input['code']) : null;
$user_id = isset($input['user_id']) && $input['user_id'] !== '' ? intval($input['user_id']) : null;
$order_id = isset($input['order_id']) && $input['order_id'] !== '' ? intval($input['order_id']) : null;

if (!$code || !$user_id || !$order_id) {
    send_json(['status'=>'error','error'=>'missing_parameters','message'=>'Required: code, user_id, order_id'], 400);
}

if (!isset($con) || !($con instanceof PDO)) {
    send_json(['status'=>'error','error'=>'server_error','message'=>'Database connection not available'], 500);
}

try {
    // begin transaction
    $con->beginTransaction();

    // 1) lock the coupon row to avoid race conditions
    $stmt = $con->prepare("SELECT coupon_id, coupon_name, coupon_platfrom, coupon_discount, coupon_expired, usage_limit, is_active FROM coupons WHERE coupon_name = ? FOR UPDATE");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        $con->rollBack();
        send_json(['status'=>'error','error'=>'not_found','message'=>"Coupon '{$code}' not found"], 404);
    }

    // check active
    if (!isset($coupon['is_active']) || intval($coupon['is_active']) !== 1) {
        $con->rollBack();
        send_json(['status'=>'error','error'=>'disabled','message'=>"Coupon '{$coupon['coupon_name']}' is disabled"], 400);
    }

    // check expiry
    if (!empty($coupon['coupon_expired'])) {
        try {
            $expiredAt = new DateTime($coupon['coupon_expired']);
            $now = new DateTime('now');
            if ($expiredAt < $now) {
                $con->rollBack();
                send_json(['status'=>'error','error'=>'expired','message'=>"Coupon '{$coupon['coupon_name']}' has expired", 'data'=>['coupon_expired'=>$coupon['coupon_expired']]], 400);
            }
        } catch (Exception $e) {
            $con->rollBack();
            send_json(['status'=>'error','error'=>'invalid_expiry_format','message'=>'Coupon expiry date format is invalid on server'], 500);
        }
    }

    $couponId = intval($coupon['coupon_id']);
    $usageLimit = isset($coupon['usage_limit']) ? intval($coupon['usage_limit']) : 0;

    // 2) check per-user usage
    $stmt = $con->prepare("SELECT COUNT(*) as cnt FROM coupon_usages WHERE coupon_id = ? AND user_id = ?");
    $stmt->execute([$couponId, $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $userUsed = isset($row['cnt']) ? intval($row['cnt']) : 0;
    if ($userUsed > 0) {
        $con->rollBack();
        send_json(['status'=>'error','error'=>'already_used','message'=>"User (id: {$user_id}) has already used coupon '{$coupon['coupon_name']}'", 'data'=>['used_count_by_user'=>$userUsed]], 400);
    }

    // 3) check global usage limit
    if ($usageLimit > 0) {
        $stmt = $con->prepare("SELECT COUNT(*) as cnt FROM coupon_usages WHERE coupon_id = ?");
        $stmt->execute([$couponId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $usedCount = isset($r['cnt']) ? intval($r['cnt']) : 0;
        if ($usedCount >= $usageLimit) {
            $con->rollBack();
            send_json(['status'=>'error','error'=>'usage_limit_reached','message'=>"Coupon '{$coupon['coupon_name']}' usage limit reached", 'data'=>['usage_limit'=>$usageLimit,'used_count'=>$usedCount]], 400);
        }
    } else {
        // unlimited
        $usedCount = 0;
    }

    // 4) insert usage (reserve the usage for this order)
    $stmt = $con->prepare("INSERT INTO coupon_usages (coupon_id, user_id, order_id, used_at) VALUES (?, ?, ?, NOW())");
    $ok = $stmt->execute([$couponId, $user_id, $order_id]);
    if (!$ok) {
        $con->rollBack();
        send_json(['status'=>'error','error'=>'insert_failed','message'=>'Failed to record coupon usage'], 500);
    }

    // recompute remaining uses
    if ($usageLimit > 0) {
        $stmt = $con->prepare("SELECT COUNT(*) as cnt FROM coupon_usages WHERE coupon_id = ?");
        $stmt->execute([$couponId]);
        $r2 = $stmt->fetch(PDO::FETCH_ASSOC);
        $usedCount2 = isset($r2['cnt']) ? intval($r2['cnt']) : 0;
        $remaining = max(0, $usageLimit - $usedCount2);
    } else {
        $remaining = null;
    }

    // commit
    $con->commit();

    // success response
    $resp = [
        'status' => 'success',
        'message' => "Coupon '{$coupon['coupon_name']}' applied and usage recorded",
        'data' => [
            'coupon_id' => $couponId,
            'coupon_name' => $coupon['coupon_name'],
            'coupon_discount' => isset($coupon['coupon_discount']) ? (int)$coupon['coupon_discount'] : 0,
            'usage_limit' => $usageLimit > 0 ? $usageLimit : null,
            'remaining_uses' => $remaining
        ]
    ];
    send_json($resp, 200);

} catch (PDOException $e) {
    if ($con->inTransaction()) $con->rollBack();
    send_json(['status'=>'error','error'=>'server_error','message'=>'Database error','details'=>$e->getMessage()], 500);
} catch (Exception $e) {
    if ($con->inTransaction()) $con->rollBack();
    send_json(['status'=>'error','error'=>'server_error','message'=>$e->getMessage()], 500);
}
