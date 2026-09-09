<?php
session_start();

// الاتصال المباشر والمستقل بقاعدة البيانات
$host = 'localhost';
$db   = 'luxury_shop';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$error = '';

try {
     $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES   => false,
     ]);
} catch (\PDOException $e) {
     $error = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND password = ?');
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = '⚠️ رمز الوصول أو اسم المستخدم غير صحيح!';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول | متجر الهكر</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <link rel="stylesheet" href="https://cloudflare.com">
    <style>
        body { margin: 0; padding: 0; background: radial-gradient(circle, #0d1117 0%, #05070a 100%); font-family: 'Cairo', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; color: #fff; }
        .login-card { background: rgba(10, 25, 20, 0.6); border: 1px solid rgba(0, 255, 136, 0.2); padding: 40px; border-radius: 15px; width: 350px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.7), 0 0 20px rgba(0, 255, 136, 0.05); backdrop-filter: blur(10px); }
        .login-card h2 { color: #00ff88; margin-bottom: 30px; font-weight: 700; letter-spacing: 1px; text-shadow: 0 0 10px rgba(0, 255, 136, 0.3); }
        .form-group { margin-bottom: 20px; text-align: right; }
        .form-group label { display: block; margin-bottom: 8px; color: #8b949e; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #30363d; background: #161b22; color: #fff; box-sizing: border-box; transition: 0.3s; font-family: 'Cairo'; }
        .form-group input:focus { border-color: #00ff88; outline: none; box-shadow: 0 0 8px rgba(0, 255, 136, 0.3); }
        .btn-login { width: 100%; padding: 12px; background: linear-gradient(45deg, #00b43d, #00ff88); border: none; border-radius: 8px; color: #05070a; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 16px; font-family: 'Cairo'; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 136, 0.4); }
        .error { color: #ff4d4d; font-size: 14px; margin-bottom: 15px; text-align: center; background: rgba(255,77,77,0.1); padding: 10px; border-radius: 6px; border: 1px solid rgba(255,77,77,0.2); }
    </style>
</head>
<body>
    <div class="login-card">
        <h2><i class="fa-solid fa-terminal"></i> متجر الهكر</h2>
        <?php if($error): ?> <div class="error"><?= $error ?></div> <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" required placeholder="admin">
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-login">تسجيل الدخول بالنظام</button>
        </form>
    </div>
</body>
</html>