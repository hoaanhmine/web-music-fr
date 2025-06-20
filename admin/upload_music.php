<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tải Nhạc Lên</title>
    <style>
        body { font-family: Arial; background-color: #f7f7f7; }
        .upload-container {
            width: 420px; margin: 50px auto; background: white;
            padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="url"], input[type="number"], input[type="file"] {
            width: 100%; padding: 8px; box-sizing: border-box;
        }
        button {
            background-color: #28a745; color: white; padding: 10px 20px;
            border: none; border-radius: 5px; cursor: pointer;
        }
        button:hover { background-color: #218838; }
        .success { color: green; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="upload-container">
    <h2>Upload Nhạc</h2>
    <?php if (isset($_GET['success'])): ?>
        <p class="success">✅ Tải nhạc thành công!</p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
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
            <input type="url" name="cover_image_url" id="cover_image_url" placeholder="https://example.com/cover.jpg">
        </div>
        <div class="form-group">
            <label for="musicFile">Tệp nhạc (.ogg, .wav):</label>
            <input type="file" name="musicFile" id="musicFile" accept=".ogg,.wav" required>
        </div>
        <div class="form-group">
            <label for="bpm">BPM (nhịp/phút):</label>
            <input type="number" name="bpm" id="bpm" value="120" min="60" max="500">
        </div>
        <button type="submit">Tải lên</button>
    </form>
    <p><a href="admin_manage_music.php">🎵 Quản lý nhạc</a> | <a href="../index.php">🏠 Trang chính</a></p>
</div>
</body>
</html>



<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $composer = trim($_POST['composer'] ?? '');
    $uploaded_by = $_SESSION['user_id'];
    $cover_image_url = trim($_POST['cover_image_url'] ?? '');
    $bpm = intval($_POST['bpm'] ?? 120);

    // Kiểm tra tệp nhạc
    if (!isset($_FILES['musicFile']) || $_FILES['musicFile']['error'] !== UPLOAD_ERR_OK) {
        die("❌ Lỗi upload file nhạc.");
    }

    // Tạo thư mục lưu nhạc
    $sanitized_title = preg_replace('/[^A-Za-z0-9_\-]/', '_', $title);
    $relative_dir = 'upload/' . $sanitized_title;
    $base_dir = __DIR__ . '/' . $relative_dir;

    if (!is_dir($base_dir)) {
        if (!mkdir($base_dir, 0777, true)) {
            die("❌ Không thể tạo thư mục: $base_dir");
        }
    }

    // Lưu file nhạc
    $file = $_FILES['musicFile'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_name = uniqid('music_', true) . '.' . $ext;
    $relative_file_path = $relative_dir . '/' . $unique_name;
    $full_file_path = $base_dir . '/' . $unique_name;

    if (!move_uploaded_file($file['tmp_name'], $full_file_path)) {
        die("❌ Không thể lưu tệp nhạc.");
    }

    // Tải ảnh bìa nếu có
    $relative_cover_path = null;
    if (!empty($cover_image_url)) {
        $image_data = @file_get_contents($cover_image_url);
        if ($image_data !== false) {
            $relative_cover_path = $relative_dir . '/cover.jpg';
            $full_cover_path = $base_dir . '/cover.jpg';
            file_put_contents($full_cover_path, $image_data);
        }
    }

    // Ghi file info.json
    $info = [
        'title' => $title,
        'composer' => $composer,
        'uploaded_by' => $uploaded_by,
        'file_path' => $relative_file_path,
        'cover_image' => $relative_cover_path,
        'upload_date' => date('Y-m-d H:i:s'),
        'bpm' => $bpm
    ];
    file_put_contents($base_dir . '/info.json', json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Ghi vào CSDL
    $stmt = $pdo->prepare("INSERT INTO musics (title, composer, uploaded_by, cover_image, file_path, bpm) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $composer, $uploaded_by, $relative_cover_path, $relative_file_path, $bpm]);

    header("Location: upload_music.php?success=1");
    exit();
}
?>
