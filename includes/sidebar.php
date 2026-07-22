<?php
$user = currentUser();
$unread = countUnreadNotifications($user['id']);
$current = $current ?? '';
$roleName = ['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi / Approver'][$user['role']] ?? '';
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/assets/img/logo-new.png" alt="TNI AU" class="logo" onerror="this.src='<?= BASE_URL ?>/assets/img/logo-tniau.png'">
        <div class="brand-text">TNI ANGKATAN<br>UDARA</div>
    </div>
    <div class="sidebar-user" style="display:none;"> <!-- Hidden in new design -->
        <div class="name"><?= e($user['full_name']) ?></div>
        <div class="role"><span class="role-dot"></span> <?= e($roleName) ?></div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/news_list.php" class="<?= in_array($current,['list','draft','view','edit']) ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Daftar Berita</span>
        </a>
        
        <!-- Accordion: Dashboard -->
        <div class="sidebar-accordion">
            <div class="accordion-header <?= $current==='dashboard' ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon"></span><span class="label">Dashboard</span>
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
                <span class="icon"></span><span class="label">Statistik</span>
                <span class="chevron">▼</span>
            </div>
            <div class="accordion-content" style="<?= $current==='statistics' ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/statistics.php?view=berita" class="active">Statistik Berita</a>
                <a href="<?= BASE_URL ?>/statistics.php?view=tren">Tren</a>
                <a href="<?= BASE_URL ?>/statistics.php?view=aktor">Top Aktor</a>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/profile.php" class="<?= $current==='profile' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Profile</span>
        </a>

        <!-- Accordion: Report -->
        <div class="sidebar-accordion <?= $current==='report' ? 'open' : '' ?>">
            <div class="accordion-header <?= $current==='report' ? 'active' : '' ?>" onclick="toggleSidebarAccordion(this)">
                <span class="icon"></span><span class="label">Report</span>
                <span class="chevron">▼</span>
            </div>
            <div class="accordion-content" style="<?= $current==='report' ? 'display:block;' : '' ?>">
                <a href="<?= BASE_URL ?>/report.php?view=kontributor">Kontributor Informasi</a>
                <a href="<?= BASE_URL ?>/report.php?view=reviewer">Reviewer</a>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/gallery.php" class="<?= $current==='gallery' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Galeri Berita</span>
        </a>
        
        <a href="<?= BASE_URL ?>/support.php" class="<?= $current==='support' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Kontak Support</span>
        </a>
    </nav>
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
});
</script>
