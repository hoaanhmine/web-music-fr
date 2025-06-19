<?php
session_start();
require_once '../../models/Playlist.php';
require_once '../../controllers/PlaylistController.php';

$playlistController = new PlaylistController();
$userPlaylists = $playlistController->getUserPlaylists($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <title>Your Playlists</title>
</head>
<body>
    <?php include '../layout.php'; ?>

    <div class="container">
        <h1>Your Playlists</h1>
        <ul>
            <?php foreach ($userPlaylists as $playlist): ?>
                <li>
                    <a href="view.php?id=<?php echo $playlist->id; ?>"><?php echo htmlspecialchars($playlist->name); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="create.php" class="btn">Create New Playlist</a>
    </div>

    <script src="/assets/js/scripts.js"></script>
</body>
</html>