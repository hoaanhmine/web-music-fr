<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Quản trị nhạc' ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background: #f6f6f6;
        }
        .admin-nav {
            width: 210px;
            background: #23242a;
            padding: 30px 0 0 0;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            min-height: 100vh;
        }
        .admin-nav a {
            color: #fff;
            background: none;
            padding: 14px 28px;
            border-radius: 0 20px 20px 0;
            margin: 0 0 8px 0;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
            display: block;
        }
        .admin-nav a.active, .admin-nav a:hover {
            background: #007bff;
        }
        .admin-content {
            flex: 1;
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            padding: 40px 40px 30px 40px;
        }
        @media (max-width: 700px) {
            .admin-layout { flex-direction: column; }
            .admin-nav { flex-direction: row; width: 100%; min-height: unset; padding: 0; }
            .admin-nav a { border-radius: 0; padding: 10px 10px; margin: 0 4px 0 0; }
            .admin-content { margin: 0; padding: 15px; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <nav class="admin-nav">
            <a href="../index.php" style="background:#28a745;">Trang chính</a>
            <a href="upload_music.php" class="<?= basename($_SERVER['PHP_SELF']) == 'upload_music.php' ? 'active' : '' ?>">Thêm nhạc</a>
            <a href="admin_manage_music.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_manage_music.php' ? 'active' : '' ?>">Quản lý nhạc</a>
        </nav>
        <div class="admin-content">
            <?php if (isset($content)) echo $content; ?>
        </div>
    </div>
</body>
</html>