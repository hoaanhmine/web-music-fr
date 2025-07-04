// Định nghĩa các biến và hàm global trước
let current = 0;
let isRandom = false;

window.playTrack = function(index) {
    if (!window.audio || !window.tracks[index]) return;
    window.current = index;
    window.audio.src = window.tracks[index];
    window.audio.play().catch(error => {
        console.error('Error playing audio:', error);
        if (window.playPauseButton) window.playPauseButton.textContent = '▶';
        if (window.visualizer) window.visualizer.classList.remove('active');
    });
    if (window.barCover && window.musics[index]) {
        window.barCover.src = window.musics[index].cover_image ? 'admin/' + window.musics[index].cover_image : 'https://via.placeholder.com/150';
    }
    if (window.musicBar) window.musicBar.classList.add('active');
    if (window.visualizer) {
        window.visualizer.classList.add('active');
        if (window.musics[index]) window.initVisualizer(window.musics[index].bpm || 120); // Gọi global
    }
    if (window.playPauseButton) window.playPauseButton.textContent = '❚❚';
};

window.toggleDropdown = function(btn, id) {
    const dd = document.getElementById('dropdown-' + id);
    if (!dd) return;
    dd.classList.toggle('active');
    document.addEventListener('click', e => {
        if (!btn.contains(e.target) && !dd.contains(e.target)) dd.classList.remove('active');
    }, { once: true });
};

window.openPlaylistModal = function(pid) {
    fetch(`get_playlist.php?playlist_id=${pid}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('modal-title').textContent = window.musics.find(m => m.id == pid)?.name || 'Playlist';
            document.getElementById('modal-count').textContent = `${data.count} bài hát`;
            const tbody = document.getElementById('modal-body');
            tbody.innerHTML = '';
            data.songs.forEach((s, i) => {
                const idx = window.musics.findIndex(m => m.id === s.id);
                let tr = `<tr><td>${i + 1}</td><td><button onclick="playTrack(${idx})">▶</button> ${s.title}</td><td>${s.composer || ''}</td><td>${formatTime(s.duration || 0)}</td></tr>`;
                tbody.insertAdjacentHTML('beforeend', tr);
            });
            document.getElementById('playlist-modal').style.display = 'flex';
        })
        .catch(console.error);
};

window.closePlaylistModal = function() {
    document.getElementById('playlist-modal').style.display = 'none';
};

window.editPlaylist = function(id, name) {
    document.getElementById('edit-playlist-id').value = id;
    document.getElementById('edit-playlist-name').value = name;
    document.getElementById('edit-playlist-form').style.display = 'block';
};

// Định nghĩa các hàm khác sau khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    let animationFrameId = null;

    window.audio = document.getElementById('audio-player');
    window.barCover = document.getElementById('bar-cover');
    window.barProgress = document.getElementById('bar-progress');
    window.barTime = document.getElementById('bar-time');
    window.musicBar = document.getElementById('musicBar');
    window.volumeControl = document.getElementById('volume-control');
    window.playPauseButton = document.querySelector('.play-pause-button');
    window.visualizer = document.getElementById('visualizer');
    window.canvas = document.getElementById('visualizer-canvas');
    const ctx = window.canvas ? window.canvas.getContext('2d') : null;
    const btnRandom = document.getElementById('btn-random');

    // Định nghĩa initVisualizer ở phạm vi global
    window.initVisualizer = function(bpm) {
        if (!window.canvas || !ctx) {
            console.error('Canvas or context not available');
            return;
        }
        const dpr = window.devicePixelRatio || 1;
        window.canvas.width = window.canvas.offsetWidth * dpr;
        window.canvas.height = window.canvas.offsetHeight * dpr;
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);

        // Chỉ tạo audioContext và source một lần duy nhất
        if (!window._audioContext) {
            window._audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (!window._audioSource) {
            window._audioSource = window._audioContext.createMediaElementSource(window.audio);
            window._audioSource.connect(window._audioContext.destination);
        }

        // Luôn tạo mới analyser cho mỗi lần visualizer
        const analyser = window._audioContext.createAnalyser();
        analyser.fftSize = 64;
        const bufferLength = analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);

        window._audioSource.connect(analyser);

        function draw() {
            requestAnimationFrame(draw);
            if (!window.visualizer || !window.visualizer.classList.contains('active')) return;

            analyser.getByteFrequencyData(dataArray);

            ctx.clearRect(0, 0, window.canvas.width, window.canvas.height);

            const barWidth = (window.canvas.width / bufferLength) * 0.7;
            const gap = (window.canvas.width / bufferLength) * 0.3;
            for (let i = 0; i < bufferLength; i++) {
                const value = dataArray[i];
                const percent = value / 255;
                const barHeight = percent * window.canvas.height * 0.9;
                const x = i * (barWidth + gap);
                const y = window.canvas.height - barHeight;

                const grad = ctx.createLinearGradient(x, y, x, y + barHeight);
                grad.addColorStop(0, "#fff700");
                grad.addColorStop(0.5, "#a8ff00");
                grad.addColorStop(1, "#00ff44");

                ctx.fillStyle = grad;
                ctx.fillRect(x, y, barWidth, barHeight);

                ctx.save();
                ctx.shadowColor = "#fff700";
                ctx.shadowBlur = 10;
                ctx.globalAlpha = 0.5;
                ctx.fillRect(x, y, barWidth, barHeight * 0.1);
                ctx.restore();
            }
        }
        draw();
    };

    // Đảm bảo các phần tử tồn tại trước khi thao tác
    if (btnRandom) {
        btnRandom.addEventListener('click', function() {
            isRandom = !isRandom;
            this.classList.toggle('active', isRandom);
            this.title = isRandom ? 'Tắt phát ngẫu nhiên' : 'Phát ngẫu nhiên';
            if (isRandom) {
                this.style.backgroundColor = '#1ed760';
            } else {
                this.style.backgroundColor = '';
            }
        });
    }

    if (window.audio) {
        window.audio.onended = () => {
            if (isRandom) {
                let next;
                do {
                    next = Math.floor(Math.random() * window.tracks.length);
                } while (window.tracks.length > 1 && next === current);
                window.playTrack(next);
            } else {
                playNext();
            }
            if (window.playPauseButton) window.playPauseButton.textContent = '▶';
            if (window.visualizer) window.visualizer.classList.remove('active');
        };

        window.audio.ontimeupdate = () => {
            if (window.audio.duration && !isNaN(window.audio.duration)) {
                if (window.barProgress) window.barProgress.value = window.audio.currentTime / window.audio.duration;
                if (window.barTime) window.barTime.textContent = formatTime(window.audio.currentTime) + ' / ' + formatTime(window.audio.duration);
            } else {
                if (window.barProgress) window.barProgress.value = 0;
                if (window.barTime) window.barTime.textContent = formatTime(0) + ' / ' + formatTime(0);
            }
        };
    }

    function playPause() {
        if (!window.audio) return;
        if (window.audio.paused) {
            window.audio.play().catch(error => console.error('Error playing audio:', error));
            if (window.visualizer) {
                window.visualizer.classList.add('active');
                if (window.musics[current]) window.initVisualizer(window.musics[current].bpm || 120);
            }
            if (window.musics[current]) startBeatAnimation(window.musics[current].bpm || 120);
            if (window.playPauseButton) window.playPauseButton.textContent = '❚❚';
        } else {
            window.audio.pause();
            stopBeatAnimation();
            if (window.visualizer) window.visualizer.classList.remove('active');
            if (window.playPauseButton) window.playPauseButton.textContent = '▶';
        }
    }

    function playPrevious() {
        console.log('playPrevious called, current:', current);
        if (current > 0) window.playTrack(current - 1);
        else if (current === 0) window.playTrack(window.tracks.length - 1);
    }

    function playNext() {
        console.log('playNext called, current:', current, 'isRandom:', isRandom);
        if (isRandom) {
            let next;
            do {
                next = Math.floor(Math.random() * window.tracks.length);
            } while (window.tracks.length > 1 && next === current);
            window.playTrack(next);
        } else {
            if (current + 1 < window.tracks.length) window.playTrack(current + 1);
            else window.playTrack(0);
        }
    }

    function formatTime(s) {
        const m = Math.floor(s / 60), sec = Math.floor(s % 60);
        return `${m}:${sec < 10 ? '0' : ''}${sec}`;
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
            if (window.barCover) window.barCover.style.transform = `rotate(${angle}deg)`;

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
            if (window.barCover) window.barCover.style.transform = 'rotate(0deg)';
        }
    }

    // Thêm sự kiện cho volume control
    if (window.volumeControl) {
        window.volumeControl.addEventListener('input', () => {
            if (window.audio) window.audio.volume = window.volumeControl.value;
        });
    }

    // Thêm sự kiện tua nhạc bằng thanh progress
    if (window.barProgress) {
        window.barProgress.addEventListener('click', (e) => {
            const rect = window.barProgress.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            if (window.audio && window.audio.duration) {
                window.audio.currentTime = percent * window.audio.duration;
            }
        });
    }

    // Gắn sự kiện playPause cho nút nếu tồn tại
    if (window.playPauseButton) {
        window.playPauseButton.addEventListener('click', playPause);
    }

    // Gắn sự kiện cho các nút play trong music-list
    document.querySelectorAll('.play-music-button').forEach((button, index) => {
        button.addEventListener('click', () => window.playTrack(index));
    });

    // Gắn sự kiện cho nút playNext và playPrevious (fallback)
    const prevButton = document.querySelector('.nav-button[onclick="playPrevious()"]');
    const nextButton = document.querySelector('.nav-button[onclick="playNext()"]');
    if (prevButton) prevButton.addEventListener('click', playPrevious);
    if (nextButton) nextButton.addEventListener('click', playNext);
});