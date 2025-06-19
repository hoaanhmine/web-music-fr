<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Music</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Upload Music Track</h1>
        <form action="/music/upload" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Track Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="artist">Artist Name:</label>
                <input type="text" id="artist" name="artist" required>
            </div>
            <div class="form-group">
                <label for="file">Select Music File:</label>
                <input type="file" id="file" name="file" accept="audio/*" required>
            </div>
            <button type="submit">Upload</button>
        </form>
    </div>
</body>
</html>