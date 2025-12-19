<?php

/**
 * cart_quantity.php
 * Checks the current quantity of a product in the user's cart and its favorite status.
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Buffer to catch any accidental output from included files to prevent JSON corruption
ob_start();
include_once "../connect.php";
include_once "../functions.php";
$unintended_output = ob_get_clean();

// Log unintended output if any exists
if (!empty($unintended_output)) {
    $log_message = "[" . date('Y-m-d H:i:s') . "] EXTRA OUTPUT:\n" . $unintended_output . "\n---\n";
    file_put_contents(__DIR__ . '/debug_extra_output.log', $log_message, FILE_APPEND);
}

/**
 * Sends a standardized JSON response and terminates execution.
 * 
 * @param string $status  "success" or "fail"
 * @param array  $data    Optional data to include in the response
 * @param string $message Optional message describing the status
 */
function send_response($status, $data = [], $message = null)
{
    if (ob_get_length()) ob_clean(); // Discard any buffered output

    $response = ["status" => $status];
    if ($message !== null) $response["message"] = $message;
    if (!empty($data)) $response = array_merge($response, $data);

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

// Input Retrieval (Supports JSON raw body, $_POST, and $_GET)
$json_raw = file_get_contents('php://input');
$json_data = json_decode($json_raw, true) ?? [];

/**
 * Helper to fetch input values from various sources.
 */
function get_input_field($key, $json_data)
{
    if (isset($json_data[$key])) return $json_data[$key];
    if (isset($_POST[$key]))    return $_POST[$key];
    if (isset($_REQUEST[$key])) return $_REQUEST[$key];
    return null;
}

// 1. Collect and Validate Required Fields
$user_id    = get_input_field('user_id', $json_data);
$product_id = get_input_field('product_id', $json_data);
$attr_input = get_input_field('attributes', $json_data);

if (empty($user_id) || empty($product_id) || ($attr_input === null || $attr_input === '')) {
    send_response("fail", [], "Missing required fields: user_id, product_id, and attributes are required.");
}

// 2. Database Connection Check
if (!isset($con) || !($con instanceof PDO)) {
    send_response("fail", [], "Database connection is unavailable.");
}

// 3. Prepare Attributes for Query
// Normalize attributes: if it's a JSON string, decode it first.
// Then re-encode it to ensure the JSON format matches exactly what was stored by cart_add.php.
$attributes = $attr_input;
if (is_string($attributes) && $attributes !== '') {
    $decoded = json_decode($attributes, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $attributes = $decoded;
    }
}
$attributes_for_query = is_array($attributes) ? json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $attributes;

try {
    /**
     * Optimized query to fetch:
     * - cart_quantity: Current count in user's cart (returns null if not found)
     * - in_favorite: Boolean existence in favorites table
     */
    $sql = "
        SELECT 
            (SELECT `cart_quantity` 
             FROM `cart` 
             WHERE `cart_user_id` = :uid 
             AND `cart_product_id` = :pid 
             AND `cart_attributes` = :attr 
             LIMIT 1) AS cart_quantity, 
            EXISTS(
             SELECT 1 
             FROM `favorites` 
             WHERE `favorite_user_id` = :uid 
             AND `favorite_product_id` = :pid) AS in_favorite
    ";

    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':uid'  => $user_id,
        ':pid'  => $product_id,
        ':attr' => $attributes_for_query
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Cast results to appropriate types
    $quantity    = isset($row['cart_quantity']) ? (int)$row['cart_quantity'] : 0;
    $in_favorite = isset($row['in_favorite'])   ? (bool)$row['in_favorite']   : false;

    send_response("success", [
        "cart_quantity" => $quantity,
        "in_favorite"   => $in_favorite
    ]);
} catch (PDOException $e) {
    // Log the error for internal tracking
    error_log("Database Error in cart_quantity.php: " . $e->getMessage());

    // Provide a generic fail message to the client, but include error details if in debug mode
    send_response("fail", ["error" => $e->getMessage()], "A database error occurred.");
}
