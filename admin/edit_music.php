<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "Thiếu ID bài nhạc!";
    exit();
}

// Lấy thông tin nhạc
$stmt = $pdo->prepare("SELECT * FROM musics WHERE id = ?");
$stmt->execute([$id]);
$music = $stmt->fetch();
if (!$music) {
    echo "Không tìm thấy bài nhạc!";
    exit();
}

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $composer = $_POST['composer'];
    $bpm = $_POST['bpm'];
    // Xử lý upload ảnh bìa mới nếu có
    $cover_image = $music['cover_image'];
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['size'] > 0) {
        $target = 'uploads/covers/' . uniqid() . '_' . basename($_FILES['cover_image']['name']);
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], '../' . $target)) {
            // Xóa ảnh cũ nếu có
            if ($cover_image && file_exists('../' . $cover_image)) unlink('../' . $cover_image);
            $cover_image = $target;
        }
    }
    // Xử lý upload file nhạc mới nếu có
    $file_path = $music['file_path'];
    if (isset($_FILES['file_path']) && $_FILES['file_path']['size'] > 0) {
        $target = 'uploads/music/' . uniqid() . '_' . basename($_FILES['file_path']['name']);
        if (move_uploaded_file($_FILES['file_path']['tmp_name'], '../' . $target)) {
            // Xóa file nhạc cũ nếu có
            if ($file_path && file_exists('../' . $file_path)) unlink('../' . $file_path);
            $file_path = $target;
        }
    }
    $stmt = $pdo->prepare("UPDATE musics SET title=?, composer=?, bpm=?, cover_image=?, file_path=? WHERE id=?");
    $stmt->execute([$title, $composer, $bpm, $cover_image, $file_path, $id]);
    header("Location: admin_manage_music.php?success=1");
    exit();
}

ob_start();
?>
<style>
    .edit-container { 
        max-width: 500px; 
        margin: 40px auto; 
        background: #fff; 
        padding: 30px; 
        border-radius: 10px; 
        box-shadow: 0 0 10px #ccc; 
        color: #222;
    }
    label { 
        display: block; 
        margin-top: 15px; 
        color: #222;
    }
    input[type="text"], input[type="number"], input[type="file"] {
        width: 100%; 
        padding: 8px; 
        border-radius: 5px; 
        border: 1px solid #ccc; 
        color: #222; 
        background: #fff;
    }
    input[type="file"] { margin-top: 8px; }
    img { max-width: 120px; margin-top: 10px; border-radius: 8px; }
    button { 
        margin-top: 20px; 
        padding: 10px 20px; 
        border-radius: 5px; 
        border: none; 
        background: #007bff; 
        color: #fff; 
        font-weight: bold; 
        cursor: pointer; 
    }
    a { display: inline-block; margin-top: 20px; color: #007bff; }
</style>
<div class="edit-container">
    <h2>Sửa bài nhạc</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Tên bài hát:
            <input type="text" name="title" value="<?= htmlspecialchars($music['title']) ?>" required>
        </label>
        <label>Tác giả:
            <input type="text" name="composer" value="<?= htmlspecialchars($music['composer']) ?>">
        </label>
        <label>BPM:
            <input type="number" name="bpm" value="<?= htmlspecialchars($music['bpm']) ?>" min="1" max="300">
        </label>
        <label>Ảnh bìa hiện tại:
            <?php if ($music['cover_image'] && file_exists('../' . $music['cover_image'])): ?>
                <img src="../<?= $music['cover_image'] ?>" alt="cover">
            <?php else: ?>
                <img src="https://via.placeholder.com/120?text=No+Cover" alt="cover">
            <?php endif; ?>
        </label>
        <label>Đổi ảnh bìa:
            <input type="file" name="cover_image" accept="image/*">
        </label>
        <label>File nhạc hiện tại:
            <?php if ($music['file_path'] && file_exists('../' . $music['file_path'])): ?>
                <a href="../<?= $music['file_path'] ?>" target="_blank">Nghe thử</a>
            <?php else: ?>
                <span>Không có file</span>
            <?php endif; ?>
        </label>
        <label>Đổi file nhạc:
            <input type="file" name="file_path" accept="audio/*">
        </label>
        <button type="submit">Lưu thay đổi</button>
    </form>
    <a href="admin_manage_music.php">← Quay lại quản lý nhạc</a>
</div>
<?php
$content = ob_get_clean();
$title = "Sửa nhạc";
include '../views/admin_layout.php';