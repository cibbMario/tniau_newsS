<?php
$user = currentUser();
$unread = countUnreadNotifications($user['id']);
$current = $current ?? '';
$roleName = userDisplayName($user['role']);
?>

<aside class="sidebar" id="appSidebar">
    <!-- SIDEBAR BRAND HEADER -->
    <div class="sidebar-brand" style="justify-content: center; display: flex; align-items: center; gap: 12px; padding: 10px 14px;">
        <img src="<?= BASE_URL ?>/assets/img/logo-tniau-transparent.png" alt="TNI AU" class="logo" style="height: 44px; width: auto; max-width: 100%; object-fit: contain;">
        <div class="brand-text" style="display: flex; flex-direction: column; text-align: left;">
            <span style="font-size: 16px; font-weight: 500; color: #ffffff; letter-spacing: 0; word-spacing: -1px; line-height: 1.1;">NEWS PORTAL</span>
            <span style="font-size: 18px; font-weight: 400; color: #c9a227; letter-spacing: 0; word-spacing: -1px; line-height: 1.1;">TNI AU</span>
        </div>
    </div>

    <!-- SIDEBAR NAVIGATION -->
    <nav class="sidebar-nav">
        <!-- User D Dedicated Page -->
        <?php if (in_array($user['role'], ['D','E'])): ?>
        <a href="<?= BASE_URL ?>/user_d_dashboard.php" class="<?= $current==='user_d' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </span>
            <span class="label">Dashboard User D</span>
        </a>
        <?php endif; ?>


        <!-- 1. Daftar Berita -->
        <a href="<?= BASE_URL ?>/news_list.php" class="<?= in_array($current, ['list', 'view', 'edit']) ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2m-4-3H9M7 16h6M7 8h6m-6 4h6"></path></svg>
            </span>
            <span class="label">Daftar Berita</span>
        </a>
        <?php if (in_array($user['role'], ['A','E'])):
            $draftCount = countDraftsForUser($user['id']);
        ?>
        <a href="<?= BASE_URL ?>/news_list.php?status=draft" class="<?= $current === 'draft' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z"/><path d="M9 7h6M9 11h6M9 15h3"/></svg>
            </span>
            <span class="label">Draft Berita</span>
            <?php if ($draftCount > 0): ?>
                <span class="badge badge-gray" style="margin-left:auto;font-size:10px;padding:3px 7px;min-width:20px;text-align:center"><?= $draftCount ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        
        <!-- 2. Accordion: Dashboard -->
        <?php 
        $isDashboardActive = ($current === 'dashboard' || in_array($current, ['dashboard_harian', 'berita_negatif', 'inspiratif', 'konten', 'sentimen']));
        $dashView = $_GET['view'] ?? 'harian';
        if ($dashView === 'dashboard' || $dashView === 'semua') $dashView = 'harian';
        ?>
        <div class="sidebar-accordion <?= $isDashboardActive ? 'open' : '' ?>">
            <div class="accordion-header <?= $isDashboardActive ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </span>
                <span class="label">Dashboard</span>
                <span class="chevron">
                    <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
            </div>
            <div class="accordion-content" style="<?= $isDashboardActive ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/dashboard.php?view=harian" class="<?= ($isDashboardActive && ($dashView === 'harian' || $current === 'dashboard_harian')) ? 'active' : '' ?>">Dashboard Harian</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=negatif" class="<?= ($isDashboardActive && ($dashView === 'negatif' || $current === 'berita_negatif')) ? 'active' : '' ?>">Berita Negatif</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=inspiratif" class="<?= ($isDashboardActive && ($dashView === 'inspiratif' || $current === 'inspiratif')) ? 'active' : '' ?>">Inspiratif</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=konten" class="<?= ($isDashboardActive && ($dashView === 'konten' || $current === 'konten')) ? 'active' : '' ?>">Konten</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=sentimen" class="<?= ($isDashboardActive && ($dashView === 'sentimen' || $current === 'sentimen')) ? 'active' : '' ?>">Sentimen</a>
            </div>
        </div>

        <!-- 3. Accordion: Statistik -->
        <?php $statsViews = ['berita','tren','aktor','peta']; $isStatsActive = $current==='statistics'; ?>
        <div class="sidebar-accordion <?= $isStatsActive ? 'open' : '' ?>">
            <div class="accordion-header <?= $isStatsActive ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                </span>
                <span class="label">Statistik</span>
                <span class="chevron">
                    <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
            </div>
            <div class="accordion-content" style="<?= $isStatsActive ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/statistics.php?view=berita" class="<?= ($current==='statistics' && ($_GET['view']??'berita')==='berita') ? 'active' : '' ?>">Statistik Berita</a>
                <a href="<?= BASE_URL ?>/statistics.php?view=tren"   class="<?= ($current==='statistics' && ($_GET['view']??'')==='tren')   ? 'active' : '' ?>">Tren</a>
                <a href="<?= BASE_URL ?>/statistics.php?view=aktor"  class="<?= ($current==='statistics' && ($_GET['view']??'')==='aktor')  ? 'active' : '' ?>">Top Aktor</a>
                <a href="<?= BASE_URL ?>/statistics.php?view=peta"   class="<?= ($current==='statistics' && ($_GET['view']??'')==='peta')   ? 'active' : '' ?>">Peta Sebaran Lanud</a>
            </div>
        </div>



        <!-- 4. Profile -->
        <a href="<?= BASE_URL ?>/profile.php" class="<?= $current==='profile' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </span>
            <span class="label">Profile</span>
        </a>
        <a href="<?= BASE_URL ?>/change_password.php" class="<?= $current==='change_password' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </span>
            <span class="label">Ganti Password</span>
        </a>

        <!-- 5. Accordion: Report -->
        <div class="sidebar-accordion <?= $current==='report' ? 'open' : '' ?>">
            <div class="accordion-header <?= $current==='report' ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                </span>
                <span class="label">Report</span>
                <span class="chevron">
                    <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
            </div>
            <div class="accordion-content" style="<?= $current==='report' ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/report.php?view=kontributor" class="<?= ($current==='report' && ($_GET['view']??'kontributor')==='kontributor') ? 'active' : '' ?>">Kontributor Informasi</a>
                <a href="<?= BASE_URL ?>/report.php?view=reviewer" class="<?= ($current==='report' && ($_GET['view']??'')==='reviewer') ? 'active' : '' ?>">Reviewer</a>
            </div>
        </div>

        <!-- 6. Manajemen Pengguna & Audit Trail (Role C, E) -->
        <?php if (in_array($user['role'], ['C', 'E'])): ?>
        <a href="<?= BASE_URL ?>/users_management.php" class="<?= in_array($current, ['users_management', 'audit_log']) ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </span>
            <span class="label">Manajemen Pengguna</span>
        </a>
        <?php endif; ?>

        <!-- 7. Riwayat Berita -->
        <a href="<?= BASE_URL ?>/news_history.php" class="<?= $current==='news_history' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                </svg>
            </span>
            <span class="label">Riwayat Berita</span>
        </a>

        <!-- 8. Notifikasi -->
        <a href="<?= BASE_URL ?>/notifications.php" class="<?= $current==='notif' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </span>
            <span class="label">Notifikasi</span>
            <?php if ($unread > 0): ?>
                <span class="badge badge-red" style="margin-left:auto;font-size:10px;padding:3px 7px;min-width:20px;text-align:center"><?= $unread ?></span>
            <?php endif; ?>
        </a>

        <!-- 9. Kontak Support -->
        <a href="<?= BASE_URL ?>/support.php" class="<?= $current==='support' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </span>
            <span class="label">Kontak Support</span>
        </a>

    </nav>
    
    <!-- MODAL OVERLAY LOGOUT -->
    <?php if (in_array($user['role'], ['A','B','C','D','E'])): ?>
        <div id="logoutModal" class="modal-overlay" role="dialog" aria-modal="true">
            <div class="modal-backdrop" id="logoutBackdrop"></div>
            <div class="modal-box">
                <button type="button" class="modal-close-icon" id="modalCloseX" aria-label="Tutup">&times;</button>
                <div class="modal-icon-badge">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e63946" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <h3 class="modal-title">Konfirmasi Keluar</h3>
                <p class="modal-desc">Apakah Anda yakin ingin keluar dari sistem <strong>Portal Berita TNI AU</strong>?</p>
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel" id="logoutCancel">Batal</button>
                    <button type="button" class="modal-btn confirm" id="logoutConfirm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Keluar Sekarang
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</aside>

<!-- MOBILE BACKDROP OVERLAY -->
<div class="sidebar-mobile-backdrop" id="sidebarMobileBackdrop"></div>

<script>
function toggleSidebarAccordion(el) {
    const parent = el.closest('.sidebar-accordion');
    const content = parent.querySelector('.accordion-content');
    const allAccordions = document.querySelectorAll('.sidebar-accordion');
    
    allAccordions.forEach(acc => {
        if(acc !== parent) {
            acc.classList.remove('open');
            const accContent = acc.querySelector('.accordion-content');
            if (accContent) accContent.style.display = 'none';
        }
    });

    if (parent.classList.contains('open')) {
        parent.classList.remove('open');
        content.style.display = 'none';
    } else {
        parent.classList.add('open');
        content.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtns         = document.querySelectorAll('.hamburger-btn');
    const sidebar               = document.getElementById('appSidebar');
    const mobileBackdrop        = document.getElementById('sidebarMobileBackdrop');
    const mainContent           = document.querySelector('.main-content');

    function isMobile() {
        return window.innerWidth <= 768;
    }

    // Restore collapsed state from localStorage
    const COLLAPSE_KEY = 'sidebar_collapsed';
    if (!isMobile() && localStorage.getItem(COLLAPSE_KEY) === '1') {
        document.body.classList.add('sidebar-collapsed');
    }

    function toggleSidebar() {
        if (!sidebar) return;
        if (isMobile()) {
            sidebar.classList.toggle('mobile-open');
            if (mobileBackdrop) mobileBackdrop.classList.toggle('active');
        } else {
            const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem(COLLAPSE_KEY, isCollapsed ? '1' : '0');
        }
    }

    hamburgerBtns.forEach(btn => {
        btn.addEventListener('click', toggleSidebar);
    });

    // On resize: if going to mobile, remove sidebar-collapsed; if going to desktop, close drawer
    window.addEventListener('resize', function () {
        if (isMobile()) {
            // On mobile, sidebar-collapsed is irrelevant — drawer controls visibility
            document.body.classList.remove('sidebar-collapsed');
            if (mobileBackdrop) mobileBackdrop.classList.remove('active');
        } else {
            // On desktop, close any open mobile drawer
            sidebar.classList.remove('mobile-open');
            if (mobileBackdrop) mobileBackdrop.classList.remove('active');
            // Restore collapsed state from localStorage for desktop
            if (localStorage.getItem(COLLAPSE_KEY) === '1') {
                document.body.classList.add('sidebar-collapsed');
            }
        }
    });

    if (mobileBackdrop) {
        mobileBackdrop.addEventListener('click', toggleSidebar);
    }
    
    // Logout Modal Logic
    const logoutBtn      = document.getElementById('topbarLogoutBtn');
    const logoutModal    = document.getElementById('logoutModal');
    const logoutBackdrop = document.getElementById('logoutBackdrop');
    const logoutCancel   = document.getElementById('logoutCancel');
    const modalCloseX    = document.getElementById('modalCloseX');
    const logoutConfirm  = document.getElementById('logoutConfirm');

    function openModal() {
        if (!logoutModal) return;
        logoutModal.style.display = 'flex';
        setTimeout(() => logoutModal.classList.add('active'), 10);
    }

    function closeModal() {
        if (!logoutModal) return;
        logoutModal.classList.remove('active');
        setTimeout(() => logoutModal.style.display = 'none', 250);
    }

    if (logoutBtn && logoutBtn.tagName !== 'A') {
        logoutBtn.addEventListener('click', function (event) {
            event.preventDefault();
            openModal();
        });
    }
    if (logoutBackdrop) logoutBackdrop.addEventListener('click', closeModal);
    if (logoutCancel) logoutCancel.addEventListener('click', closeModal);
    if (modalCloseX) modalCloseX.addEventListener('click', closeModal);
    if (logoutConfirm) logoutConfirm.addEventListener('click', function () {
        window.location.assign('<?= BASE_URL ?>/logout.php');
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
            closeModal();
        }
    });
});
</script>
