<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['playlist_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$playlist_id = $_GET['playlist_id'];

$stmt = $pdo->prepare("SELECT p.name, m.* 
                       FROM playlists p 
                       LEFT JOIN playlist_music pm ON p.id = pm.playlist_id 
                       LEFT JOIN musics m ON pm.music_id = m.id 
                       WHERE p.id = ? AND p.user_id = ?");
$stmt->execute([$playlist_id, $user_id]);
$playlist_songs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM playlist_music WHERE playlist_id = ?");
$stmt->execute([$playlist_id]);
$count = $stmt->fetch()['count'];

echo json_encode(['songs' => $playlist_songs, 'count' => $count]);
