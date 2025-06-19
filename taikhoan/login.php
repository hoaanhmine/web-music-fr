<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
        } else {
            $error = "Đăng nhập thất bại!";
        }
    } elseif (isset($_POST['register'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $password, $email])) {
            $success = "Đăng ký thành công! Vui lòng đăng nhập.";
        } else {
            $error = "Đăng ký thất bại!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập/Đăng ký</title>
    <style>
        .form-container { width: 300px; margin: 50px auto; text-align: center; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Đăng nhập</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Tên đăng nhập" required><br>
            <input type="password" name="password" placeholder="Mật khẩu" required><br>
            <button type="submit" name="login">Đăng nhập</button>
        </form>

        <h2>Đăng ký</h2>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Tên đăng nhập" required><br>
            <input type="password" name="password" placeholder="Mật khẩu" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <button type="submit" name="register">Đăng ký</button>
        </form>
    </div>
</body>
</html>