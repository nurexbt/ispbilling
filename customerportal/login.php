<?php
session_start();
require_once '../includes/db.php';
$base_path = "../";

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
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            height: 100vh;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            background: linear-gradient(135deg, #e0e7ff 0%, #dbeafe 100%);
            position: relative;
            overflow: hidden;
        }
        
        /* Decorative Background Elements */
        body::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }
        body::after {
            content: '';
            position: absolute;
            bottom: -15%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            z-index: 1;
        }

        .login-card { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 3rem 2.5rem; 
            border-radius: 24px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.05); 
            border: 1px solid rgba(255,255,255,0.5);
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
            color: white;
            font-size: 2rem;
        }

        .login-card h4 { 
            text-align: center; 
            margin-bottom: 0.5rem; 
            font-weight: 800; 
            color: #0f172a; 
            letter-spacing: -0.5px;
        }
        
        .login-card p.subtitle { 
            text-align: center; 
            margin-bottom: 2.5rem; 
            color: #64748b;
            font-size: 0.95rem; 
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group-custom i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .form-control-custom {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 3rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            color: #334155;
            transition: all 0.3s ease;
        }

        .form-control-custom:focus {
            outline: none;
            background-color: #fff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-control-custom:focus + i, 
        .input-group-custom:focus-within i {
            color: #3b82f6;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.5rem;
            display: block;
        }

        .btn-login {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.9rem;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
            width: 100%;
            box-shadow: 0 8px 15px rgba(59, 130, 246, 0.25);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(59, 130, 246, 0.35);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert-custom {
            border-radius: 12px;
            font-size: 0.9rem;
            border: none;
            background-color: #fef2f2;
            color: #ef4444;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="icon-circle">
                <i class="bi bi-wifi"></i>
            </div>
            <h4>Welcome Back</h4>
            <p class="subtitle">Sign in to your Customer Portal</p>
            
            <?php if($error): ?>
                <div class="alert alert-custom mb-4 px-3 py-2">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">PPPoE Username</label>
                    <div class="input-group-custom">
                        <input type="text" name="username" class="form-control-custom" placeholder="Enter your username" required>
                        <i class="bi bi-person-fill"></i>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group-custom">
                        <input type="password" name="password" class="form-control-custom" placeholder="Enter your password" required>
                        <i class="bi bi-lock-fill"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    Sign In <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </form>
        </div>
    </div>
</body>
</html>