import os

portal_dir = r"c:\xampp\htdocs\billsoft\customerportal"
if not os.path.exists(portal_dir):
    os.makedirs(portal_dir)

includes_dir = os.path.join(portal_dir, "includes")
if not os.path.exists(includes_dir):
    os.makedirs(includes_dir)

files = {
    "login.php": """<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, status FROM customer_tbl WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            if ($customer['status'] !== 'Active') {
                $error = "Your account is inactive. Please contact support.";
            } else {
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_name'] = $customer['name'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Invalid PPPoE username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-box h3 { text-align: center; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h3>Customer Portal</h3>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">PPPoE Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>""",

    "logout.php": """<?php
session_start();
session_destroy();
header("Location: login.php");
exit;
?>""",

    "includes/header.php": """<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}
$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: calc(100vh - 56px); background: #343a40; color: white; padding-top: 1rem; }
        .sidebar a { color: #adb5bd; text-decoration: none; display: block; padding: 0.5rem 1rem; margin-bottom: 0.2rem; border-radius: 0.25rem; }
        .sidebar a:hover, .sidebar a.active { color: white; background: #495057; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Customer Portal</a>
            <div class="d-flex text-white align-items-center">
                <span class="me-3">Welcome, <?= htmlspecialchars($customer_name) ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar">
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a href="payments.php" class="<?= basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : '' ?>"><i class="bi bi-credit-card me-2"></i> Payments</a>
                <a href="notifications.php" class="<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>"><i class="bi bi-bell me-2"></i> Notifications</a>
                <a href="instructions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'instructions.php' ? 'active' : '' ?>"><i class="bi bi-info-circle me-2"></i> Instructions</a>
            </div>
            <div class="col-md-9 col-lg-10 p-4">""",

    "includes/footer.php": """            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>""",

    "index.php": """<?php
include 'includes/header.php';

// Fetch customer info
$stmt = $conn->prepare("SELECT * FROM customer_tbl WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<h2>Dashboard</h2>
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><th>Name:</th><td><?= htmlspecialchars($customer['name']) ?></td></tr>
                    <tr><th>Phone:</th><td><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></td></tr>
                    <tr><th>Email:</th><td><?= htmlspecialchars($customer['email'] ?? 'N/A') ?></td></tr>
                    <tr><th>Address:</th><td><?= htmlspecialchars($customer['address'] ?? 'N/A') ?></td></tr>
                    <tr><th>PPPoE Username:</th><td><?= htmlspecialchars($customer['username']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Account Status</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><th>Status:</th><td>
                        <?php if($customer['status'] == 'Active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><?= htmlspecialchars($customer['status']) ?></span>
                        <?php endif; ?>
                    </td></tr>
                    <tr><th>PPPoE Status:</th><td><?= htmlspecialchars($customer['pppoe_status'] ?? 'enable') ?></td></tr>
                    <tr><th>Previous Due:</th><td>$<?= number_format($customer['previous_due'] ?? 0, 2) ?></td></tr>
                    <tr><th>Package Price:</th><td>$<?= number_format($customer['package_price'] ?? 0, 2) ?></td></tr>
                    <tr><th>Billing Date:</th><td><?= htmlspecialchars($customer['billing_date'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>""",

    "payments.php": """<?php
include 'includes/header.php';

// Fetch payments (Using standard structure, if columns missing they will gracefully fallback or query will fail but standard is amount, discount)
// In billsoft, customer is typically customer_id
$stmt = $conn->prepare("SELECT * FROM payment_tbl WHERE customer_id = ? ORDER BY id DESC");
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $payments = $stmt->get_result();
} else {
    // If something fails
    $payments = false;
}
?>
<h2>Payment Information</h2>
<div class="card shadow-sm mt-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Discount</th>
                        <th>Payment Method</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($payments && $payments->num_rows > 0): ?>
                        <?php while($pay = $payments->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($pay['payment_date'] ?? ($pay['created_at'] ?? 'N/A')) ?></td>
                                <td>$<?= number_format($pay['amount'] ?? $pay['payment_amount'] ?? 0, 2) ?></td>
                                <td>$<?= number_format($pay['discount'] ?? 0, 2) ?></td>
                                <td><?= htmlspecialchars($pay['payment_method'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($pay['note'] ?? $pay['remarks'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No payment records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php 
if ($stmt) $stmt->close();
include 'includes/footer.php'; 
?>""",

    "notifications.php": """<?php
include 'includes/header.php';
?>
<h2>Notifications</h2>
<div class="card shadow-sm mt-4">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i> No new notifications at this time.
        </div>
        <!-- You can expand this feature by querying a notifications table -->
    </div>
</div>
<?php include 'includes/footer.php'; ?>""",

    "instructions.php": """<?php
include 'includes/header.php';
?>
<h2>Instructions</h2>
<div class="card shadow-sm mt-4">
    <div class="card-body">
        <h5>How to pay your bill</h5>
        <p>1. Go to the payments tab.</p>
        <p>2. Review your pending dues.</p>
        <p>3. Use our accepted mobile banking methods to complete payment.</p>
        
        <hr>
        
        <h5>Having Connection Issues?</h5>
        <p>1. Try restarting your router (turn off, wait 10 seconds, turn back on).</p>
        <p>2. Ensure the optical fiber cable is securely plugged in.</p>
        <p>3. If the PON light is red, please contact our support team immediately.</p>
        
        <hr>
        
        <h5>Support Contact</h5>
        <p>Email: support@example.com</p>
        <p>Phone: +1 234 567 890</p>
    </div>
</div>
<?php include 'includes/footer.php'; ?>"""
}

for rel_path, content in files.items():
    full_path = os.path.join(portal_dir, rel_path)
    with open(full_path, "w", encoding="utf-8") as f:
        f.write(content)

print("Customer Portal created successfully.")
