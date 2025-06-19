<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Streaming Platform</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
    <header>
        <h1>Welcome to the Music Streaming Platform</h1>
        <nav>
            <ul>
                <li><a href="/auth/login.php">Login</a></li>
                <li><a href="/auth/register.php">Register</a></li>
                <li><a href="/music/upload.php">Upload Music</a></li>
                <li><a href="/playlists/index.php">My Playlists</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <?php 
        $view = isset($view) ? $view : 'views/home.php'; // Gán giá trị mặc định
        include($view); 
        ?>
    </main>
    <footer>
        <p>© <?php echo date("Y"); ?> Music Streaming Platform. All rights reserved.</p>
    </footer>
    <script src="/../js/script.js"></script>
</body>
</html>