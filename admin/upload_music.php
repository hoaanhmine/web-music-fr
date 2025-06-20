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
    $bpm = $_POST['bpm'] ?? 120; // Lấy bpm, mặc định 120 nếu không nhập

    // Tạo thư mục theo tên bài hát trong admin/upload
    $sanitized_title = preg_replace('/[^A-Za-z0-9\-]/', '_', $title);
    $music_dir = 'upload/' . $sanitized_title; // Tương đối từ admin/
    $full_music_dir = $_SERVER['DOCUMENT_ROOT'] . '/admin/' . $music_dir;
    if (!file_exists($full_music_dir)) {
        mkdir($full_music_dir, 0777, true); // Quyền 0777 cho XAMPP
    }

    // Lưu file nhạc
    $file_name = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_path = $music_dir . '/' . $file_name; // Đường dẫn tương đối
    $full_file_path = $_SERVER['DOCUMENT_ROOT'] . '/admin/' . $file_path;
    if (move_uploaded_file($file['tmp_name'], $full_file_path)) {
        error_log("File saved at: $full_file_path");
    } else {
        error_log("Failed to save file: $full_file_path - Error: " . print_r(error_get_last(), true));
    }

    // Tải ảnh từ URL
    $cover_image = $music_dir . '/cover.jpg';
    $full_cover_path = $_SERVER['DOCUMENT_ROOT'] . '/admin/' . $cover_image;
    if ($cover_image_url) {
        $image_content = file_get_contents($cover_image_url);
        if ($image_content !== false) {
            file_put_contents($full_cover_path, $image_content);
        }
    }

    // Tạo file JSON
    $music_info = [
        'title' => $title,
        'composer' => $composer,
        'uploaded_by' => $uploaded_by,
        'file_path' => $file_path,
        'cover_image' => $cover_image ?: null,
        'upload_date' => date('Y-m-d H:i:s'),
        'bpm' => $bpm // Thêm bpm vào JSON
    ];
    file_put_contents($full_music_dir . '/info.json', json_encode($music_info, JSON_PRETTY_PRINT));

    // Thêm vào cơ sở dữ liệu
    $stmt = $pdo->prepare("INSERT INTO musics (title, composer, uploaded_by, cover_image, file_path, bpm) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $composer, $uploaded_by, $cover_image, $file_path, $bpm]);

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
        input[type="text"], input[type="file"], input[type="url"], input[type="number"] { width: 100%; padding: 8px; }
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
            <div class="form-group">
                <label for="bpm">BPM (Nhịp/Phút):</label>
                <input type="number" name="bpm" id="bpm" value="120" min="60" max="500">
            </div>
            <button type="submit">Tải lên</button>
        </form>
        <p><a href="admin_manage_music.php">Quản lý nhạc</a> | <a href="../index.php">Quay lại trang chính</a></p>
    </div>
</body>
</html>