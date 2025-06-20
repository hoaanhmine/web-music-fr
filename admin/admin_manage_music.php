<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT file_path, cover_image FROM musics WHERE id = ?");
        $stmt->execute([$id]);
        $music = $stmt->fetch();
        if ($music) {
            if (file_exists($music['file_path'])) {
                unlink($music['file_path']);
            }
            if ($music['cover_image'] && file_exists($music['cover_image'])) {
                unlink($music['cover_image']);
            }
            $dir = dirname($music['file_path']);
            if (is_dir($dir) && count(scandir($dir)) <= 2) {
                rmdir($dir);
            }
            $stmt = $pdo->prepare("DELETE FROM musics WHERE id = ?");
            $stmt->execute([$id]);
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $title = $_POST['title'];
        $composer = $_POST['composer'] ?? null;
        $stmt = $pdo->prepare("UPDATE musics SET title = ?, composer = ? WHERE id = ?");
        $stmt->execute([$title, $composer, $id]);
        header("Location: admin_manage_music.php?success=1");
        exit();
    }
}

$stmt = $pdo->query("SELECT * FROM musics ORDER BY upload_date DESC");
$musics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>  
    <meta charset="UTF-8">
    <title>Quản lý Nhạc</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f6f6;
            margin: 0;
            padding: 0;
        }
        .manage-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .music-item {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            align-items: center;
        }
        .music-cover {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            background-color: #eee;
        }
        .music-info {
            flex: 1;
        }
        .music-info input[type="text"] {
            width: 100%;
            padding: 6px 8px;
            margin-bottom: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .music-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        button[name="update"] {
            background-color: #007bff;
            color: #fff;
        }
        button[name="delete"] {
            background-color: #dc3545;
            color: #fff;
        }
        .success {
            color: green;
            text-align: center;
            margin-bottom: 20px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        .bottom-links {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="manage-container">
        <h2>🎵 Quản lý Nhạc</h2>
        <?php if (isset($_GET['success'])) echo "<p class='success'>✅ Cập nhật thành công!</p>"; ?>

        <?php foreach ($musics as $music): ?>
            <div class="music-item">
                <img class="music-cover" src="<?php echo $music['cover_image'] && file_exists($music['cover_image']) ? $music['cover_image'] : 'https://via.placeholder.com/100?text=No+Cover'; ?>" alt="Ảnh bìa">
                
                <form method="post" class="music-info">
                    <input type="hidden" name="id" value="<?php echo $music['id']; ?>">

                    <label>Tên bài hát:</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($music['title']); ?>" required>

                    <label>Tác giả:</label>
                    <input type="text" name="composer" value="<?php echo htmlspecialchars($music['composer'] ?? ''); ?>">

                    <div class="music-meta">
                        Ngày tải lên: <?php echo date('d/m/Y H:i', strtotime($music['upload_date'])); ?> <br>
                        BPM: <?php echo htmlspecialchars($music['bpm'] ?? 120); ?>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" name="update">Sửa</button>
                        <button type="submit" name="delete" onclick="return confirm('Bạn có chắc muốn xóa bài này?')">Xóa</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="bottom-links">
            <p><a href="upload_music.php">⬆️ Tải nhạc lên</a> | <a href="../index.php">🏠 Trang chính</a></p>
        </div>
    </div>
</body>
</html>
