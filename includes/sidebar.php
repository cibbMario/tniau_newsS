<?php
$user = currentUser();
$unread = countUnreadNotifications($user['id']);
$current = $current ?? '';
$roleName = ['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi / Approver'][$user['role']] ?? '';
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/assets/img/logo-new.png" alt="TNI AU" class="logo" onerror="this.src='<?= BASE_URL ?>/assets/img/logo-tniau.png'">
        <div class="brand-text"><br></div>
    </div>
    <div class="sidebar-user" style="display:none;"> <!-- Hidden in new design -->
        <div class="name"><?= e($user['full_name']) ?></div>
        <div class="role"><span class="role-dot"></span> <?= e($roleName) ?></div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/news_list.php" class="<?= in_array($current,['list','draft','view','edit']) ? 'active' : '' ?>">
            <span class="icon">📰</span><span class="label">Daftar Berita</span>
        </a>
        
        <!-- Accordion: Dashboard -->
        <div class="sidebar-accordion <?= in_array($current, ['dashboard', 'dashboard_harian', 'berita_negatif', 'inspiratif', 'konten', 'sentimen']) ? 'open' : '' ?>">
            <div class="accordion-header <?= in_array($current, ['dashboard', 'dashboard_harian', 'berita_negatif', 'inspiratif', 'konten', 'sentimen']) ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon">📊</span><span class="label">Dashboard</span>
                <span class="chevron">▼</span>
            </div>
            <div class="accordion-content" style="<?= in_array($current, ['dashboard', 'dashboard_harian', 'berita_negatif', 'inspiratif', 'konten', 'sentimen']) ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/dashboard.php?view=harian" class="<?= $current==='dashboard_harian' ? 'active' : '' ?>">Dashboard Harian</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=negatif" class="<?= $current==='berita_negatif' ? 'active' : '' ?>">Berita Negatif</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=inspiratif" class="<?= $current==='inspiratif' ? 'active' : '' ?>">Inspiratif</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=konten" class="<?= $current==='konten' ? 'active' : '' ?>">Konten</a>
                <a href="<?= BASE_URL ?>/dashboard.php?view=sentimen" class="<?= $current==='sentimen' ? 'active' : '' ?>">Sentimen</a>
            </div>
        </div>

        <!-- Accordion: Statistik -->
        <div class="sidebar-accordion <?= $current==='statistics' ? 'open' : '' ?>">
            <div class="accordion-header <?= $current==='statistics' ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon">📈</span><span class="label">Statistik</span>
                <span class="chevron">▼</span>
            </div>
            <div class="accordion-content" style="<?= $current==='statistics' ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/statistics.php?view=berita" class="active">Statistik Berita</a>
                <a href="<?= BASE_URL ?>/statistics.php?view=tren">Tren</a>
                <a href="<?= BASE_URL ?>/statistics.php?view=aktor">Top Aktor</a>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/profile.php" class="<?= $current==='profile' ? 'active' : '' ?>">
            <span class="icon">👤</span><span class="label">Profile</span>
        </a>

        <!-- Accordion: Report -->
        <div class="sidebar-accordion <?= $current==='report' ? 'open' : '' ?>">
            <div class="accordion-header <?= $current==='report' ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon">📄</span><span class="label">Report</span>
                <span class="chevron">▼</span>
            </div>
            <div class="accordion-content" style="<?= $current==='report' ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/report.php?view=kontributor">Kontributor Informasi</a>
                <a href="<?= BASE_URL ?>/report.php?view=reviewer">Reviewer</a>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/gallery.php" class="<?= $current==='gallery' ? 'active' : '' ?>">
            <span class="icon">🖼️</span><span class="label">Galeri Berita</span>
        </a>
        
        <a href="<?= BASE_URL ?>/support.php" class="<?= $current==='support' ? 'active' : '' ?>">
            <span class="icon">💬</span><span class="label">Kontak Support</span>
        </a>
    </nav>
    
    <?php if (in_array($user['role'], ['A','B','C'])): ?>
        <div class="sidebar-footer">
            <button type="button" id="logoutBtn" class="fixed-logout-btn" title="Keluar dari akun">
                <span class="logout-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </span>
                <span class="logout-text label">Keluar Akun</span>
            </button>
        </div>

        <!-- MODAL OVERLAY LOGOUT -->
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
                    <form id="logoutForm" action="<?= BASE_URL ?>/logout.php" method="POST" style="margin:0;flex:1;">
                        <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
                        <button type="submit" class="modal-btn confirm" id="logoutConfirm">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Keluar Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</aside>

<script>
function toggleSidebarAccordion(el) {
    const parent = el.closest('.sidebar-accordion');
    const content = parent.querySelector('.accordion-content');
    const allAccordions = document.querySelectorAll('.sidebar-accordion');
    
    allAccordions.forEach(acc => {
        if(acc !== parent) {
            acc.classList.remove('open');
            acc.querySelector('.accordion-content').style.display = 'none';
        }
    });

    if (parent.classList.contains('open')) {
        parent.classList.remove('open');
        content.style.display = 'none';
    } else {
        parent.classList.add('open');
        content.style.display = 'block';
        content.style.animation = 'accordionSlideDown 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) forwards';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtns = document.querySelectorAll('.hamburger-btn');
    const sidebar       = document.querySelector('.sidebar');
    const mainContent   = document.querySelector('.main-content');

    hamburgerBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    });
    
    // Logout Modal Logic
    const logoutBtn      = document.getElementById('logoutBtn');
    const logoutModal    = document.getElementById('logoutModal');
    const logoutBackdrop = document.getElementById('logoutBackdrop');
    const logoutCancel   = document.getElementById('logoutCancel');
    const modalCloseX    = document.getElementById('modalCloseX');

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

    if (logoutBtn) logoutBtn.addEventListener('click', openModal);
    if (logoutBackdrop) logoutBackdrop.addEventListener('click', closeModal);
    if (logoutCancel) logoutCancel.addEventListener('click', closeModal);
    if (modalCloseX) modalCloseX.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && logoutModal && logoutModal.classList.contains('active')) {
            closeModal();
        }
    });
});
</script>

