<?php
$currentView = $_GET['view'] ?? $_GET['media'] ?? $current ?? 'semua';
$user = currentUser();
?>

<!-- TOP NAVBAR -->
<div class="top-navbar">
    <div class="top-navbar-left">
        <button class="hamburger-btn" title="Toggle Sidebar Menu">☰ Menu</button>
        <div class="media-tabs">
            <a href="<?= BASE_URL ?>/dashboard.php?view=semua" 
               class="media-tab-item <?= in_array($currentView, ['semua', 'dashboard']) ? 'active' : '' ?>">
               🏠 Semua Sumber
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=wilayah" 
               class="media-tab-item <?= in_array($currentView, ['wilayah', 'Wilayah']) ? 'active' : '' ?>">
               📍 Berita Wilayah
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=online" 
               class="media-tab-item <?= in_array($currentView, ['online', 'Media Online']) ? 'active' : '' ?>">
               🌐 Media Online
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=sosial" 
               class="media-tab-item <?= in_array($currentView, ['sosial', 'Media Sosial']) ? 'active' : '' ?>">
               📱 Media Sosial
            </a>

            <span class="media-tab-divider"></span>

            <a href="<?= BASE_URL ?>/dashboard.php?view=statistics" 
               class="media-tab-item <?= in_array($currentView, ['statistics', 'statistics.php']) ? 'active' : '' ?>">
               📊 Statistik
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=report" 
               class="media-tab-item <?= in_array($currentView, ['report', 'report.php']) ? 'active' : '' ?>">
               📄 Report
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=gallery" 
               class="media-tab-item <?= in_array($currentView, ['gallery', 'gallery.php']) ? 'active' : '' ?>">
               🖼️ Galeri Media
            </a>
        </div>
    </div>

    <div class="top-navbar-right">
        <form action="<?= BASE_URL ?>/news_list.php" method="GET" style="display:flex;gap:6px;align-items:center">
            <input type="text" name="q" class="form-input" placeholder="🔍 Cari berita..." 
                   value="<?= e($_GET['q'] ?? '') ?>" style="width:180px;height:34px;font-size:12px;border-radius:20px">
            <button type="submit" class="top-action-btn">Cari</button>
        </form>
        <span class="top-action-btn">📅 <?= date('d M Y') ?></span>
        <span class="top-action-btn" title="Pengguna saat ini">👤 <?= e($user['full_name']) ?></span>
    </div>
</div>

<!-- WORKSPACE TABS ROW (GOOGLE CHROME-STYLE DYNAMIC TABS) -->
<div class="workspace-tabs-row" id="workspaceTabsRow">
    <!-- Populated dynamically by assets/js/tabs.js -->
</div>

<script src="<?= BASE_URL ?>/assets/js/tabs.js?v=<?= time() ?>"></script>
