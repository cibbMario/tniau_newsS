<?php
$currentView = $_GET['view'] ?? $_GET['media'] ?? $current ?? 'semua';
$user = currentUser();
$roleLabel = ['A'=>'Reporter','B'=>'Editor','C'=>'Approver'][$user['role']] ?? 'User';
$initials = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
?>

<!-- TOP NAVBAR -->
<header class="top-navbar" role="banner">
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
               class="media-tab-item <?= in_array($currentView, ['wilayah']) ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Berita Wilayah
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=online"
               class="media-tab-item <?= in_array($currentView, ['online']) ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                Media Online
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=sosial"
               class="media-tab-item <?= in_array($currentView, ['sosial']) ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Media Sosial
            </a>

            <span class="media-tab-divider"></span>

            <a href="<?= BASE_URL ?>/dashboard.php?view=statistics"
               class="media-tab-item <?= in_array($currentView, ['statistics']) ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Statistik
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php?view=report"
               class="media-tab-item <?= in_array($currentView, ['report']) ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Report
            </a>
            <a href="<?= BASE_URL ?>/gallery.php"
               class="media-tab-item <?= in_array($currentView, ['gallery','galeri']) ? 'active' : '' ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Galeri Media
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

        <?php if (in_array($user['role'], ['A','B','C'])): ?>
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

        <?php if (in_array($user['role'], ['A','B','C'])): ?>
            <button type="button" id="topbarLogoutBtn" class="topbar-logout-btn" title="Keluar dari akun">
                <span class="logout-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </span>
                <span class="logout-text">Keluar</span>
            </button>
        <?php endif; ?>
    </div>
</header>

<!-- WORKSPACE TABS ROW (CHROME-STYLE) -->
<div class="workspace-tabs-row" id="workspaceTabsRow" role="tablist" aria-label="Tab halaman aktif">
    <!-- Populated dynamically by assets/js/tabs.js -->
</div>

<script src="<?= BASE_URL ?>/assets/js/tabs.js?v=<?= time() ?>"></script>

<script>
(function () {
    const baseUrl = '<?= BASE_URL ?>';
    const bell = document.getElementById('notificationBell');
    const dropdown = document.getElementById('notificationDropdown');
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');
    const inlineHint = document.getElementById('notificationInlineHint');
    const storageKey = 'tniau_notification_broadcast';
    const seenKey = 'tniau_last_seen_notification';
    const broadcastChannel = ('BroadcastChannel' in window) ? new BroadcastChannel('tniau_notification_channel') : null;
    let lastUnreadCount = 0;
    let lastItemId = 0;

    function formatShortDate(value) {
        if (!value) return '';
        const date = new Date(value);
        return date.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function renderNotificationList(items) {
        if (!list) return;
        if (!items.length) {
            list.innerHTML = '<div class="notification-empty">Belum ada notifikasi baru.</div>';
            return;
        }

        list.innerHTML = items.map(item => `
            <a class="notification-item ${item.is_read ? '' : 'unread'}" href="${baseUrl}/news_view.php?id=${item.news_id}&mark_read=${item.id}">
                <div class="notification-item-copy">
                    <strong>${item.message}</strong>
                    <span>${formatShortDate(item.created_at)}</span>
                </div>
            </a>
        `).join('');
    }

    function updateBadge(count) {
        if (!badge) return;
        const unread = Number(count || 0);
        badge.textContent = unread > 99 ? '99+' : unread;
        badge.style.display = unread > 0 ? 'inline-flex' : 'none';
    }

    function showInlineNotice(item) {
        if (!inlineHint) return;
        inlineHint.textContent = item.message;
        inlineHint.hidden = false;

        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification('Notifikasi Baru', {
                    body: item.message,
                    tag: 'tniau-notif-' + (item.id || Date.now())
                });
            } catch (error) {
                console.warn('Notification API failed:', error);
            }
        }

        clearTimeout(showInlineNotice.timer);
        showInlineNotice.timer = setTimeout(() => {
            inlineHint.hidden = true;
        }, 2600);
    }

    function publishNotificationPayload(payload) {
        if (broadcastChannel) {
            broadcastChannel.postMessage(payload);
        }

        if (typeof localStorage !== 'undefined') {
            localStorage.setItem(storageKey, JSON.stringify(payload));
        }
    }

    function handleIncoming(data) {
        const unreadCount = Number(data.unread_count || 0);
        const items = Array.isArray(data.items) ? data.items : [];
        updateBadge(unreadCount);
        renderNotificationList(items);

        const newestId = items.reduce((maxId, item) => Math.max(maxId, Number(item.id || 0)), 0);
        const seenId = Number(localStorage.getItem(seenKey) || 0);
        const shouldNotify = newestId > seenId;

        if (shouldNotify && items.length) {
            const nextItems = items.filter(item => Number(item.id || 0) > seenId);
            const firstNewItem = nextItems[0];

            if (firstNewItem) {
                showInlineNotice(firstNewItem);
                publishNotificationPayload({
                    id: firstNewItem.id,
                    message: firstNewItem.message,
                    ts: Date.now()
                });
                localStorage.setItem(seenKey, String(firstNewItem.id));
            }
        }

        lastUnreadCount = unreadCount;
        lastItemId = newestId;
    }

    async function fetchNotifications() {
        try {
            const response = await fetch(baseUrl + '/notifications_feed.php', { cache: 'no-store' });
            const data = await response.json();
            handleIncoming(data);
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    if (bell && dropdown) {
        bell.addEventListener('click', function (event) {
            event.preventDefault();
            const willShow = dropdown.hidden;
            dropdown.hidden = !willShow;
            bell.classList.toggle('active', willShow);
        });

        document.addEventListener('click', function (event) {
            if (!dropdown.hidden && !dropdown.contains(event.target) && !bell.contains(event.target)) {
                dropdown.hidden = true;
                bell.classList.remove('active');
            }
        });
    }

    if (typeof window !== 'undefined') {
        if (broadcastChannel) {
            broadcastChannel.addEventListener('message', function (event) {
                const payload = event.data;
                if (payload && payload.message) {
                    showInlineNotice(payload);
                    localStorage.setItem(seenKey, String(payload.id || 0));
                }
            });
        }

        window.addEventListener('storage', function (event) {
            if (event.key === storageKey && event.newValue) {
                const payload = JSON.parse(event.newValue);
                if (payload && payload.message) {
                    showInlineNotice(payload);
                    localStorage.setItem(seenKey, String(payload.id || 0));
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        fetchNotifications();
        setInterval(fetchNotifications, 15000);

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().catch(function () {});
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                fetchNotifications();
            }
        });

        window.addEventListener('focus', fetchNotifications);
    });
})();
</script>
