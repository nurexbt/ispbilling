<?php
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "billsoft_db";
$port = 3307;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-check and ensure connection_request_tbl has customer_id & request_type columns (e.g. on new server deploy)
if (empty($_SESSION['__cr_cols_verified'])) {
    try {
        $chk_cr = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'customer_id'");
        if ($chk_cr && $chk_cr->num_rows === 0) {
            @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN customer_id INT(11) DEFAULT NULL AFTER pppoe_username");
        }
        $chk_rt = $conn->query("SHOW COLUMNS FROM connection_request_tbl LIKE 'request_type'");
        if ($chk_rt && $chk_rt->num_rows === 0) {
            @$conn->query("ALTER TABLE connection_request_tbl ADD COLUMN request_type VARCHAR(50) DEFAULT 'new_connection' AFTER customer_id");
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['__cr_cols_verified'] = true;
        }
    } catch (Throwable $e) {
        // Suppress to ensure db connection continues smoothly
    }
}
?>
