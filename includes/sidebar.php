<?php
$user = currentUser();
$unread = countUnreadNotifications($user['id']);
$current = $current ?? '';
$roleName = ['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi / Approver'][$user['role']] ?? '';
?>

<aside class="sidebar" id="appSidebar">
    <!-- SIDEBAR BRAND HEADER -->
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/assets/img/logo-tniau.png" alt="TNI AU" class="logo" onerror="this.src='<?= BASE_URL ?>/assets/img/logo-new.png'">
        <div class="brand-text"></div>    
    </div>

    <!-- SIDEBAR NAVIGATION -->
    <nav class="sidebar-nav">
        <!-- 1. Daftar Berita -->
        <a href="<?= BASE_URL ?>/news_list.php" class="<?= in_array($current,['list','draft','view','edit']) ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2m-4-3H9M7 16h6M7 8h6m-6 4h6"></path></svg>
            </span>
            <span class="label">Daftar Berita</span>
        </a>
        
        <!-- 2. Accordion: Dashboard -->
        <div class="sidebar-accordion <?= in_array($current, ['dashboard', 'dashboard_harian', 'berita_negatif', 'inspiratif', 'konten', 'sentimen']) ? 'open' : '' ?>">
            <div class="accordion-header <?= in_array($current, ['dashboard', 'dashboard_harian', 'berita_negatif', 'inspiratif', 'konten', 'sentimen']) ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </span>
                <span class="label">Dashboard</span>
                <span class="chevron">
                    <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
            </div>
            <div class="accordion-content" style="<?= in_array($current, ['dashboard', 'dashboard_harian', 'berita_negatif', 'inspiratif', 'konten', 'sentimen']) ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/dashboard.php?view=harian" class="<?= $current==='dashboard_harian' ? 'active' : '' ?>">Dashboard Harian</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=negatif" class="<?= $current==='berita_negatif' ? 'active' : '' ?>">Berita Negatif</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=inspiratif" class="<?= $current==='inspiratif' ? 'active' : '' ?>">Inspiratif</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=konten" class="<?= $current==='konten' ? 'active' : '' ?>">Konten</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=sentimen" class="<?= $current==='sentimen' ? 'active' : '' ?>">Sentimen</a>
            </div>
        </div>

        <!-- 3. Accordion: Statistik -->
        <?php $statsViews = ['berita','tren','aktor']; $isStatsActive = $current==='statistics'; ?>
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
            </div>
        </div>

        <!-- 4. Profile -->
        <a href="<?= BASE_URL ?>/profile.php" class="<?= $current==='profile' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </span>
            <span class="label">Profile</span>
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
                <a href="<?= BASE_URL ?>/report.php?view=kontributor" class="<?= ($_GET['view']??'')==='kontributor' ? 'active' : '' ?>">Kontributor Informasi</a>
                <a href="<?= BASE_URL ?>/report.php?view=reviewer" class="<?= ($_GET['view']??'')==='reviewer' ? 'active' : '' ?>">Reviewer</a>
            </div>
        </div>

        <!-- 6. Galeri Berita -->
        <a href="<?= BASE_URL ?>/gallery.php" class="<?= $current==='gallery' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            </span>
            <span class="label">Galeri Berita</span>
        </a>
        
        <!-- 7. Kontak Support -->
        <a href="<?= BASE_URL ?>/support.php" class="<?= $current==='support' ? 'active' : '' ?>">
            <span class="icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </span>
            <span class="label">Kontak Support</span>
        </a>

    </nav>
    
    <!-- MODAL OVERLAY LOGOUT -->
    <?php if (in_array($user['role'], ['A','B','C'])): ?>
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

    function toggleMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.toggle('mobile-open');
        if (mobileBackdrop) mobileBackdrop.classList.toggle('active');
    }

    hamburgerBtns.forEach(btn => {
        btn.addEventListener('click', toggleMobileSidebar);
    });

    if (mobileBackdrop) {
        mobileBackdrop.addEventListener('click', toggleMobileSidebar);
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

    if (logoutBtn) logoutBtn.addEventListener('click', function (event) {
        event.preventDefault();
        openModal();
    });
    if (logoutBackdrop) logoutBackdrop.addEventListener('click', closeModal);
    if (logoutCancel) logoutCancel.addEventListener('click', closeModal);
    if (modalCloseX) modalCloseX.addEventListener('click', closeModal);
    if (logoutConfirm) logoutConfirm.addEventListener('click', function () {
        window.location.href = '<?= BASE_URL ?>/logout.php';
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
            closeModal();
        }
    });
});
</script>
