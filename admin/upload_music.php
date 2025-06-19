<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $composer = $_POST['composer'] ?? null;
    $uploaded_by = $_SESSION['user_id'];
    $cover_image_url = $_POST['cover_image_url'] ?? null;
    $file = $_FILES['musicFile'];

    // Tạo thư mục theo tên bài hát
    $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '_', $title);
    $music_dir = 'upload/' . $sanitized_title;
    if (!file_exists($music_dir)) {
        mkdir($music_dir, 0775, true);
    }

    // Lưu file nhạc
    $file_path = $music_dir . '/' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    move_uploaded_file($file['tmp_name'], $file_path);

    // Tải ảnh từ URL
    $cover_image = $music_dir . '/cover.jpg';
    if ($cover_image_url) {
        $image_content = file_get_contents($cover_image_url);
        if ($image_content !== false) {
            file_put_contents($cover_image, $image_content);
        }
    }

    // Tạo file JSON
    $music_info = [
        'title' => $title,
        'composer' => $composer,
        'uploaded_by' => $uploaded_by,
        'file_path' => $file_path,
        'cover_image' => basename($cover_image) ?: null,
        'upload_date' => date('Y-m-d H:i:s')
    ];
    file_put_contents($music_dir . '/info.json', json_encode($music_info, JSON_PRETTY_PRINT));

    $stmt = $pdo->prepare("INSERT INTO musics (title, composer, uploaded_by, cover_image, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $composer, $uploaded_by, $cover_image, $file_path]);

    header("Location: upload_music.php?success=1");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tải Nhạc Lên</title>
    <style>
        .upload-container { width: 400px; margin: 50px auto; text-align: center; }
        .form-group { margin-bottom: 15px; text-align: left; }
        input[type="text"], input[type="file"], input[type="url"] { width: 100%; padding: 8px; }
        button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="upload-container">
        <h2>Tải Nhạc Lên</h2>
        <?php if (isset($_GET['success'])) echo "<p class='success'>Tải lên thành công!</p>"; ?>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Tên bài hát:</label>
                <input type="text" name="title" id="title" required>
            </div>
            <div class="form-group">
                <label for="composer">Tác giả:</label>
                <input type="text" name="composer" id="composer">
            </div>
            <div class="form-group">
                <label for="cover_image_url">Link ảnh bìa:</label>
                <input type="url" name="cover_image_url" id="cover_image_url" placeholder="https://example.com/image.jpg">
            </div>
            <div class="form-group">
                <label for="musicFile">Tệp nhạc (.ogg, .wav):</label>
                <input type="file" name="musicFile" id="musicFile" accept=".ogg,.wav" required>
            </div>
            <button type="submit">Tải lên</button>
        </form>
        <p><a href="manage_music.php">Quản lý nhạc</a> | <a href="../index.php">Quay lại trang chính</a></p>
    </div>
</body>
</html>