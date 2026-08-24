<?php
/**
 * Koneksi Database menggunakan PDO
 * Sesuaikan DB_HOST, DB_NAME, DB_USER, DB_PASS dengan server-mu.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'tniau_news');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // Auto-ensure required columns exist on 'news' table
    try {
        static $schemaChecked = false;
        if (!$schemaChecked) {
            $schemaChecked = true;
            $requiredColumns = [
                'sentiment'      => "ENUM('Positif', 'Negatif', 'Netral') NOT NULL DEFAULT 'Positif'",
                'priority'       => "ENUM('High', 'Medium', 'Low') NOT NULL DEFAULT 'Medium'",
                'classification' => "VARCHAR(100) NOT NULL DEFAULT 'Tni au'",
                'wilayah'        => "VARCHAR(100) DEFAULT NULL",
                'tempat'         => "VARCHAR(100) DEFAULT NULL",
                'media'          => "ENUM('Wilayah', 'Media Online', 'Media Sosial', 'Semua Sumber') NOT NULL DEFAULT 'Wilayah'",
                'aktor'          => "TEXT DEFAULT NULL",
                'tag'            => "TEXT DEFAULT NULL",
                'topik'          => "TEXT DEFAULT NULL",
                'keyword'        => "TEXT DEFAULT NULL",
                'author_label'   => "VARCHAR(100) DEFAULT NULL",
                'reviewer_id'    => "INT DEFAULT NULL",
                'published_at'   => "DATETIME DEFAULT NULL"
            ];

            $existing = $pdo->query("SHOW COLUMNS FROM news")->fetchAll(PDO::FETCH_COLUMN);
            if ($existing) {
                foreach ($requiredColumns as $col => $def) {
                    if (!in_array($col, $existing)) {
                        $pdo->exec("ALTER TABLE `news` ADD COLUMN `$col` $def");
                    }
                }
            }
        }
    } catch (Exception $schemaEx) {
        // Silently skip if news table is not created yet
    }

} catch (PDOException $e) {
    
    die("Koneksi database gagal: " . $e->getMessage());
}
