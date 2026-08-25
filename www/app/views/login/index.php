<?php
/**
 * Login Page
 */
$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — تاجر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #152d54 50%, #0f2140 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #0f2140 0%, #0ea5a0 100%);
            padding: 36px 30px 28px;
            text-align: center;
            color: #fff;
        }
        .login-header .logo-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .login-header .logo-circle img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }
        .login-header h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .login-header p {
            font-size: 13px;
            opacity: 0.8;
        }
        .login-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1a2332;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            color: #1a2332;
            transition: all 0.2s;
            outline: none;
            direction: rtl;
        }
        .form-group input:focus {
            border-color: #0ea5a0;
            box-shadow: 0 0 0 3px rgba(14, 165, 160, 0.12);
        }
        .form-group input::placeholder {
            color: #8899aa;
        }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0ea5a0 0%, #14c8c1 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 4px;
            box-shadow: 0 4px 15px rgba(14, 165, 160, 0.3);
        }
        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(14, 165, 160, 0.4);
        }
        .login-btn:active {
            transform: translateY(0);
        }
        .error-msg {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
            text-align: center;
        }
        .login-footer {
            text-align: center;
            padding: 0 30px 24px;
            font-size: 11px;
            color: #8899aa;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-circle">
                    <img src="public/images/logo.png" alt="" onerror="this.style.display='none';this.parentNode.innerHTML='<svg width=\'32\' height=\'32\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'white\' stroke-width=\'2\'><path d=\'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z\'></path><polyline points=\'9 22 9 12 15 12 15 22\'></polyline></svg>';">
                </div>
                <h1>تاجر</h1>
                <p>نظام نقاط البيع وإدارة المخزون</p>
            </div>
            <div class="login-body">
                <?php if ($loginError): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($loginError); ?></div>
                <?php endif; ?>

                <form method="POST" action="?page=login&action=authenticate">
                    <div class="form-group">
                        <label>اسم المستخدم</label>
                        <input type="text" name="username" placeholder="أدخل اسم المستخدم" required autofocus autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور</label>
                        <input type="password" name="password" placeholder="أدخل كلمة المرور" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="login-btn">تسجيل الدخول</button>
                </form>
            </div>
            <div class="login-footer">
                تاجر &copy; <?php echo date('Y'); ?>
            </div>
        </div>
    </div>
</body>
</html>
