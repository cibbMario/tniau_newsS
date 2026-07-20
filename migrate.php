<?php
/**
 * Database Migration & Seeding Script
 * Run this file from the browser or terminal to update the schema and seed data.
 * URL: http://localhost/tniau-news/migrate.php
 */

require_once __DIR__ . '/config/db.php';

echo "<h2>Starting Database Migration...</h2>";

try {
    // 1. Helper function to check and add columns
    function addColumnIfNotExist($pdo, $table, $column, $definition) {
        $stmt = $pdo->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = ? 
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Added column: <strong>$column</strong> to table <strong>$table</strong>.<br>";
            return true;
        }
        echo "Column <strong>$column</strong> already exists in table <strong>$table</strong>.<br>";
        return false;
    }

    // 2. Add columns to news table
    addColumnIfNotExist($pdo, 'news', 'sentiment', "ENUM('Positif', 'Negatif', 'Netral') NOT NULL DEFAULT 'Positif'");
    addColumnIfNotExist($pdo, 'news', 'priority', "ENUM('High', 'Medium', 'Low') NOT NULL DEFAULT 'Medium'");
    addColumnIfNotExist($pdo, 'news', 'classification', "VARCHAR(100) NOT NULL DEFAULT 'Tni au'");
    addColumnIfNotExist($pdo, 'news', 'wilayah', "VARCHAR(100) DEFAULT NULL");
    addColumnIfNotExist($pdo, 'news', 'tempat', "VARCHAR(100) DEFAULT NULL");
    addColumnIfNotExist($pdo, 'news', 'media', "ENUM('Wilayah', 'Media Online', 'Media Sosial', 'Semua Sumber') NOT NULL DEFAULT 'Wilayah'");
    addColumnIfNotExist($pdo, 'news', 'aktor', "TEXT DEFAULT NULL");
    addColumnIfNotExist($pdo, 'news', 'tag', "TEXT DEFAULT NULL");
    addColumnIfNotExist($pdo, 'news', 'topik', "TEXT DEFAULT NULL");
    addColumnIfNotExist($pdo, 'news', 'keyword', "TEXT DEFAULT NULL");
    addColumnIfNotExist($pdo, 'news', 'author_label', "VARCHAR(100) DEFAULT NULL");
    addColumnIfNotExist($pdo, 'news', 'reviewer_id', "INT DEFAULT NULL");
    addColumnIfNotExist($pdo, 'news', 'published_at', "DATETIME DEFAULT NULL");
    
    // Add reviewer constraint if reviewer_id was added
    try {
        $pdo->exec("ALTER TABLE news ADD CONSTRAINT fk_news_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "Added foreign key constraint <strong>fk_news_reviewer</strong>.<br>";
    } catch (PDOException $e) {
        // Constraint might already exist
        echo "Foreign key constraint on <strong>reviewer_id</strong> check/skip: " . $e->getMessage() . "<br>";
    }

    // 3. Create news_images table for multi-image gallery
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS news_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            news_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_news_images FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");
    echo "Table <strong>news_images</strong> checked/created.<br>";
    // 3b. Create new requested tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS statistik (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL UNIQUE,
            views_count INT DEFAULT 0,
            visitors_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
        
        CREATE TABLE IF NOT EXISTS galeri_media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_path VARCHAR(255) NOT NULL,
            uploaded_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_galeri_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB;
        
        CREATE TABLE IF NOT EXISTS berita_wilayah (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            wilayah VARCHAR(100) NOT NULL,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_berita_wil_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB;
        
        CREATE TABLE IF NOT EXISTS media_online (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform_name VARCHAR(100) NOT NULL,
            url VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
        
        CREATE TABLE IF NOT EXISTS media_sosial (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform_name VARCHAR(100) NOT NULL,
            url VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");
    echo "New tables (statistik, galeri_media, berita_wilayah, media_online, media_sosial) checked/created.<br>";

    // 4. Seed default users: gilang, budi, chandra
    $defaultPassword = 'password123';
    $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

    $users = [
        ['username' => 'gilang',   'full_name' => 'Gilang (Reporter)',    'role' => 'A'],
        ['username' => 'budi',     'full_name' => 'Budi (Editor)',         'role' => 'B'],
        ['username' => 'chandra',  'full_name' => 'Chandra (Petinggi)',    'role' => 'C'],
    ];

    foreach ($users as $u) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$u['username']]);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$u['username'], $hash, $u['full_name'], $u['role']]);
            echo "Seeded user: <strong>{$u['username']}</strong>.<br>";
        } else {
            echo "User: <strong>{$u['username']}</strong> already exists, skipping.<br>";
        }
    }

    // Get Gilang's ID for author seeding
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'gilang' LIMIT 1");
    $stmt->execute();
    $gilangId = $stmt->fetchColumn();

    // 5. Seed sample news items to match screenshots
    $newsItems = [
        [
            'title' => "Danlanud Atang Sendjaja Selaku Pembina Yasarini Pengurus Cabang Buka MPLS Sekolah Angkasa Bogor Tahun Ajaran 2026/2027",
            'slug' => "danlanud-atang-sendjaja-selaku-pembina-yasarini-pengurus-cabang-buka-mpls-sekolah-angkasa-bogor-tahun-ajaran-2026-2027",
            'content' => "<p>Danlanud Atang Sendjaja Marsmo TNI A. F. Picaulima, S.Sos., selaku Pembina Yasarini Pengurus Cabang Lanud Atang Sendjaja secara resmi membuka Masa Pengenalan Lingkungan Sekolah (MPLS) pada Sekolah Angkasa Bogor Tahun Ajaran 2026/2027, Rabu (15/7/2026). Kegiatan tersebut menjadi momentum awal bagi peserta didik baru untuk mengenal lingkungan sekolah, budaya belajar, serta menanamkan nilai-nilai karakter sebagai bekal dalam menempuh proses pendidikan.</p><p>Pembukaan MPLS dihadiri Ketua Yasarini Pengurus Cabang Lanud Atang Sendjaja Ny. Cornelia Picaulima, para pejabat utama Lanud Atang Sendjaja, Pembina Harian PIA Ardhya Garini Pengurus Cabang 3/D.I Lanud Atang Sendjaja, para pengurus Yasarini, para kepala sekolah dan dewan guru Sekolah Angkasa di jajaran Yasarini Pengurus Cabang Lanud Atang Sendjaja, serta seluruh peserta didik baru dari berbagai jenjang pendidikan.</p><p>Dalam sambutannya, Danlanud Atang Sendjaja menyampaikan bahwa MPLS merupakan tahapan penting bagi peserta didik baru untuk beradaptasi dengan lingkungan sekolah, mengenal tata tertib, memahami budaya organisasi sekolah, serta membangun karakter yang berintegritas. Hal tersebut sejalan dengan upaya menciptakan lingkungan pendidikan yang aman, nyaman, inklusif, dan berorientasi pada pembentukan generasi penerus bangsa yang unggul.</p><p>MPLS Tahun Ajaran 2026/2027 mengusung tema \"Menggapai Cita-cita dalam Keberagaman Bersama Sekolah Angkasa yang Ramah, Aman, Nyaman, dan Menyenangkan\". Tema tersebut...</p>",
            'status' => 'published',
            'sentiment' => 'Positif',
            'priority' => 'Medium',
            'classification' => '9. Tni au',
            'wilayah' => 'Lanud Atang Sendjaja',
            'tempat' => 'Bogor',
            'media' => 'Wilayah',
            'aktor' => 'S.Sos, Danlanud Atang Sendjaja Marsmo TNI A. F. Picaulima, Ketua Yasarini Pengurus Cabang Lanud Atang Sendjaja Ny. Cornelia Picaulima',
            'tag' => 'Danlanud Atang Sendjaja Selaku Pembina Yasarini Pengurus Cabang Buka MPLS Sekolah Angkasa Bogor Tahun Ajaran 2026/2027',
            'topik' => 'Danlanud Atang Sendjaja Selaku Pembina Yasarini Pengurus Cabang Buka MPLS Sekolah Angkasa Bogor Tahun Ajaran 2026/2027',
            'keyword' => 'Danlanud Atang Sendjaja Selaku Pembina Yasarini Pengurus Cabang Buka MPLS Sekolah Angkasa Bogor Tahun Ajaran 2026/2027',
            'author_label' => 'pen ats',
            'created_at' => '2026-07-16 05:58:36'
        ],
        [
            'title' => "Danlanud Atang Sendjaja Perkuat Kolaborasi Ketahanan Pangan Sektor Peternakan Bersama PT Sumber Citarasa Alam",
            'slug' => "danlanud-atang-sendjaja-perkuat-kolaborasi-ketahanan-pangan-sektor-peternakan-bersama-pt-sumber-citarasa-alam",
            'content' => "<p>Danlanud Atang Sendjaja memperkuat kolaborasi program ketahanan pangan sektor peternakan bersama PT Sumber Citarasa Alam. Langkah ini bertujuan meningkatkan swasembada pangan hewani di wilayah Lanud Atang Sendjaja dan sekitarnya.</p>",
            'status' => 'published',
            'sentiment' => 'Positif',
            'priority' => 'Medium',
            'classification' => '9. Tni au',
            'wilayah' => 'Lanud Atang Sendjaja',
            'tempat' => 'Bogor',
            'media' => 'Wilayah',
            'aktor' => 'Danlanud Atang Sendjaja, Direktur PT Sumber Citarasa Alam',
            'tag' => 'Danlanud Atang Sendjaja Perkuat Kolaborasi Ketahanan Pangan Sektor Peternakan Bersama PT Sumber Citarasa Alam',
            'topik' => 'Danlanud Atang Sendjaja Perkuat Kolaborasi Ketahanan Pangan Sektor Peternakan Bersama PT Sumber Citarasa Alam',
            'keyword' => 'Danlanud Atang Sendjaja Perkuat Kolaborasi Ketahanan Pangan Sektor Peternakan Bersama PT Sumber Citarasa Alam',
            'author_label' => 'pen ats',
            'created_at' => '2026-07-16 05:55:00'
        ],
        [
            'title' => "Danlanud Dhomber Bersama Personel Meriahkan Jalan Santai Dan Aerobik Ceria HUT Ke-68 Kodam VI/Mulawarman",
            'slug' => "danlanud-dhomber-bersama-personel-meriahkan-jalan-santai-dan-aerobik-ceria-hut-ke-68-kodam-vi-mulawarman",
            'content' => "<p>Danlanud Dhomber beserta segenap personel memeriahkan acara jalan santai dan senam aerobik ceria dalam rangka menyambut HUT ke-68 Kodam VI/Mulawarman. Kegiatan ini dihadiri oleh berbagai unsur TNI dan Polri serta masyarakat Balikpapan.</p>",
            'status' => 'published',
            'sentiment' => 'Positif',
            'priority' => 'Medium',
            'classification' => '9. Tni au',
            'wilayah' => 'Lanud Dhomber',
            'tempat' => 'Balikpapan',
            'media' => 'Wilayah',
            'aktor' => 'Danlanud Dhomber, Personel Lanud Dhomber',
            'tag' => 'Danlanud Dhomber Bersama Personel Meriahkan Jalan Santai Dan Aerobik Ceria HUT Ke-68 Kodam VI/Mulawarman',
            'topik' => 'Danlanud Dhomber Bersama Personel Meriahkan Jalan Santai Dan Aerobik Ceria HUT Ke-68 Kodam VI/Mulawarman',
            'keyword' => 'Danlanud Dhomber Bersama Personel Meriahkan Jalan Santai Dan Aerobik Ceria HUT Ke-68 Kodam VI/Mulawarman',
            'author_label' => 'pen dmb',
            'created_at' => '2026-07-16 05:52:00'
        ]
    ];

    foreach ($newsItems as $item) {
        $check = $pdo->prepare("SELECT id FROM news WHERE slug = ?");
        $check->execute([$item['slug']]);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO news (
                    title, slug, content, status, sentiment, priority, 
                    classification, wilayah, tempat, media, aktor, tag, 
                    topik, keyword, author_label, created_by, created_at, published_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            $stmt->execute([
                $item['title'], $item['slug'], $item['content'], $item['status'], $item['sentiment'], $item['priority'],
                $item['classification'], $item['wilayah'], $item['tempat'], $item['media'], $item['aktor'], $item['tag'],
                $item['topik'], $item['keyword'], $item['author_label'], $gilangId, $item['created_at'], $item['created_at']
            ]);
            echo "Seeded news: <em>{$item['title']}</em>.<br>";
        } else {
            echo "News: <em>{$item['title']}</em> already exists, skipping.<br>";
        }
    }

    echo "<h3 style='color:green;'>Migration completed successfully!</h3>";
    echo "<p>You can now log in using:</p>";
    echo "<ul>";
    echo "<li>Username: <strong>gilang</strong>, Password: <strong>password123</strong> (Reporter)</li>";
    echo "<li>Username: <strong>budi</strong>, Password: <strong>password123</strong> (Editor)</li>";
    echo "<li>Username: <strong>chandra</strong>, Password: <strong>password123</strong> (Petinggi)</li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Migration failed: " . $e->getMessage() . "</h3>";
}
