<div class="sidebar">
  <h2>Playlist</h2>
  <?php if ($user_id): ?>
    <form method="post" style="margin-bottom:10px;">
      <input name="playlist_name" required placeholder="Tên playlist">
      <button name="create_playlist">Tạo</button>
    </form>
    <ul class="playlist-list">
      <?php foreach ($playlists as $pl): ?>
        <li>
          <span onclick="openPlaylistModal(<?= $pl['id'] ?>)"><?= htmlspecialchars($pl['name']) ?></span>
          <!-- Nút sửa -->
          <button onclick="event.preventDefault();editPlaylist(<?= $pl['id'] ?>, '<?= htmlspecialchars(addslashes($pl['name'])) ?>')">✏️</button>
          <!-- Nút xoá -->
          <form method="post" style="display:inline;" onsubmit="return confirm('Xoá playlist này?');">
            <input type="hidden" name="playlist_id" value="<?= $pl['id'] ?>">
            <button name="delete_playlist" style="color:red;">🗑️</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
    <!-- Form sửa playlist ẩn -->
    <form method="post" id="edit-playlist-form" style="display:none;margin-top:10px;">
      <input type="hidden" name="playlist_id" id="edit-playlist-id">
      <input name="playlist_name" id="edit-playlist-name" required>
      <button name="edit_playlist">Lưu</button>
      <button type="button" onclick="document.getElementById('edit-playlist-form').style.display='none'">Huỷ</button>
    </form>
    <script>
      function editPlaylist(id, name) {
        document.getElementById('edit-playlist-id').value = id;
        document.getElementById('edit-playlist-name').value = name;
        document.getElementById('edit-playlist-form').style.display = 'block';
      }
    </script>
  <?php endif; ?>
</div>