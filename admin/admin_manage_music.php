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

        // Lấy dữ liệu cũ
        $stmt = $pdo->prepare("SELECT title, composer FROM musics WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch();

        if ($old && ($old['title'] !== $title || $old['composer'] !== $composer)) {
            // Chỉ update nếu có thay đổi
            $stmt = $pdo->prepare("UPDATE musics SET title = ?, composer = ? WHERE id = ?");
            $stmt->execute([$title, $composer, $id]);
            header("Location: admin_manage_music.php?success=1");
            exit();
        } else {
            // Không thay đổi, không báo thành công
            header("Location: admin_manage_music.php?nochange=1");
            exit();
        }
    }
}

$stmt = $pdo->query("SELECT * FROM musics ORDER BY upload_date DESC");
$musics = $stmt->fetchAll();
?>

<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>  
    <meta charset="UTF-8">
    <title>Quản lý Nhạc</title>
    <link rel="stylesheet" href="../css/admin_manage_music.css">
</head>
<body>
    <div class="manage-container">
        <h2>🎵 Quản lý Nhạc</h2>
        <?php if (isset($_GET['success'])) echo "<p class='success'>✅ Cập nhật thành công!</p>"; ?>
        <?php if (isset($_GET['nochange'])) echo "<p class='success'>ℹ️ Không có thay đổi nào!</p>"; ?>

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

                    <a href="edit_music.php?id=<?php echo $music['id']; ?>" class="edit-link">✏️ Sửa chi tiết</a>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="bottom-links">
            <p><a href="upload_music.php">⬆️ Tải nhạc lên</a> | <a href="../index.php">🏠 Trang chính</a></p>
        </div>
    </div>
</body>
</html>
<?php
$content = ob_get_clean();
$title = "Quản lý nhạc";
include '../views/admin_layout.php';
?>
