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