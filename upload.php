<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    $title = $_POST['title'];
    $composer = $_POST['composer'];
    $uploaded_by = $_SESSION['user_id'];
    $file = $_FILES['musicFile'];

    $fileName = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filePath = 'upload/' . $fileName;
    move_uploaded_file($file['tmp_name'], $filePath);

    $stmt = $pdo->prepare("INSERT INTO musics (title, composer, uploaded_by, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $composer, $uploaded_by, $filePath]);

    header("Location: index.php");
}
?>