<div class="main-content">
    <h2>Danh sách bài hát</h2>
    <ul class="music-list">
        <?php foreach ($musics as $i => $mus): ?>
            <li>
                <img src="<?= $mus['cover_image'] ? 'admin/' . $mus['cover_image'] : 'https://via.placeholder.com/150' ?>" alt="cover">
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
                <?php if ($mus['composer']): ?>
                    <span class="composer">(<?= htmlspecialchars($mus['composer']) ?>)</span>
                <?php endif; ?>
                <button class="play-music-button" onclick="playTrack(<?= $i ?>)">▶</button>
                <?php if ($user_id): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="music_id" value="<?= $mus['id'] ?>">
                        <button type="submit" name="add_favorite" style="background:none; border:none; color:#ff4444; cursor:pointer;">❤️</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>