<div class="header">
  <div class="auth-buttons">
    <?php if ($user_id): ?>
      <form method="post" action="taikhoan/logout.php" style="display:inline;">
        <button>Đăng xuất</button>
      </form>
      <?php if ($role === 'admin'): ?>
        <a href="admin/admin_manage_music.php">Quản lý nhạc</a>
      <?php else: ?>
        <a href="admin/upload_music.php">Thêm nhạc</a>
      <?php endif; ?>
    <?php else: ?>
      <a href="taikhoan/login.php">Đăng nhập</a>
      <a href="taikhoan/register.php">Đăng ký</a>
    <?php endif; ?>
  </div>
</div>