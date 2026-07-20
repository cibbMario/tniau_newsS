-- =========================================================
-- DATABASE: tniau_news
-- Sistem Portal Berita TNI AU
-- Alur: User A (Reporter) -> User B (Editor) -> User C (Approver)
-- =========================================================

CREATE DATABASE IF NOT EXISTS tniau_news CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tniau_news;

-- ---------------------------------------------------------
-- TABEL USERS
-- role: 'A' = Reporter (buat & edit berita)
--       'B' = Editor (koreksi tahap 1)
--       'C' = Approver / Petinggi (koreksi & approval akhir)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('A','B','C') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL NEWS (BERITA)
-- status flow:
--   draft         -> hanya A yg lihat, belum diajukan
--   pending_b     -> menunggu review User B
--   revision_b    -> B minta revisi, balik ke A
--   pending_c     -> lolos B, menunggu review User C
--   revision_c    -> C minta revisi, balik ke A
--   published     -> lolos C, tayang
-- ---------------------------------------------------------
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    status ENUM('draft','pending_b','revision_b','pending_c','revision_c','published') 
           NOT NULL DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_news_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE news
ADD FULLTEXT INDEX ft_news_search (title, content);

-- ---------------------------------------------------------
-- TABEL COMMENTS
-- type: 'comment'    -> komentar biasa
--       'correction' -> koreksi kata/kalimat
-- ---------------------------------------------------------
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('comment','correction') NOT NULL DEFAULT 'comment',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comment_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL NOTIFICATIONS
-- Notifikasi status berita (approved / revisi / dll)
-- ---------------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    news_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL NEWS_HISTORY (jejak audit perubahan status)
-- ---------------------------------------------------------
CREATE TABLE news_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    status_from VARCHAR(20) DEFAULT NULL,
    status_to VARCHAR(20) NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hist_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    CONSTRAINT fk_hist_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SEED USER
-- Jangan insert password_hash manual di SQL (rawan salah/hash palsu).
-- Setelah import schema ini, jalankan sekali file setup_users.php
-- di browser untuk membuat 3 akun contoh dengan password ter-hash
-- yang benar oleh PHP (password_hash). Hapus file itu setelah dipakai.
-- ---------------------------------------------------------

-- ---------------------------------------------------------
-- TABEL STATISTIK
-- ---------------------------------------------------------
CREATE TABLE statistik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    views_count INT DEFAULT 0,
    visitors_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL GALERI MEDIA
-- ---------------------------------------------------------
CREATE TABLE galeri_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_galeri_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL BERITA WILAYAH
-- ---------------------------------------------------------
CREATE TABLE berita_wilayah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    wilayah VARCHAR(100) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_berita_wil_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL MEDIA ONLINE
-- ---------------------------------------------------------
CREATE TABLE media_online (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- TABEL MEDIA SOSIAL
-- ---------------------------------------------------------
CREATE TABLE media_sosial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
