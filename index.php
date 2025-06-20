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
$selected_playlist = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT id, name FROM playlists WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $playlists = $stmt->fetchAll();

    // Lấy playlist được chọn (nếu có)
    if (isset($_GET['playlist_id'])) {
        $selected_playlist_id = $_GET['playlist_id'];
        $stmt = $pdo->prepare("SELECT p.name, m.* FROM playlists p LEFT JOIN playlist_music pm ON p.id = pm.playlist_id LEFT JOIN musics m ON pm.music_id = m.id WHERE p.id = ? AND p.user_id = ?");
        $stmt->execute([$selected_playlist_id, $user_id]);
        $selected_playlist = $stmt->fetchAll();

        // Đếm số bài hát trong playlist
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM playlist_music WHERE playlist_id = ?");
        $stmt->execute([$selected_playlist_id]);
        $playlist_count = $stmt->fetch()['count'];
    }
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

// Xử lý thêm nhạc vào danh sách
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_playlist']) && isset($_POST['playlist_id']) && $user_id) {
    $music_id = $_POST['music_id'];
    $playlist_id = $_POST['playlist_id'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO playlist_music (playlist_id, music_id) VALUES (?, ?)");
    $stmt->execute([$playlist_id, $music_id]);
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
            transition: transform 0.1s;
        }
        .header {
            display: flex;
            justify-content: flex-end;
            padding: 10px 20px;
            background-color: #1e1e1e;
        }
        .auth-buttons a, .auth-buttons form {
            margin-left: 10px;
            color: #1a73e8;
            text-decoration: none;
        }
        .auth-buttons form button {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .auth-buttons form button:hover {
            background-color: #1557b0;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background-color 0.3s;
        }
        .playlist-list li:hover {
            background-color: #2d2d2d;
        }
        .play-button {
            background-color: #1ed760;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .play-button:hover {
            background-color: #1db954;
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
            position: relative;
        }
        .music-list li:hover {
            transform: scale(1.05);
        }
        .music-list li img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            background-color: #333;
        }
        .music-list li span {
            display: block;
            margin-top: 5px;
            font-size: 14px;
            color: #bbb;
        }
        .add-to-playlist {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #1a73e8;
            border: none;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: none;
        }
        .add-to-playlist:hover {
            background-color: #1557b0;
        }
        .music-list li:hover .add-to-playlist {
            display: block;
        }
        .playlist-dropdown {
            display: none;
            position: absolute;
            background-color: #2d2d2d;
            border-radius: 5px;
            padding: 5px 0;
            z-index: 10;
            min-width: 150px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        }
        .playlist-dropdown.active {
            display: block;
        }
        .playlist-dropdown form {
            margin: 0;
        }
        .playlist-dropdown button {
            width: 100%;
            text-align: left;
            padding: 5px 10px;
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
        }
        .playlist-dropdown button:hover {
            background-color: #1e1e1e;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #2d2d2d;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }
        .playlist-header {
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .playlist-header img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 20px;
        }
        .playlist-header h1 {
            font-size: 24px;
            margin: 0;
        }
        .playlist-header .details {
            color: #bbb;
        }
        .playlist-table {
            width: 100%;
            border-collapse: collapse;
        }
        .playlist-table th, .playlist-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        .playlist-table th {
            color: #bbb;
        }
        .playlist-table td {
            color: #fff;
        }
        .playlist-table td:first-child {
            width: 30px;
        }
        .playlist-table td:last-child {
            text-align: right;
        }
        .playlist-table tr:hover {
            background-color: #1e1e1e;
        }
        .play-table-button {
            background-color: #1ed760;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-right: 10px;
            transition: background-color 0.3s;
        }
        .play-table-button:hover {
            background-color: #1db954;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="auth-buttons">
            <?php if ($user_id): ?>
                <form method="post" action="taikhoan/logout.php">
                    <button type="submit">Đăng xuất</button>
                </form>
            <?php else: ?>
                <a href="taikhoan/login.php">Đăng nhập</a> | <a href="taikhoan/register.php">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container">
        <div class="sidebar">
            <h2>Danh sách phát</h2>
            <?php if ($user_id): ?>
                <div class="playlist-form">
                    <form method="post" action="">
                        <input type="text" name="playlist_name" placeholder="Tên danh sách" required>
                        <button type="submit" name="create_playlist">Tạo</button>
                    </form>
                </div>
            <?php endif; ?>
            <ul class="playlist-list">
                <?php foreach ($playlists as $playlist): ?>
                    <li>
                        <span onclick="openPlaylistModal(<?php echo $playlist['id']; ?>)">
                            <?php echo htmlspecialchars($playlist['name']); ?>
                        </span>
                        <button class="play-button" onclick="playPlaylist(<?php echo $playlist['id']; ?>)">▶</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="main-content">
            <h2>Danh sách bài hát</h2>
            <ul class="music-list">
                <?php foreach ($musics as $index => $music): ?>
                    <li>
                        <img src="<?php echo htmlspecialchars($music['cover_image'] ? 'admin/' . $music['cover_image'] : 'placeholder.jpg'); ?>" alt="Cover">
                        <span><?php echo htmlspecialchars($music['title']); ?></span>
                        <?php if ($user_id): ?>
                            <button class="add-to-playlist" onclick="toggleDropdown(this, <?php echo $music['id']; ?>)">+</button>
                            <div class="playlist-dropdown" id="dropdown-<?php echo $music['id']; ?>">
                                <?php foreach ($playlists as $playlist): ?>
                                    <form method="post" action="">
                                        <input type="hidden" name="music_id" value="<?php echo $music['id']; ?>">
                                        <input type="hidden" name="playlist_id" value="<?php echo $playlist['id']; ?>">
                                        <button type="submit" name="add_to_playlist"><?php echo htmlspecialchars($playlist['name']); ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="modal" id="playlist-modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closePlaylistModal()">×</button>
            <div class="playlist-header" id="playlist-header">
                <img src="" alt="Cover">
                <div>
                    <h1></h1>
                    <div class="details"></div>
                </div>
            </div>
            <table class="playlist-table" id="playlist-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tiêu đề</th>
                        <th>Tên người sáng tác</th>
                        <th><span class="duration">⏱</span></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
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
        const playlistHeader = document.getElementById('playlist-header');
        const playlistModal = document.getElementById('playlist-modal');
        const playlistTableBody = document.getElementById('playlist-table').querySelector('tbody');
        let bpm = 120; // Giả định BPM, thay bằng giá trị từ cơ sở dữ liệu nếu có

        function loadTracks() {
            if (tracks.length) {
                updateMusicBar();
                audioSource.src = tracks[currentTrack];
                audioSourceWav.src = tracks[currentTrack];
                console.log('Loading track (audioSource):', audioSource.src);
                audioPlayer.load();
                updateBackgroundColor();
            }
        }

        function playTrack(index) {
            if (index >= 0 && index < musicsData.length) {
                currentTrack = index;
                updateMusicBar();
                audioSource.src = tracks[currentTrack];
                audioSourceWav.src = tracks[currentTrack];
                console.log('Playing track:', tracks[currentTrack]);
                audioPlayer.load();
                audioPlayer.play().catch(error => console.error('Error playing audio:', error));
                musicBar.style.display = 'flex';
                coverImage.style.display = audioPlayer.paused ? 'none' : 'block';
                updateBackgroundColor();
                startBeatAnimation();
            } else {
                console.error('Index out of bounds:', index);
            }
        }

        function updateMusicBar() {
            const music = musicsData[currentTrack];
            if (music && music.cover_image) {
                coverImage.querySelector('img').src = 'admin/' + music.cover_image;
            } else {
                coverImage.style.display = 'none';
                coverImage.querySelector('img').src = 'placeholder.jpg'; // Ảnh mặc định nếu không có
            }
        }

        function playPause() {
            if (audioPlayer.paused) {
                audioPlayer.play().catch(error => console.error('Error:', error));
                coverImage.style.display = 'block';
                coverImage.style.animationPlayState = 'running';
                startBeatAnimation();
            } else {
                audioPlayer.pause();
                coverImage.style.animationPlayState = 'paused';
                stopBeatAnimation();
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

        function toggleDropdown(button, musicId) {
            const dropdown = document.getElementById('dropdown-' + musicId);
            dropdown.classList.toggle('active');
            document.addEventListener('click', function closeDropdown(event) {
                if (!button.contains(event.target) && !dropdown.contains(event.target)) {
                    dropdown.classList.remove('active');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }

        // Thay đổi màu nền dựa trên ảnh bìa
        function updateBackgroundColor() {
            if (coverImage.querySelector('img').src) {
                const img = new Image();
                img.crossOrigin = "Anonymous";
                img.src = coverImage.querySelector('img').src;
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
                    let r = 0, g = 0, b = 0;
                    for (let i = 0; i < imageData.length; i += 4) {
                        r += imageData[i];
                        g += imageData[i + 1];
                        b += imageData[i + 2];
                    }
                    const pixelCount = imageData.length / 4;
                    r = Math.round(r / pixelCount);
                    g = Math.round(g / pixelCount);
                    b = Math.round(b / pixelCount);
                    playlistHeader.style.backgroundColor = `rgb(${r}, ${g}, ${b})`;
                };
            }
        }

        // Hiệu ứng zoom theo nhịp điệu
        let animationFrameId = null;
        function startBeatAnimation() {
            if (animationFrameId) return;
            const beatInterval = 60000 / bpm; // Thời gian giữa các nhịp (ms)
            let lastBeat = performance.now();

            function animate(currentTime) {
                const timeSinceLastBeat = currentTime - lastBeat;
                const scale = 1 + Math.sin(timeSinceLastBeat / beatInterval * 2 * Math.PI) * 0.05; // Dao động scale từ 0.95 đến 1.05
                document.body.style.transform = `scale(${scale})`;
                animationFrameId = requestAnimationFrame(animate);
                if (timeSinceLastBeat >= beatInterval) {
                    lastBeat = currentTime;
                }
            }
            animationFrameId = requestAnimationFrame(animate);
        }

        function stopBeatAnimation() {
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
                document.body.style.transform = 'scale(1)';
            }
        }

        function findMusicIndex(song, musicsData) {
            return musicsData.findIndex(m => m.id === song.id);
        }

        function openPlaylistModal(playlistId) {
            const playlist = <?php echo json_encode($playlists); ?>.find(p => p.id == playlistId);
            const selectedPlaylist = <?php echo json_encode($selected_playlist); ?>;
            const playlistCount = <?php echo json_encode($playlist_count); ?>;

            playlistHeader.querySelector('h1').textContent = playlist ? playlist.name : 'Chưa có tên';
            playlistHeader.querySelector('.details').textContent = `HMNF - ${playlistCount} bài hát`;
            playlistHeader.querySelector('img').src = selectedPlaylist.length && selectedPlaylist[0].cover_image ? 'admin/' + selectedPlaylist[0].cover_image : 'placeholder.jpg';

            playlistTableBody.innerHTML = '';
            selectedPlaylist.forEach((song, index) => {
                if (song.id) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>
                            <button class="play-table-button" onclick="playTrack(${findMusicIndex(song, musicsData)})">▶</button>
                            ${song.title || 'Không có tiêu đề'}
                        </td>
                        <td>${song.composer || 'Không rõ'}</td>
                        <td>${formatTime(song.duration || 0)}</td>
                    `;
                    playlistTableBody.appendChild(row);
                }
            });

            playlistModal.style.display = 'flex';
            updateBackgroundColor();
        }

        function closePlaylistModal() {
            playlistModal.style.display = 'none';
        }

        function playPlaylist(playlistId) {
            const selectedPlaylist = <?php echo json_encode($selected_playlist); ?>;
            if (selectedPlaylist.length > 0) {
                const firstSongIndex = findMusicIndex(selectedPlaylist[0], musicsData);
                if (firstSongIndex !== -1) {
                    playTrack(firstSongIndex);
                }
            }
        }

        window.onload = loadTracks;
    </script>
</body>
</html>