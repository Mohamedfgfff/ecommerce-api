<?php


header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once "../connect.php"; // يجب أن يعرّف $con كـ PDO

function send_json($payload, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// قراءة المدخلات
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

$user_id = isset($input['user_id']) ? intval($input['user_id']) : null;
$order_id = isset($input['order_id']) ? intval($input['order_id']) : null;

// التحقق من المدخلات المطلوبة
if (!$user_id || !$order_id) {
    send_json([
        'status' => 'error',
        'error' => 'missing_parameters',
        'message' => 'Required: user_id and order_id'
    ], 400);
}

// التحقق من الاتصال بقاعدة البيانات
if (!isset($con) || !($con instanceof PDO)) {
    send_json([
        'status' => 'error',
        'error' => 'server_error',
        'message' => 'Database connection not available'
    ], 500);
}

try {
    // جلب الطلب أولاً للتحقق من الحالة والملكية
    $stmt = $con->prepare("SELECT order_id, user_id, status, total_amount 
                           FROM orders 
                           WHERE order_id = ? AND user_id = ? 
                           LIMIT 1");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // التحقق من وجود الطلب
    if (!$order) {
        send_json([
            'status' => 'error',
            'error' => 'not_found',
            'message' => 'Order not found or does not belong to this user'
        ], 404);
    }

    $currentStatus = $order['status'];

    // التحقق من الحالة: يجب أن تكون pending_approval أو approved فقط
    $allowedStatuses = ['pending_approval', 'approved'];

    if (!in_array($currentStatus, $allowedStatuses)) {
        send_json([
            'status' => 'error',
            'error' => 'invalid_status',
            'message' => "Cannot cancel order. Order status is '{$currentStatus}'. Only orders with status 'pending_approval' or 'approved' can be cancelled.",
            'data' => [
                'current_status' => $currentStatus,
                'allowed_statuses' => $allowedStatuses
            ]
        ], 400);
    }

    // تحديث حالة الطلب إلى cancelled
    $updateStmt = $con->prepare("UPDATE orders 
                                 SET status = 'cancelled', updated_at = NOW() 
                                 WHERE order_id = ? AND user_id = ?");
    $success = $updateStmt->execute([$order_id, $user_id]);

    if (!$success) {
        send_json([
            'status' => 'error',
            'error' => 'update_failed',
            'message' => 'Failed to cancel order'
        ], 500);
    }

    // إرجاع استجابة النجاح
    send_json([
        'status' => 'success',
        'message' => 'Order cancelled successfully',
        'data' => [
            'order_id' => (int)$order_id,
            'previous_status' => $currentStatus,
            'new_status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ]
    ], 200);
} catch (PDOException $e) {
    send_json([
        'status' => 'error',
        'error' => 'server_error',
        'message' => 'Database error',
        'details' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    send_json([
        'status' => 'error',
        'error' => 'server_error',
        'message' => $e->getMessage()
    ], 500);
}
