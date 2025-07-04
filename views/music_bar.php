<div id="musicBar">
  <img id="bar-cover" src="" style="width:40px;height:40px;object-fit:cover;">
  <button class="nav-button" onclick="playPrevious()">⏮️</button>
  <progress id="bar-progress" value="0" max="1"></progress>
  <span id="bar-time">0:00 / 0:00</span>
  <button class="play-pause-button" onclick="playPause()">▶</button>
  <button class="nav-button" onclick="playNext()">⏭️</button>
  <input type="range" id="volume-control" min="0" max="1" step="0.1" value="1">
  <audio id="audio-player"></audio>
  <!-- Thêm vào thanh điều khiển nhạc -->
  <button id="btn-random" title="Phát ngẫu nhiên">
    🔀
  </button>
</div>