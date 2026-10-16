<?php
$base_path = '';
require_once 'includes/db.php';
require_once 'includes/auth.php'; // Will redirect if already logged in? 
// Handled by auth.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Linko Communication</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, rgba(14, 165, 233, 0.05) 0, transparent 50%), 
                radial-gradient(at 100% 0%, rgba(99, 102, 241, 0.1) 0, transparent 50%);
            background-size: cover;
            background-attachment: fixed;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .login-wrapper {
            position: relative;
            width: 100%;
            max-width: 380px;
            padding: 1rem;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 1);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px -5px rgba(14, 165, 233, 0.12);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0ea5e9, #3b82f6, #2563eb);
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            color: white;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 20px -5px rgba(37, 99, 235, 0.3);
            letter-spacing: -1px;
        }

        .form-control {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            color: #334155 !important;
            border-radius: 10px;
            padding: 0.7rem 1rem 0.7rem 2.6rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 2px 0 rgba(0, 0, 0, 0.02);
        }

        .form-control:focus {
            background-color: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 1rem;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 5;
            transition: color 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus ~ .input-icon {
            color: #3b82f6;
        }

        .form-label {
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px 0 rgba(37, 99, 235, 0.25);
            margin-top: 0.5rem;
        }

        .btn-primary:hover, .btn-primary:focus {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }

        .alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #ef4444;
            border-radius: 10px;
            font-size: 0.8rem;
            padding: 0.6rem 1rem !important;
            display: flex;
            align-items: center;
            margin-bottom: 1rem !important;
        }
        
        /* Glowing orbs for background tech vibe */
        .glow-orb-1 {
            position: fixed;
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(56,189,248,0.2) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            z-index: 1;
            pointer-events: none;
        }
        
        .glow-orb-2 {
            position: fixed;
            bottom: -10%;
            right: -10%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            z-index: 1;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="glow-orb-1"></div>
<div class="glow-orb-2"></div>

<div class="login-wrapper">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-logo">LC</div>
            <h4 class="fw-bold text-slate-800 mb-1" style="letter-spacing: -0.3px;">Linko Communication</h4>
            <p class="text-slate-500 mb-0" style="font-size: 13px;">Secure Portal Authentication</p>
        </div>

        <div id="loginAlert" class="alert alert-danger d-none mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span id="loginAlertText"></span>
        </div>

        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group-custom">
                    <input type="text" name="username" class="form-control shadow-none" required placeholder="Enter username" autocomplete="username">
                    <i class="bi bi-person input-icon"></i>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group-custom">
                    <input type="password" name="password" class="form-control shadow-none" required placeholder="Enter password" autocomplete="current-password">
                    <i class="bi bi-shield-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-2"></i> Authenticate
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        
        $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Authenticating...');
        $btn.prop('disabled', true);
        $('#loginAlert').addClass('d-none');
        
        $.ajax({
            url: 'ajax/login_action.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $btn.html('<i class="bi bi-check-circle me-2"></i>Access Granted');
                    $btn.removeClass('btn-primary').addClass('btn-success');
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 500);
                } else {
                    $('#loginAlertText').text(response.message);
                    $('#loginAlert').removeClass('d-none');
                    $btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                $('#loginAlertText').text('Server communication error. Please try again.');
                $('#loginAlert').removeClass('d-none');
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>

</body>
</html>
