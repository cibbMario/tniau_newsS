<?php
$currentView = $_GET['view'] ?? $_GET['media'] ?? $current ?? 'semua';
$user = currentUser();
$roleLabel = userDisplayName($user['role']);
$initials = strtoupper(substr($roleLabel, 0, 1));
?>

<!-- TOP NAVBAR -->
<header class="top-navbar" role="banner" data-base-url="<?= BASE_URL ?>">
    <div class="top-navbar-left">
        <button class="hamburger-btn" id="hamburgerBtn" title="Toggle Sidebar" aria-label="Toggle menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <nav class="media-tabs" aria-label="Filter sumber berita">
            <a href="<?= BASE_URL ?>/dashboard.php?view=semua"
               class="media-tab-item <?= in_array($currentView, ['semua','dashboard']) ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Semua Sumber
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=wilayah"
               class="media-tab-item <?= ($currentView === 'wilayah') ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Berita Wilayah
            </a>
            <span class="media-tab-divider"></span>

            <a href="<?= BASE_URL ?>/statistics.php"
               class="media-tab-item <?= ($current === 'statistics') ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Statistik
            </a>
            <a href="<?= BASE_URL ?>/report.php"
               class="media-tab-item <?= ($current === 'report') ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Report
            </a>
        </nav>
    </div>

    <div class="top-navbar-right">
        <!-- Search -->
        <form action="<?= BASE_URL ?>/news_list.php" method="GET" class="topbar-search-form" role="search">
            <div class="topbar-search-wrap">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="q" class="topbar-search-input" placeholder="Cari berita..." value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            </div>
        </form>

        <!-- Date badge -->
        <div class="topbar-date-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span><?= date('d M Y') ?></span>
        </div>

        <?php if (in_array($user['role'], ['A','B','C','D','E'])): ?>
            <div class="topbar-notification-wrap">
                <button type="button" id="notificationBell" class="topbar-notify-btn" title="Notifikasi" aria-label="Notifikasi">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="topbar-notify-badge" id="notificationBadge">0</span>
                </button>

                <div id="notificationDropdown" class="notification-dropdown" hidden>
                    <div class="notification-dropdown-header">
                        <strong>Notifikasi Terbaru</strong>
                        <a href="<?= BASE_URL ?>/notifications.php">Lihat semua</a>
                    </div>
                    <div id="notificationList" class="notification-list"></div>
                </div>

                <div id="notificationInlineHint" class="notification-inline-hint" hidden></div>
            </div>
        <?php endif; ?>

        <!-- User badge -->
        <div class="topbar-user-badge" title="<?= e($user['full_name']) ?> — <?= $roleLabel ?>">
            <div class="topbar-avatar"><?= $initials ?></div>
            <div class="topbar-user-info">
                <span class="topbar-user-name"><?= e($user['full_name']) ?></span>
                <span class="topbar-user-role"><?= $roleLabel ?></span>
            </div>
        </div>

        <?php if (in_array($user['role'], ['A','B','C','D','E'])): ?>
            <a href="<?= BASE_URL ?>/logout.php" id="topbarLogoutBtn" class="topbar-logout-btn" title="Keluar dari akun" aria-label="Keluar dari akun">
                <span class="logout-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </span>
                <span class="logout-text">Keluar</span>
            </a>
        <?php endif; ?>
    </div>
</header>
<script src="<?= BASE_URL ?>/assets/js/notifications.js?v=<?= time() ?>" defer></script>

