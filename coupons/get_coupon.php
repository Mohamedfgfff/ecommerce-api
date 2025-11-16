<?php
// get_coupon.php
// Usage:
//  GET  /get_coupon.php?code=CODE[&user_id=123]
//  or POST JSON { "code": "CODE", "user_id": 123 }
// Response: JSON with clear errors for not_found, expired, disabled, usage_limit_reached, already_used.

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// include DB connection (must define $con as PDO)
include_once "../connect.php";

function send_json($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_input() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) return $json;
    // fallback to $_POST / $_GET
    return array_merge($_GET, $_POST);
}

$input = get_input();
$code = isset($input['code']) ? trim($input['code']) : null;
$user_id = isset($input['user_id']) && $input['user_id'] !== '' ? intval($input['user_id']) : null;

if (!$code) {
    send_json(['status' => 'error', 'error' => 'missing_code', 'message' => 'Missing coupon code parameter (code)'], 400);
}

if (!isset($con) || !($con instanceof PDO)) {
    send_json(['status' => 'error', 'error' => 'server_error', 'message' => 'Database connection not available'], 500);
}

try {
    // 1) fetch coupon
    $stmt = $con->prepare("SELECT coupon_id, coupon_name, coupon_platfrom, coupon_discount, coupon_expired, usage_limit, is_active FROM coupons WHERE coupon_name = ?");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        send_json([
            'status' => 'error',
            'error' => 'not_found',
            'message' => "Coupon with code '{$code}' not found"
        ], 404);
    }

    // 2) is_active check
    if (!isset($coupon['is_active']) || intval($coupon['is_active']) !== 1) {
        send_json([
            'status' => 'error',
            'error' => 'disabled',
            'message' => "Coupon '{$coupon['coupon_name']}' is disabled"
        ], 400);
    }

    // 3) expiry check
    if (!empty($coupon['coupon_expired'])) {
        try {
            $expiredAt = new DateTime($coupon['coupon_expired']);
            $now = new DateTime('now');
            if ($expiredAt < $now) {
                send_json([
                    'status' => 'error',
                    'error' => 'expired',
                    'message' => "Coupon '{$coupon['coupon_name']}' has expired",
                    'data' => [
                        'coupon_id' => $coupon['coupon_id'],
                        'coupon_expired' => $coupon['coupon_expired']
                    ]
                ], 400);
            }
        } catch (Exception $e) {
            send_json([
                'status' => 'error',
                'error' => 'invalid_expiry_format',
                'message' => 'Coupon expiry date format is invalid on server'
            ], 500);
        }
    }

    // 4) usage limit check (global)
    $usageLimit = isset($coupon['usage_limit']) ? intval($coupon['usage_limit']) : 0;
    $remainingUses = null; // null => unlimited

    if ($usageLimit > 0) {
        // count usages
        $stmt = $con->prepare("SELECT COUNT(*) as cnt FROM coupon_usages WHERE coupon_id = ?");
        $stmt->execute([$coupon['coupon_id']]);
        $cntRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $usedCount = isset($cntRow['cnt']) ? intval($cntRow['cnt']) : 0;

        if ($usedCount >= $usageLimit) {
            send_json([
                'status' => 'error',
                'error' => 'usage_limit_reached',
                'message' => "Coupon '{$coupon['coupon_name']}' usage limit reached",
                'data' => [
                    'coupon_id' => $coupon['coupon_id'],
                    'usage_limit' => $usageLimit,
                    'used_count' => $usedCount
                ]
            ], 400);
        }

        $remainingUses = $usageLimit - $usedCount;
    }

    // 5) per-user usage check (if user_id provided)
    if ($user_id) {
        $stmt = $con->prepare("SELECT COUNT(*) as cnt FROM coupon_usages WHERE coupon_id = ? AND user_id = ?");
        $stmt->execute([$coupon['coupon_id'], $user_id]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $userUsed = isset($userRow['cnt']) ? intval($userRow['cnt']) : 0;
        if ($userUsed > 0) {
            send_json([
                'status' => 'error',
                'error' => 'already_used',
                'message' => "User (id: {$user_id}) has already used coupon '{$coupon['coupon_name']}'",
                'data' => [
                    'coupon_id' => $coupon['coupon_id'],
                    'user_id' => $user_id,
                    'used_count_by_user' => $userUsed
                ]
            ], 400);
        }
    }

    // success — return coupon details (with remainingUses if applicable)
    $response = [
        'status' => 'success',
        'data' => [
            'coupon_id' => (int)$coupon['coupon_id'],
            'coupon_name' => $coupon['coupon_name'],
            'coupon_discount' => isset($coupon['coupon_discount']) ? (int)$coupon['coupon_discount'] : 0,
            'coupon_platfrom' => $coupon['coupon_platfrom'],
            'coupon_expired' => $coupon['coupon_expired'],
            'usage_limit' => $usageLimit > 0 ? $usageLimit : null,
            'remaining_uses' => $remainingUses
        ]
    ];

    send_json($response, 200);

} catch (PDOException $e) {
    send_json(['status' => 'error', 'error' => 'server_error', 'message' => 'Database query failed', 'details' => $e->getMessage()], 500);
}
