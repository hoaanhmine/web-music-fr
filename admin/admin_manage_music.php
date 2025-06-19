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
            unlink($music['file_path']);
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
        header("Location: manage_music.php?success=1");
    }
}

$stmt = $pdo->query("SELECT * FROM musics");
$musics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Nhạc</title>
    <style>
        .manage-container { width: 600px; margin: 50px auto; }
        .music-item { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
        .form-group { margin-bottom: 10px; }
        input[type="text"] { padding: 5px; }
        button { padding: 5px 10px; margin-right: 5px; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="manage-container">
        <h2>Quản lý Nhạc</h2>
        <?php if (isset($_GET['success'])) echo "<p class='success'>Cập nhật thành công!</p>"; ?>
        <?php foreach ($musics as $music): ?>
            <div class="music-item">
                <form method="post" action="">
                    <input type="hidden" name="id" value="<?php echo $music['id']; ?>">
                    <div class="form-group">
                        <label>Tên bài hát:</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($music['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tác giả:</label>
                        <input type="text" name="composer" value="<?php echo htmlspecialchars($music['composer'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="update">Cập nhật</button>
                    <button type="submit" name="delete" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</button>
                </form>
            </div>
        <?php endforeach; ?>
        <p><a href="upload_music.php">Tải nhạc lên</a> | <a href="../index.php">Quay lại trang chính</a></p>
    </div>
</body>
</html>