<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: taikhoan/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $pdo->prepare("SELECT * FROM musics WHERE uploaded_by = ?");
$stmt->execute([$user_id]);
$musics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Web Nghe Nhạc</title>
    <style>
        .player { width: 100%; max-width: 600px; margin: 20px auto; }
        audio { width: 100%; }
        .controls { margin-top: 10px; }
        .upload { display: <?php echo $role == 'admin' ? 'block' : 'none'; ?>; }
        .auth-buttons { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="player">
        <h1>Web Nghe Nhạc</h1>
        <audio id="audioPlayer" controls>
            <source id="audioSource" src="" type="audio/ogg">
            Trình duyệt của bạn không hỗ trợ phần tử âm thanh.
        </audio>
        <div class="controls">
            <button onclick="playPrevious()">Quay lại</button>
            <button onclick="playPause()">Phát/Tạm dừng</button>
            <button onclick="playNext()">Tiếp theo</button>
        </div>
    </div>

    <div class="upload">
        <form method="post" action="upload.php" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Tên bài hát" required>
            <input type="text" name="composer" placeholder="Tác giả">
            <input type="file" name="musicFile" accept=".ogg,.wav" required>
            <button type="submit">Tải lên</button>
        </form>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post" action="taikhoan/logout.php">
            <button type="submit">Đăng xuất</button>
        </form>
    <?php endif; ?>

    <script>
        let currentTrack = 0;
        let tracks = <?php echo json_encode(array_column($musics, 'file_path')); ?>;
        const audioPlayer = document.getElementById('audioPlayer');
        const audioSource = document.getElementById('audioSource');

        function loadTracks() {
            if (tracks.length) audioSource.src = tracks[currentTrack];
        }

        function playPause() {
            if (audioPlayer.paused) audioPlayer.play();
            else audioPlayer.pause();
        }

        function playPrevious() {
            if (currentTrack > 0) {
                currentTrack--;
                audioSource.src = tracks[currentTrack];
                audioPlayer.load();
                audioPlayer.play();
            }
        }

        function playNext() {
            if (currentTrack < tracks.length - 1) {
                currentTrack++;
                audioSource.src = tracks[currentTrack];
                audioPlayer.load();
                audioPlayer.play();
            }
        }

        window.onload = loadTracks;
    </script>
</body>
</html>