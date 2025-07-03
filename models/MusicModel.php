<?php
class MusicModel {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllMusics() {
        return $this->pdo->query("SELECT * FROM musics")->fetchAll();
    }

    public function getUserPlaylists($user_id) {
        $stmt = $this->pdo->prepare("SELECT id, name FROM playlists WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function createPlaylist($user_id, $name) {
        $stmt = $this->pdo->prepare("INSERT INTO playlists (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);
    }

    public function addToPlaylist($playlist_id, $music_id) {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO playlist_music (playlist_id, music_id) VALUES (?, ?)");
        $stmt->execute([$playlist_id, $music_id]);
    }

    public function addFavorite($user_id, $music_id) {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO user_favorites (user_id, music_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $music_id]);
    }

    public function updatePlaylist($playlist_id, $user_id, $name) {
        $stmt = $this->pdo->prepare("UPDATE playlists SET name=? WHERE id=? AND user_id=?");
        $stmt->execute([$name, $playlist_id, $user_id]);
    }

    public function deletePlaylist($playlist_id, $user_id) {
        // Xoá các bản ghi liên quan trước
        $this->pdo->prepare("DELETE FROM playlist_music WHERE playlist_id=?")->execute([$playlist_id]);
        $this->pdo->prepare("DELETE FROM playlists WHERE id=? AND user_id=?")->execute([$playlist_id, $user_id]);
    }
}
?>