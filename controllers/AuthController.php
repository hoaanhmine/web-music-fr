<?php

namespace App\Controllers;

use App\Models\User;

class AuthController
{
    private $db;

    public function __construct()
    {
        // Khởi tạo kết nối cơ sở dữ liệu
        require_once __DIR__ . '../config/database.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // Tạo instance của User model
            $userModel = new User($this->db);
            $user = $userModel->findByUsername($username);

            if ($user && password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: ../index.php"); // Chuyển hướng sau khi đăng nhập thành công
                exit();
            } else {
                // Lưu lỗi để hiển thị trên view
                $_SESSION['error'] = "Invalid username or password!";
                header("Location: ../views/auth/login.php");
                exit();
            }
        }
    }

    public function register()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT);

            // Tạo instance của User model
            $userModel = new User($this->db);
            if ($userModel->create($username, $email, $password)) {
                session_start();
                $_SESSION['success'] = "Registration successful! Please log in.";
                header("Location: ../views/auth/login.php");
                exit();
            } else {
                $_SESSION['error'] = "Registration failed. Username or email may already exist!";
                header("Location: ../views/auth/register.php");
                exit();
            }
        }
    }
}