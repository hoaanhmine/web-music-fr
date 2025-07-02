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
    #visualizer {
      width: 100%;
      height: 30px; /* Nhỏ gọn */
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
<div id="musicBar">
  <img id="bar-cover" src="" style="width:40px;height:40px;object-fit:cover;">
  <button class="nav-button" onclick="playPrevious()">⏮️</button>
  <progress id="bar-progress" value="0" max="1"></progress>
  <span id="bar-time">0:00 / 0:00</span>
  <button class="play-pause-button" onclick="playPause()">▶</button>
  <button class="nav-button" onclick="playNext()">⏭️</button>
  <input type="range" id="volume-control" min="0" max="1" step="0.1" value="1">
  <audio id="audioPlayer"></audio>
</div>

<!-- Panel visualize -->
<div id="visualizer">
  <canvas id="visualizer-canvas"></canvas>
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
const volumeControl = document.getElementById('volume-control');
const playPauseButton = document.querySelector('.play-pause-button');
const visualizer = document.getElementById('visualizer');
const canvas = document.getElementById('visualizer-canvas');
const ctx = canvas.getContext('2d');

function playTrack(i) {
  if (!audio) {
    console.error('Audio element not found!');
    return;
  }
  current = i;
  audio.src = tracks[i];
  audio.play().catch(error => console.error('Error playing audio:', error));
  barCover.src = musics[i].cover_image ? 'admin/' + musics[i].cover_image : 'https://via.placeholder.com/150';
  musicBar.classList.add('active');
  visualizer.classList.add('active');
  startBeatAnimation(musics[i].bpm || 120);
  playPauseButton.textContent = '❚❚';
  initVisualizer(musics[i].bpm || 120);
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

audio.onended = () => {
  playNext();
  playPauseButton.textContent = '▶';
  visualizer.classList.remove('active');
};

function playPause() {
  if (audio.paused) {
    audio.play().catch(error => console.error('Error playing audio:', error));
    visualizer.classList.add('active');
    startBeatAnimation(musics[current].bpm || 120);
    playPauseButton.textContent = '❚❚';
  } else {
    audio.pause();
    stopBeatAnimation();
    visualizer.classList.remove('active');
    playPauseButton.textContent = '▶';
  }
}

function playPrevious() {
  if (current > 0) playTrack(current - 1);
  else if (current === 0) playTrack(tracks.length - 1);
}

function playNext() {
  if (current + 1 < tracks.length) playTrack(current + 1);
  else playTrack(0);
}

function formatTime(s) {
  const m = Math.floor(s/60), sec = Math.floor(s%60);
  return `${m}:${sec<10?'0':''}${sec}`;
}

function toggleDropdown(btn, id) {
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

// Hàm animation xoay giống FNF theo BPM
function startBeatAnimation(bpm) {
  if (animationFrameId) cancelAnimationFrame(animationFrameId);
  const beatInterval = 60000 / bpm;
  let lastBeat = performance.now();
  let direction = 1;

  function animate(currentTime) {
    const timeSinceLastBeat = currentTime - lastBeat;
    const progress = timeSinceLastBeat / beatInterval;
    const angle = direction * 10 * Math.sin(progress * Math.PI);
    barCover.style.transform = `rotate(${angle}deg)`;

    animationFrameId = requestAnimationFrame(animate);
    if (timeSinceLastBeat >= beatInterval) {
      lastBeat = currentTime;
      direction *= -1;
    }
  }
  animationFrameId = requestAnimationFrame(animate);
}

function stopBeatAnimation() {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
    animationFrameId = null;
    barCover.style.transform = 'rotate(0deg)';
  }
}

// Khởi tạo và vẽ visualizer sóng biển với nhiều lớp
function initVisualizer(bpm) {
  const audioContext = new (window.AudioContext || window.webkitAudioContext)();
  const source = audioContext.createMediaElementSource(audio);
  const analyser = audioContext.createAnalyser();
  analyser.fftSize = 1024;
  const bufferLength = analyser.frequencyBinCount;
  const timeDomainData = new Uint8Array(analyser.fftSize);
  const waveLayers = 3; // Số lớp sóng

  source.connect(analyser);
  analyser.connect(audioContext.destination);

  const beatInterval = 60000 / bpm;
  let lastBeatTime = performance.now();

  function draw(currentTime) {
    requestAnimationFrame(draw);
    if (visualizer.classList.contains('active')) {
      analyser.getByteTimeDomainData(timeDomainData);
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      const sliceWidth = canvas.width * 1.0 / analyser.fftSize;
      let x = 0;

      for (let layer = 0; layer < waveLayers; layer++) {
        ctx.beginPath();
        const offset = layer * (canvas.height / (waveLayers + 1)); // Dịch chuyển lớp sóng
        const ampFactor = 0.6 - (layer * 0.1); // Giảm biên độ cho các lớp sâu hơn

        for (let i = 0; i < analyser.fftSize; i++) {
          const v = timeDomainData[i] / 128.0;
          const amp = ampFactor; // Biên độ giảm dần
          const y = (canvas.height / 2) + offset + (v * canvas.height * amp / 2) + Math.sin(i * 0.1 + layer * 0.5) * 2; // Uốn lượn

          if (i === 0) {
            ctx.moveTo(x, y);
          } else {
            ctx.lineTo(x, y);
          }

          x += sliceWidth;
        }

        // Gradient cho từng lớp sóng
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, `rgba(0, 102, 204, ${0.9 - layer * 0.2})`); // Xanh đậm dần nhạt
        gradient.addColorStop(0.5, `rgba(0, 191, 255, ${0.7 - layer * 0.2})`);
        gradient.addColorStop(1, `rgba(135, 206, 235, ${0.3 - layer * 0.1})`);
        ctx.strokeStyle = gradient;
        ctx.lineWidth = 1.0 + (waveLayers - layer) * 0.3; // Độ dày giảm dần
        ctx.shadowBlur = 2 + layer; // Phản xạ nhẹ tăng dần
        ctx.shadowColor = `rgba(135, 206, 235, ${0.3 - layer * 0.1})`;
        ctx.stroke();
      }

      // Phản xạ sóng (mờ dần)
      ctx.globalAlpha = 0.2;
      ctx.fillStyle = 'rgba(135, 206, 235, 0.1)';
      ctx.fillRect(0, canvas.height / 2, canvas.width, canvas.height / 2);
      ctx.globalAlpha = 1.0;

      // Nhảy theo beat
      const timeSinceLastBeat = currentTime - lastBeatTime;
      if (timeSinceLastBeat >= beatInterval) {
        lastBeatTime = currentTime;
        ctx.globalAlpha = 0.3;
        ctx.fillStyle = 'rgba(0, 191, 255, 0.3)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.globalAlpha = 1.0;
      }
    }
  }
  draw();
}

// Thêm sự kiện cho volume control
volumeControl.addEventListener('input', () => {
  if (audio) audio.volume = volumeControl.value;
});

// Thêm sự kiện tua nhạc bằng thanh progress
barProgress.addEventListener('click', (e) => {
  const rect = barProgress.getBoundingClientRect();
  const percent = (e.clientX - rect.left) / rect.width;
  if (audio.duration) {
    audio.currentTime = percent * audio.duration;
  }
});
</script>
</body>
</html>