<?php
session_start();
require_once 'config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'user';

// Lấy tất cả bài hát
$musics = $pdo->query("SELECT * FROM musics")->fetchAll();

// Lấy playlist người dùng nếu đăng nhập
$playlists = [];
if ($user_id) {
    $playlists = $pdo->prepare("SELECT id, name FROM playlists WHERE user_id = ?");
    $playlists->execute([$user_id]);
    $playlists = $playlists->fetchAll();
}

// Xử lý tạo playlist/ thêm nhạc vào playlist/ thêm yêu thích
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_playlist'])) {
        $pdo->prepare("INSERT INTO playlists (user_id, name) VALUES (?, ?)")
            ->execute([$user_id, $_POST['playlist_name']]);
    } elseif (isset($_POST['add_to_playlist']) && $user_id) {
        $pdo->prepare("INSERT IGNORE INTO playlist_music (playlist_id, music_id) VALUES (?, ?)")
            ->execute([$_POST['playlist_id'], $_POST['music_id']]);
    } elseif (isset($_POST['add_favorite']) && $user_id) {
        $pdo->prepare("INSERT IGNORE INTO user_favorites (user_id, music_id) VALUES (?, ?)")
            ->execute([$user_id, $_POST['music_id']]);
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
  <style>
    #bar-cover {
      transition: transform 0.1s ease-out;
    }
  </style>
</head>
<body>

<div class="header">
  <div class="auth-buttons">
    <?php if ($user_id): ?>
      <form method="post" action="taikhoan/logout.php" style="display:inline;">
        <button>Đăng xuất</button>
      </form>
      <a href="admin/upload_music.php">Thêm nhạc</a>
    <?php else: ?>
      <a href="taikhoan/login.php">Đăng nhập</a>
      <a href="taikhoan/register.php">Đăng ký</a>
    <?php endif; ?>
  </div>
</div>

<div class="container">
  <div class="sidebar">
    <h2>Playlist</h2>
    <?php if ($user_id): ?>
      <form method="post"><input name="playlist_name" required placeholder="Tên playlist"><button name="create_playlist">Tạo</button></form>
    <?php endif; ?>
    <ul class="playlist-list">
      <?php foreach ($playlists as $pl): ?>
        <li><span onclick="openPlaylistModal(<?= $pl['id'] ?>)"><?= htmlspecialchars($pl['name']) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="main-content">
    <h2>Danh sách bài hát</h2>
    <ul class="music-list">
      <?php foreach ($musics as $i => $mus): ?>
        <li>
          <img src="<?= $mus['cover_image'] ? 'admin/' . $mus['cover_image'] : 'https://via.placeholder.com/150' ?>" alt="cover">
          
          <button class="play-music-button" onclick="playTrack(<?= $i ?>)">▶</button>

          <?php if ($user_id): ?>
            <button class="add-to-playlist" onclick="toggleDropdown(this, <?= $mus['id'] ?>)">+</button>
            <div class="playlist-dropdown" id="dropdown-<?= $mus['id'] ?>">
              <?php foreach ($playlists as $pl): ?>
                <form method="post">
                  <input type="hidden" name="music_id" value="<?= $mus['id'] ?>">
                  <input type="hidden" name="playlist_id" value="<?= $pl['id'] ?>">
                  <button name="add_to_playlist"><?= htmlspecialchars($pl['name']) ?></button>
                </form>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <span><?= htmlspecialchars($mus['title']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- Modal hiển thị danh sách nhạc trong playlist -->
<div class="modal" id="playlist-modal">
  <div class="modal-content">
    <button onclick="closePlaylistModal()" class="close-modal">×</button>
    <div class="playlist-header">
      <div class="info">
        <h1 id="modal-title">Playlist</h1>
        <div class="details" id="modal-count"></div>
      </div>
    </div>
    <table class="playlist-table">
      <thead><tr><th>#</th><th>Tiêu đề</th><th>Tác giả</th><th>⏱</th></tr></thead>
      <tbody id="modal-body"></tbody>
    </table>
  </div>
</div>

<!-- Trình phát nhạc -->
<div id="musicBar" style="display:none;">
  <img id="bar-cover" src="" style="width:60px;height:60px;object-fit:cover;margin-right:10px;">
  <div class="controls">
    <button onclick="playPrevious()">❮</button>
    <button onclick="playPause()">▶/❚❚</button>
    <button onclick="playNext()">❯</button>
  </div>
  <progress id="bar-progress" value="0" max="1"></progress>
  <span id="bar-time">0:00 / 0:00</span>
  <audio id="audioPlayer"></audio>
</div>

<script>
const musics = <?= json_encode($musics) ?>;
const tracks = <?= json_encode($tracks) ?>;
let current = 0;
let animationFrameId = null;

const audio = document.getElementById('audioPlayer');
const barCover = document.getElementById('bar-cover');
const barProgress = document.getElementById('bar-progress');
const barTime = document.getElementById('bar-time');
const musicBar = document.getElementById('musicBar');

function playTrack(i) {
  current = i;
  audio.src = tracks[i];
  audio.play();
  barCover.src = musics[i].cover_image ? 'admin/' + musics[i].cover_image : 'https://via.placeholder.com/150';
  musicBar.style.display = 'flex';
  startBeatAnimation(musics[i].bpm || 120); // Bắt đầu animation với BPM
}

audio.ontimeupdate = () => {
  if (audio.duration && !isNaN(audio.duration)) {
    barProgress.value = audio.currentTime / audio.duration;
    barTime.textContent = formatTime(audio.currentTime) + ' / ' + formatTime(audio.duration);
  } else {
    barProgress.value = 0;
    barTime.textContent = formatTime(0) + ' / ' + formatTime(0);
  }
};

audio.onended = () => playNext();

function playPause() {
  if (audio.paused) {
    audio.play();
    startBeatAnimation(musics[current].bpm || 120); // Tiếp tục animation khi play
  } else {
    audio.pause();
    stopBeatAnimation(); // Dừng animation khi pause
  }
}

function playPrevious() {
  if (current > 0) playTrack(current - 1);
}

function playNext() {
  if (current + 1 < tracks.length) playTrack(current + 1);
}

function formatTime(s) {
  const m = Math.floor(s/60), sec = Math.floor(s%60);
  return `${m}:${sec<10?'0':''}${sec}`;
}

function toggleDropdown(btn,id) {
  const dd = document.getElementById('dropdown-'+id);
  dd.classList.toggle('active');
  document.addEventListener('click', e => {
    if (!btn.contains(e.target) && !dd.contains(e.target)) dd.classList.remove('active');
  }, { once: true });
}

function openPlaylistModal(pid) {
  fetch(`get_playlist.php?playlist_id=${pid}`)
    .then(r => r.json())
    .then(data => {
      document.getElementById('modal-title').textContent = musics.find(m=>m.id==pid)?.name || 'Playlist';
      document.getElementById('modal-count').textContent = `${data.count} bài hát`;
      const tbody = document.getElementById('modal-body');
      tbody.innerHTML = '';
      data.songs.forEach((s,i) => {
        const idx = musics.findIndex(m => m.id === s.id);
        let tr = `<tr><td>${i+1}</td><td><button onclick="playTrack(${idx})">▶</button> ${s.title}</td><td>${s.composer||''}</td><td>${formatTime(s.duration||0)}</td></tr>`;
        tbody.insertAdjacentHTML('beforeend',tr);
      });
      document.getElementById('playlist-modal').style.display='flex';
    })
    .catch(console.error);
}

function closePlaylistModal() {
  document.getElementById('playlist-modal').style.display='none';
}

// Hàm animation nhảy theo BPM
function startBeatAnimation(bpm) {
  if (animationFrameId) cancelAnimationFrame(animationFrameId);
  const beatInterval = 60000 / bpm; // Chuyển BPM sang mili giây mỗi nhịp
  let lastBeat = performance.now();

  function animate(currentTime) {
    const timeSinceLastBeat = currentTime - lastBeat;
    const scale = 1 + Math.sin(timeSinceLastBeat / beatInterval * 2 * Math.PI) * 0.1; // Dao động scale từ 0.9 đến 1.1
    barCover.style.transform = `scale(${scale})`;
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
    barCover.style.transform = 'scale(1)'; // Trả về kích thước ban đầu
  }
}


</script>
</body>
</html>