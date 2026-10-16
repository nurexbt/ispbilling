<?php
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = $_POST['db_host'] ?? '127.0.0.1';
    $db_port = $_POST['db_port'] ?? '3306';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';
    $admin_name = $_POST['admin_name'] ?? 'System Administrator';

    if (empty($db_host) || empty($db_user) || empty($db_name) || empty($admin_user) || empty($admin_pass)) {
        $error = "Database details and Admin credentials are required.";
    } else {
        // Test connection
        $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
        
        if ($conn->connect_error) {
            $error = "Database Connection Failed: " . $conn->connect_error;
        } else {
            // Read and execute database.sql
            $sql_file = __DIR__ . '/database.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                
                // Execute multi query
                if ($conn->multi_query($sql)) {
                    do {
                        // Store first result set
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                    } while ($conn->more_results() && $conn->next_result());
                    
                    // Now insert Sysadmin Role, Employee and Portal User
                    $admin_role_id = 0;
                    
                    // Check if SysAdmin role exists
                    $role_res = $conn->query("SELECT id FROM role_tbl WHERE role_name = 'SysAdmin'");
                    if ($role_res && $role_res->num_rows > 0) {
                        $admin_role_id = $role_res->fetch_assoc()['id'];
                    } else {
                        // Create role with full access
                        $permissions = json_encode(['global_full_access'=>1, 'owner_full_access'=>1, 'noc_full_access'=>1, 'transmission_full_access'=>1, 'manager_full_access'=>1, 'billing_full_access'=>1]);
                        $stmt = $conn->prepare("INSERT INTO role_tbl (role_name, permissions) VALUES ('SysAdmin', ?)");
                        if ($stmt) {
                            $stmt->bind_param("s", $permissions);
                            $stmt->execute();
                            $admin_role_id = $stmt->insert_id;
                            $stmt->close();
                        }
                    }
                    
                    // Create Employee record for sysadmin
                    $emp_id = 0;
                    $stmt = $conn->prepare("INSERT INTO employee_tbl (full_name, designation) VALUES (?, 'System Administrator')");
                    if ($stmt) {
                        $stmt->bind_param("s", $admin_name);
                        $stmt->execute();
                        $emp_id = $stmt->insert_id;
                        $stmt->close();
                    }
                    
                    // Create portal user
                    $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                    
                    // Check if user already exists
                    $user_check = $conn->prepare("SELECT id FROM portal_user_tbl WHERE username = ?");
                    if ($user_check) {
                        $user_check->bind_param("s", $admin_user);
                        $user_check->execute();
                        $user_check->store_result();
                        if ($user_check->num_rows > 0) {
                            // Update existing user
                            $upd = $conn->prepare("UPDATE portal_user_tbl SET password = ?, role_id = ?, employee_id = ? WHERE username = ?");
                            $upd->bind_param("siis", $hashed_pass, $admin_role_id, $emp_id, $admin_user);
                            $upd->execute();
                            $upd->close();
                        } else {
                            // Insert new user
                            $ins = $conn->prepare("INSERT INTO portal_user_tbl (employee_id, username, password, role_id, is_active) VALUES (?, ?, ?, ?, 1)");
                            if ($ins) {
                                $ins->bind_param("issi", $emp_id, $admin_user, $hashed_pass, $admin_role_id);
                                $ins->execute();
                                $ins->close();
                            }
                        }
                        $user_check->close();
                    }

                    // Write db.php
                    $db_config = "<?php\n"
                               . "\$host = \"$db_host\";\n"
                               . "\$user = \"$db_user\";\n"
                               . "\$password = \"$db_pass\";\n"
                               . "\$database = \"$db_name\";\n"
                               . "\$port = $db_port;\n\n"
                               . "\$conn = new mysqli(\$host, \$user, \$password, \$database, \$port);\n\n"
                               . "if (\$conn->connect_error) {\n"
                               . "    die(\"Connection failed: \" . \$conn->connect_error);\n"
                               . "}\n"
                               . "?>";
                    
                    if (file_put_contents(__DIR__ . '/includes/db.php', $db_config)) {
                        $success = "Database deployed and System Administrator created successfully! Please delete install.php for security.";
                    } else {
                        $error = "Database deployed, but failed to write includes/db.php. Please check file permissions.";
                    }
                } else {
                    $error = "Error executing SQL: " . $conn->error;
                }
            } else {
                $error = "database.sql file not found in the root directory.";
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BillSoft - System Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 0; }
        .install-card { width: 100%; max-width: 600px; padding: 2rem; background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .section-title { font-size: 1.1rem; font-weight: 600; color: #495057; border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem; margin-bottom: 1.5rem; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="install-card">
        <h3 class="text-center mb-4">Linko Communication Setup</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?><br><br>
                <a href="login.php" class="btn btn-primary w-100">Go to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                
                <div class="section-title">Database Configuration</div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Database Host</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Port</label>
                        <input type="number" name="db_port" class="form-control" value="3306" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Database Name</label>
                    <input type="text" name="db_name" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Database Username</label>
                        <input type="text" name="db_user" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Database Password</label>
                        <input type="password" name="db_pass" class="form-control">
                    </div>
                </div>

                <div class="section-title">System Administrator Account</div>
                <div class="mb-3">
                    <label class="form-label">Admin Full Name</label>
                    <input type="text" name="admin_name" class="form-control" value="System Administrator" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Admin Username</label>
                        <input type="text" name="admin_user" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Admin Password</label>
                        <input type="password" name="admin_pass" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3">Deploy & Install System</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
