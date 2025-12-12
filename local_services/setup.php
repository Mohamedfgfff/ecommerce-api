<?php
include "../connect.php";

$table1 = "
CREATE TABLE IF NOT EXISTS local_services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    service_desc TEXT NOT NULL,
    service_image VARCHAR(255) NOT NULL,
    service_price DECIMAL(10,2) NOT NULL,
    service_city VARCHAR(100) NOT NULL,
    service_phone VARCHAR(50) NOT NULL,
    service_visible TINYINT(1) DEFAULT 1,
    service_create_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$table2 = "
CREATE TABLE IF NOT EXISTS orders_services (
    orders_services_id INT AUTO_INCREMENT PRIMARY KEY,
    order_user_id INT NOT NULL,
    order_service_id INT NOT NULL,
    order_status VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, approved, declined, completed, cancelled',
    order_note TEXT,
    order_address_id INT NOT NULL,
    order_create_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_service_id) REFERENCES local_services(service_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $con->exec($table1);
    $con->exec($table2);
    echo json_encode(array("status" => "success", "message" => "Tables created successfully"));
} catch (PDOException $e) {
    echo json_encode(array("status" => "fail", "message" => $e->getMessage()));
}
