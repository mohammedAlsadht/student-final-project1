<?php
session_start();
require 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // استعلام مباشر وآمن
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND password = ?');
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول | المتجر الفخم</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; background: radial-gradient(circle, #1a1a1a 0%, #0a0a0a 100%); font-family: 'Cairo', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; color: #fff; }
        .login-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(212, 175, 55, 0.2); padding: 40px; border-radius: 15px; width: 350px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.5), 0 0 20px rgba(212, 175, 55, 0.05); backdrop-filter: blur(10px); }
        .login-card h2 { color: #d4af37; margin-bottom: 30px; font-weight: 700; letter-spacing: 1px; }
        .form-group { margin-bottom: 20px; text-align: right; }
        .form-group label { display: block; margin-bottom: 8px; color: #ccc; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #222; color: #fff; box-sizing: border-box; transition: 0.3s; font-family: 'Cairo'; }
        .form-group input:focus { border-color: #d4af37; outline: none; box-shadow: 0 0 8px rgba(212, 175, 55, 0.3); }
        .btn-login { width: 100%; padding: 12px; background: linear-gradient(45deg, #b8860b, #d4af37); border: none; border-radius: 8px; color: #000; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 16px; font-family: 'Cairo'; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4); }
        .error { color: #ff4d4d; font-size: 14px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>المتجر الملكي</h2>
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
            <button type="submit" class="btn-login">تسجيل الدخول</button>
        </form>
    </div>
</body>
</html>