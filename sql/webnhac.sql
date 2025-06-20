-- Tạo bảng người dùng
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng âm nhạc
CREATE TABLE musics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    composer VARCHAR(255),
    uploaded_by INT,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    cover_image VARCHAR(255),
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Tạo bảng danh sách nhạc (playlist)
CREATE TABLE playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Bảng liên kết playlist và nhạc (nhiều-nhiều)
CREATE TABLE playlist_music (
    playlist_id INT,
    music_id INT,
    PRIMARY KEY (playlist_id, music_id),
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (music_id) REFERENCES musics(id) ON DELETE CASCADE
);

-- Bảng nhạc yêu thích của người dùng
CREATE TABLE user_favorites (
    user_id INT,
    music_id INT,
    PRIMARY KEY (user_id, music_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (music_id) REFERENCES musics(id) ON DELETE CASCADE
    );


-- Thêm sau:

ALTER TABLE musics ADD COLUMN bpm INT DEFAULT 120;