<?php
require_once __DIR__ . '/includes/db.php';

$indexes = [
    'idx_cust_pppoe' => "CREATE INDEX idx_cust_pppoe ON customer_tbl (pppoe_status)",
    'idx_cust_router' => "CREATE INDEX idx_cust_router ON customer_tbl (router_id)",
    'idx_cust_username' => "CREATE INDEX idx_cust_username ON customer_tbl (username)",
    'idx_cust_status' => "CREATE INDEX idx_cust_status ON customer_tbl (status)",
    'idx_cust_billing_date' => "CREATE INDEX idx_cust_billing_date ON customer_tbl (billing_date)"
];

$existing_indexes = [];
$res = $conn->query("SHOW INDEX FROM customer_tbl");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $existing_indexes[$row['Key_name']] = true;
    }
}

$created = [];
$skipped = [];

foreach ($indexes as $name => $sql) {
    if (isset($existing_indexes[$name])) {
        $skipped[] = $name;
    } else {
        if ($conn->query($sql)) {
            $created[] = $name;
        } else {
            // Check if column is already covered or error occurred
            echo "Notice on $name: " . $conn->error . "\n";
        }
    }
}

echo "Created indexes: " . (empty($created) ? 'None (all exist)' : implode(', ', $created)) . "\n";
echo "Already existing: " . implode(', ', $skipped) . "\n";

