<?php
session_start();
require_once 'config/database.php';
require_once 'models/MusicModel.php';

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'user';

$musicModel = new MusicModel($pdo);

// Lấy tất cả bài hát
$musics = $musicModel->getAllMusics();

// Lấy playlist người dùng nếu đăng nhập
$playlists = [];
if ($user_id) {
    $playlists = $musicModel->getUserPlaylists($user_id);
}

// Xử lý tạo playlist/ thêm nhạc vào playlist/ thêm yêu thích
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_playlist'])) {
        $musicModel->createPlaylist($user_id, $_POST['playlist_name']);
    } elseif (isset($_POST['edit_playlist']) && $user_id) {
        $musicModel->updatePlaylist($_POST['playlist_id'], $user_id, $_POST['playlist_name']);
    } elseif (isset($_POST['delete_playlist']) && $user_id) {
        $musicModel->deletePlaylist($_POST['playlist_id'], $user_id);
    } elseif (isset($_POST['add_to_playlist']) && $user_id) {
        $musicModel->addToPlaylist($_POST['playlist_id'], $_POST['music_id']);
    } elseif (isset($_POST['add_favorite']) && $user_id) {
        $musicModel->addFavorite($user_id, $_POST['music_id']);
    }
    header("Location: index.php");
    exit();
}

// Chuẩn bị danh sách đường dẫn nhạc
$tracks = [];
foreach ($musics as $music) {
    $tracks[] = 'admin/upload/' . basename(dirname($music['file_path'])) . '/' . basename($music['file_path']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Web Nghe Nhạc</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Tải script với defer để đảm bảo DOM sẵn sàng -->
    <script src="public/js/app.js" defer></script>
    <script>
        // Định nghĩa biến global trước khi DOM được render
        window.musics = <?= json_encode($musics) ?>;
        window.tracks = <?= json_encode($tracks) ?>;
    </script>
    <style>
        #visualizer {
            width: 100%;
            height: 30px;
            background: transparent;
            position: fixed;
            bottom: 70px;
            left: 50%;
            transform: translateX(-50%);
            display: none;
            overflow: hidden;
        }
        #visualizer.active {
            display: block;
        }
        canvas#visualizer-canvas {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <?php include 'views/header.php'; ?>
    <div class="container">
        <?php include 'views/sidebar.php'; ?>
        <?php include 'views/music_list.php'; ?>
    </div>
    <?php include 'views/modal.php'; ?>
    <?php include 'views/music_bar.php'; ?>
    <?php include 'views/visualizer.php'; ?>
</body>
</html>