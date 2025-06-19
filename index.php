<?php
session_start();
require_once 'config/database.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

// Lấy tất cả các bài hát từ cơ sở dữ liệu
$stmt = $pdo->query("SELECT * FROM musics");
$musics = $stmt->fetchAll();

// Lấy danh sách playlist của người dùng
$playlists = [];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT id, name FROM playlists WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $playlists = $stmt->fetchAll();
}

// Thêm tiền tố đường dẫn cho file trong thư mục admin/upload
$base_upload_path = 'admin/upload/';
$tracks = array_map(function($music) use ($base_upload_path) {
    $full_path = $base_upload_path . basename(dirname($music['file_path'])) . '/' . basename($music['file_path']);
    error_log("Track path: $full_path"); // Ghi log để debug
    return $full_path;
}, $musics);

// Xử lý tạo danh sách nhạc
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_playlist'])) {
    $playlist_name = $_POST['playlist_name'];
    $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name) VALUES (?, ?)");
    $stmt->execute([$user_id, $playlist_name]);
    header("Location: index.php");
    exit();
}

// Xử lý thêm nhạc ưa thích
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_favorite']) && $user_id) {
    $music_id = $_POST['music_id'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_favorites (user_id, music_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $music_id]);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Web Nghe Nhạc</title>
    <link rel="stylesheet" href="css/thanhbar.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            background-color: #121212;
            color: #fff;
            display: flex;
            flex-direction: column;
        }
        .container {
            display: flex;
            flex: 1;
            padding: 20px;
        }
        .sidebar {
            width: 250px;
            padding-right: 20px;
            border-right: 1px solid #333;
        }
        .sidebar h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .playlist-list {
            list-style: none;
            padding: 0;
        }
        .playlist-list li {
            padding: 10px;
            background-color: #1e1e1e;
            margin-bottom: 5px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .playlist-list li:hover {
            background-color: #2d2d2d;
        }
        .main-content {
            flex: 1;
            padding-left: 20px;
        }
        .main-content h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .music-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        .music-list li {
            background-color: #1e1e1e;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .music-list li:hover {
            transform: scale(1.05);
        }
        .music-list li img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            background-color: #333; /* Màu nền tạm thời nếu ảnh không load */
        }
        .music-list li span {
            display: block;
            margin-top: 5px;
            font-size: 14px;
            color: #bbb;
        }
        .auth-buttons, .playlist-form {
            margin: 10px 0;
        }
        input[type="text"] {
            padding: 5px;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: #2d2d2d;
            color: #fff;
        }
        button {
            padding: 5px 10px;
            margin-left: 5px;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #1557b0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Danh sách phát</h2>
            <div class="playlist-form">
                <form method="post" action="">
                    <input type="text" name="playlist_name" placeholder="Tên danh sách" required>
                    <button type="submit" name="create_playlist">Tạo</button>
                </form>
            </div>
            <ul class="playlist-list">
                <?php foreach ($playlists as $playlist): ?>
                    <li><?php echo htmlspecialchars($playlist['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="main-content">
            <h2>Danh sách bài hát</h2>
            <ul class="music-list">
                <?php foreach ($musics as $index => $music): ?>
                    <li onclick="playTrack(<?php echo $index; ?>)">
                        <img src="<?php echo htmlspecialchars($music['cover_image'] ? 'admin/' . $music['cover_image'] : 'placeholder.jpg'); ?>" alt="Cover">
                        <span><?php echo htmlspecialchars($music['title']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="music-bar" id="musicBar" style="display: none;">
        <div class="cover" id="coverImage">
            <img src="" alt="Cover">
        </div>
        <div class="controls">
            <button onclick="playPrevious()">❮</button>
            <button onclick="playPause()">▶</button>
            <button onclick="playNext()">❯</button>
        </div>
        <div class="progress-container">
            <progress id="progressBar" value="0" max="1" class="progress"></progress>
            <div class="time">
                <span id="currentTime">0:00</span>
                <span id="duration">0:00</span>
            </div>
        </div>
        <audio id="audioPlayer">
            <source id="audioSource" src="" type="audio/ogg">
            <source id="audioSourceWav" src="" type="audio/wav">
            Trình duyệt của bạn không hỗ trợ phần tử âm thanh.
        </audio>
    </div>

    <script>
        let currentTrack = 0;
        let tracks = <?php echo json_encode($tracks); ?>;
        let musicsData = <?php echo json_encode($musics); ?>;
        const audioPlayer = document.getElementById('audioPlayer');
        const audioSource = document.getElementById('audioSource');
        const audioSourceWav = document.getElementById('audioSourceWav');
        const musicBar = document.getElementById('musicBar');
        const progressBar = document.getElementById('progressBar');
        const currentTime = document.getElementById('currentTime');
        const duration = document.getElementById('duration');
        const coverImage = document.getElementById('coverImage');

        function loadTracks() {
            if (tracks.length) {
                updateMusicBar();
                audioSource.src = tracks[currentTrack];
                audioSourceWav.src = tracks[currentTrack];
                console.log('Loading track (audioSource):', audioSource.src);
                audioPlayer.load();
            }
        }

        function playTrack(index) {
            currentTrack = index;
            updateMusicBar();
            audioSource.src = tracks[currentTrack];
            audioSourceWav.src = tracks[currentTrack];
            console.log('Playing track:', tracks[currentTrack]);
            audioPlayer.load();
            audioPlayer.play().catch(error => console.error('Error playing audio:', error));
            musicBar.style.display = 'flex';
            coverImage.style.display = audioPlayer.paused ? 'none' : 'block'; // Hiển thị ảnh khi phát
        }

        function updateMusicBar() {
            const music = musicsData[currentTrack];
            if (music.cover_image) {
                coverImage.querySelector('img').src = 'admin/' + music.cover_image;
            } else {
                coverImage.style.display = 'none';
            }
        }

        function playPause() {
            if (audioPlayer.paused) {
                audioPlayer.play().catch(error => console.error('Error:', error));
                coverImage.style.display = 'block';
                coverImage.style.animationPlayState = 'running';
            } else {
                audioPlayer.pause();
                coverImage.style.animationPlayState = 'paused';
            }
        }

        function playPrevious() {
            if (currentTrack > 0) {
                currentTrack--;
                playTrack(currentTrack);
            }
        }

        function playNext() {
            if (currentTrack < tracks.length - 1) {
                currentTrack++;
                playTrack(currentTrack);
            }
        }

        audioPlayer.addEventListener('timeupdate', () => {
            if (audioPlayer.duration) {
                progressBar.value = audioPlayer.currentTime / audioPlayer.duration;
                currentTime.textContent = formatTime(audioPlayer.currentTime);
                duration.textContent = formatTime(audioPlayer.duration);
            }
        });

        progressBar.addEventListener('click', (e) => {
            const rect = progressBar.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audioPlayer.currentTime = percent * audioPlayer.duration;
        });

        audioPlayer.addEventListener('ended', () => {
            playNext();
        });

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
        }

        window.onload = loadTracks;
    </script>
</body>
</html>