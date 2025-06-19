<?php
// player.php

session_start();
require_once '../../models/Track.php';

if (!isset($_GET['id'])) {
    die('Track ID not specified.');
}

$trackId = $_GET['id'];
$track = Track::getTrackById($trackId);

if (!$track) {
    die('Track not found.');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <title><?php echo htmlspecialchars($track->title); ?> - Music Player</title>
</head>
<body>
    <div class="player">
        <h1><?php echo htmlspecialchars($track->title); ?></h1>
        <h2>Artist: <?php echo htmlspecialchars($track->artist); ?></h2>
        <audio controls>
            <source src="/uploads/<?php echo htmlspecialchars($track->file_path); ?>" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio>
    </div>
    <script src="/assets/js/player.js"></script>
</body>
</html>