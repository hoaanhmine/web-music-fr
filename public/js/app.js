document.addEventListener('DOMContentLoaded', function() {
    let current = 0;
    let isRandom = false;
    let animationFrameId = null;

    const tracks = window.tracks || [];
    const musics = window.musics || [];

    const audio = document.getElementById('audio-player');
    const barCover = document.getElementById('bar-cover');
    const barProgress = document.getElementById('bar-progress');
    const barTime = document.getElementById('bar-time');
    const musicBar = document.getElementById('musicBar');
    const volumeControl = document.getElementById('volume-control');
    const playPauseButton = document.querySelector('.play-pause-button');
    const visualizer = document.getElementById('visualizer');
    const canvas = document.getElementById('visualizer-canvas');
    const ctx = canvas.getContext('2d');

    // Đảm bảo các phần tử tồn tại trước khi thao tác
    if (document.getElementById('btn-random')) {
        document.getElementById('btn-random').addEventListener('click', function() {
            isRandom = !isRandom;
            this.classList.toggle('active', isRandom);
        });
    }

    if (audio) {
        audio.onended = () => {
            if (isRandom) {
                let next;
                do {
                    next = Math.floor(Math.random() * tracks.length);
                } while (tracks.length > 1 && next === current);
                window.playTrack(next);
            } else {
                playNext();
            }
            playPauseButton.textContent = '▶';
            visualizer.classList.remove('active');
        };

        audio.ontimeupdate = () => {
            if (audio.duration && !isNaN(audio.duration)) {
                barProgress.value = audio.currentTime / audio.duration;
                barTime.textContent = formatTime(audio.currentTime) + ' / ' + formatTime(audio.duration);
            } else {    
                barProgress.value = 0;
                barTime.textContent = formatTime(0) + ' / ' + formatTime(0);
            }
        };
    }

    // Định nghĩa playTrack ở global scope
    window.playTrack = function(index) {
        if (!audio || !tracks[index]) return;
        current = index;
        audio.src = tracks[index];
        audio.play().catch(error => console.error('Error playing audio:', error));
        if (barCover && musics[index]) {
            barCover.src = musics[index].cover_image ? 'admin/' + musics[index].cover_image : 'https://via.placeholder.com/150';
        }
        if (musicBar) musicBar.classList.add('active');
        if (visualizer) visualizer.classList.add('active');
        if (musics[index]) startBeatAnimation(musics[index].bpm || 120);
        if (playPauseButton) playPauseButton.textContent = '❚❚';
        if (musics[index]) initVisualizer(musics[index].bpm || 120);
    };

    function playPause() {
        if (!audio) return;
        if (audio.paused) {
            audio.play().catch(error => console.error('Error playing audio:', error));
            if (visualizer) visualizer.classList.add('active');
            if (musics[current]) startBeatAnimation(musics[current].bpm || 120);
            if (playPauseButton) playPauseButton.textContent = '❚❚';
        } else {
            audio.pause();
            stopBeatAnimation();
            if (visualizer) visualizer.classList.remove('active');
            if (playPauseButton) playPauseButton.textContent = '▶';
        }
    }

    function playPrevious() {
        if (current > 0) window.playTrack(current - 1);
        else if (current === 0) window.playTrack(tracks.length - 1);
    }

    function playNext() {
        if (current + 1 < tracks.length) window.playTrack(current + 1);
        else window.playTrack(0);
    }

    function formatTime(s) {
        const m = Math.floor(s/60), sec = Math.floor(s%60);
        return `${m}:${sec<10?'0':''}${sec}`;
    }

    window.toggleDropdown = function(btn, id) {
        const dd = document.getElementById('dropdown-'+id);
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
    };

    window.closePlaylistModal = function() {
        document.getElementById('playlist-modal').style.display='none';
    };

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
            if (barCover) barCover.style.transform = `rotate(${angle}deg)`;

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
            if (barCover) barCover.style.transform = 'rotate(0deg)';
        }
    }

    // Khởi tạo và vẽ visualizer sóng biển với nhiều lớp
    function initVisualizer(bpm) {
        if (!canvas || !ctx) return;
        // Tăng độ phân giải canvas cho màn hình retina
        const dpr = window.devicePixelRatio || 1;
        canvas.width = canvas.offsetWidth * dpr;
        canvas.height = canvas.offsetHeight * dpr;
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);

        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const source = audioContext.createMediaElementSource(audio);
        const analyser = audioContext.createAnalyser();
        analyser.fftSize = 64; // Ít cột, giống hình mẫu
        const bufferLength = analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);

        source.connect(analyser);
        analyser.connect(audioContext.destination);

        function draw() {
            requestAnimationFrame(draw);
            if (!visualizer || !visualizer.classList.contains('active')) return;

            analyser.getByteFrequencyData(dataArray);

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const barWidth = (canvas.width / bufferLength) * 0.7;
            const gap = (canvas.width / bufferLength) * 0.3;
            for (let i = 0; i < bufferLength; i++) {
                const value = dataArray[i];
                const percent = value / 255;
                const barHeight = percent * canvas.height * 0.9;
                const x = i * (barWidth + gap);
                const y = canvas.height - barHeight;

                // Gradient màu xanh lá sang vàng
                const grad = ctx.createLinearGradient(x, y, x, y + barHeight);
                grad.addColorStop(0, "#fff700");
                grad.addColorStop(0.5, "#a8ff00");
                grad.addColorStop(1, "#00ff44");

                ctx.fillStyle = grad;
                ctx.fillRect(x, y, barWidth, barHeight);

                // Viền sáng cho cột
                ctx.save();
                ctx.shadowColor = "#fff700";
                ctx.shadowBlur = 10;
                ctx.globalAlpha = 0.5;
                ctx.fillRect(x, y, barWidth, barHeight * 0.1);
                ctx.restore();
            }
        }
        draw();
    }

    // Thêm sự kiện cho volume control
    if (volumeControl) {
        volumeControl.addEventListener('input', () => {
            if (audio) audio.volume = volumeControl.value;
        });
    }

    // Thêm sự kiện tua nhạc bằng thanh progress
    if (barProgress) {
        barProgress.addEventListener('click', (e) => {
            const rect = barProgress.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            if (audio && audio.duration) {
                audio.currentTime = percent * audio.duration;
            }
        });
    }
});

