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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="auth-buttons">
            <?php if ($user_id): ?>
                <form method="post" action="taikhoan/logout.php">
                    <button type="submit">Đăng xuất</button>
                </form>
                <a href="admin/upload_music.php">Thêm nhạc</a>
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
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="main-content">
            <h2>Danh sách bài hát</h2>
            <ul class="music-list">
                <?php foreach ($musics as $index => $music): ?>
                    <li>
                        <img src="<?php echo htmlspecialchars($music['cover_image'] ? 'admin/' . $music['cover_image'] : ''); ?>" alt="Cover" <?php echo !$music['cover_image'] ? 'style="display:none;"' : ''; ?>>
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
                        <button class="play-music-button" onclick="playTrack(<?php echo $index; ?>)">▶</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="modal" id="playlist-modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closePlaylistModal()">×</button>
            <div class="playlist-header">
                <img src="<?php echo $selected_playlist && $selected_playlist[0]['cover_image'] ? 'admin/' . $selected_playlist[0]['cover_image'] : ''; ?>" alt="Cover" <?php echo !$selected_playlist || !$selected_playlist[0]['cover_image'] ? 'style="display:none;"' : ''; ?>>
                <div class="info">
                    <h1>Playlist</h1>
                    <div class="details" id="playlist-details"></div>
                </div>
                <div class="controls">
                    <button>↓</button>
                    <button onclick="playPlaylistFromModal()">▶</button>
                    <button>⋮</button>
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
        // Đảm bảo các biến toàn cục được khai báo
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
        const playlistTableBody = document.getElementById('playlist-table') ? document.getElementById('playlist-table').querySelector('tbody') : null;
        const playlistDetails = document.getElementById('playlist-details');
        let bpm = 120; // Sẽ lấy từ musicsData

        // Kiểm tra hàm playTrack
        window.playTrack = function(index) {
            console.log('Attempting to play track at index:', index); // Debug log
            if (index >= 0 && index < musicsData.length) {
                currentTrack = index;
                bpm = musicsData[currentTrack].bpm || 120; // Lấy BPM từ dữ liệu, mặc định 120
                updateMusicBar();
                audioSource.src = tracks[currentTrack];
                audioSourceWav.src = tracks[currentTrack];
                console.log('Playing track:', tracks[currentTrack], 'with BPM:', bpm);
                audioPlayer.load();
                audioPlayer.play().catch(error => console.error('Error playing audio:', error));
                musicBar.style.display = 'flex';
                if (coverImage && coverImage.querySelector('img')) {
                    coverImage.querySelector('img').src = musicsData[currentTrack].cover_image ? 'admin/' + musicsData[currentTrack].cover_image : '';
                    coverImage.style.display = musicsData[currentTrack].cover_image ? 'block' : 'none';
                }
                updateBackgroundColor();
                startBeatAnimation();
            } else {
                console.error('Index out of bounds:', index, 'musicsData length:', musicsData.length);
            }
        };

        function updateMusicBar() {
            if (coverImage && coverImage.querySelector('img')) {
                const music = musicsData[currentTrack];
                if (music && music.cover_image) {
                    coverImage.querySelector('img').src = 'admin/' + music.cover_image;
                } else {
                    coverImage.style.display = 'none';
                }
            }
        }

        function playPause() {
            if (audioPlayer.paused) {
                audioPlayer.play().catch(error => console.error('Error:', error));
                if (coverImage && coverImage.querySelector('img')) {
                    coverImage.style.display = 'block';
                    coverImage.style.animationPlayState = 'running';
                }
                startBeatAnimation();
            } else {
                audioPlayer.pause();
                if (coverImage && coverImage.querySelector('img')) {
                    coverImage.style.animationPlayState = 'paused';
                }
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

        function updateBackgroundColor() {
            if (coverImage && coverImage.querySelector('img')) {
                const imgElement = coverImage.querySelector('img');
                if (imgElement.src) {
                    const img = new Image();
                    img.crossOrigin = "Anonymous";
                    img.src = imgElement.src;
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
                        if (playlistHeader) {
                            playlistHeader.style.backgroundColor = `rgb(${r}, ${g}, ${b})`;
                        }
                    };
                    img.onerror = () => {
                        console.warn('Failed to load image:', imgElement.src);
                        if (playlistHeader) {
                            playlistHeader.style.backgroundColor = '#2d2d2d'; // Màu mặc định nếu lỗi
                        }
                    };
                }
            } else {
                console.warn('coverImage or img not found');
                if (playlistHeader) {
                    playlistHeader.style.backgroundColor = '#2d2d2d'; // Màu mặc định nếu không có ảnh
                }
            }
        }

        let animationFrameId = null;
        function startBeatAnimation() {
            if (animationFrameId) return;
            const beatInterval = 60000 / bpm;
            let lastBeat = performance.now();

            function animate(currentTime) {
                const timeSinceLastBeat = currentTime - lastBeat;
                const scale = 1 + Math.sin(timeSinceLastBeat / beatInterval * 2 * Math.PI) * 0.05;
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
            console.log('Searching for song:', song, 'in musicsData:', musicsData); // Debug log
            const index = musicsData.findIndex(m => m.id === song.id);
            console.log('Found index:', index); // Debug log
            return index;
        }

        function openPlaylistModal(playlistId) {
            console.log('Opening playlist modal for ID:', playlistId); // Debug log
            const playlist = <?php echo json_encode($playlists); ?>.find(p => p.id == playlistId);
            const selectedPlaylist = <?php echo json_encode($selected_playlist); ?>;
            const playlistCount = <?php echo json_encode($playlist_count); ?>;

            if (playlistModal) {
                console.log('PlaylistModal found, proceeding...'); // Debug log
                if (playlist) {
                    playlistHeader.querySelector('h1').textContent = playlist.name;
                    playlistDetails.textContent = `HMNF - ${playlistCount} bài hát`;
                } else {
                    playlistHeader.querySelector('h1').textContent = 'Chưa có tên';
                    playlistDetails.textContent = 'HMNF - 0 bài hát';
                }
                playlistHeader.querySelector('img').src = selectedPlaylist && selectedPlaylist[0] && selectedPlaylist[0]['cover_image'] ? 'admin/' . selectedPlaylist[0]['cover_image'] : '';
                if (!selectedPlaylist || !selectedPlaylist[0] || !selectedPlaylist[0]['cover_image']) {
                    playlistHeader.querySelector('img').style.display = 'none';
                }

                playlistTableBody.innerHTML = '';
                if (selectedPlaylist && selectedPlaylist.length) {
                    selectedPlaylist.forEach((song, index) => {
                        if (song.id) {
                            const musicIndex = findMusicIndex(song, musicsData);
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${index + 1}</td>
                                <td>
                                    <button class="play-table-button" onclick="playTrack(${musicIndex})">▶</button>
                                    ${song.title || 'Không có tiêu đề'}
                                </td>
                                <td>${song.composer || 'Không rõ'}</td>
                                <td>${formatTime(song.duration || 0)}</td>
                            `;
                            playlistTableBody.appendChild(row);
                        }
                    });
                } else {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="4">Không có bài hát trong playlist</td>';
                    playlistTableBody.appendChild(row);
                }

                playlistModal.style.display = 'flex';
                updateBackgroundColor();
            } else {
                console.error('playlistModal element not found');
            }
        }

        function closePlaylistModal() {
            if (playlistModal) {
                playlistModal.style.display = 'none';
            }
        }

        function playPlaylist(playlistId) {
            const selectedPlaylist = <?php echo json_encode($selected_playlist); ?>;
            if (selectedPlaylist && selectedPlaylist.length > 0) {
                const firstSongIndex = findMusicIndex(selectedPlaylist[0], musicsData);
                if (firstSongIndex !== -1) {
                    playTrack(firstSongIndex);
                }
            }
        }

        function playPlaylistFromModal() {
            const selectedPlaylist = <?php echo json_encode($selected_playlist); ?>;
            if (selectedPlaylist && selectedPlaylist.length > 0) {
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