<?php
$user = currentUser();
$unread = countUnreadNotifications($user['id']);
$current = $current ?? '';
$roleName = ['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi / Approver'][$user['role']] ?? '';
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/assets/img/logo-new.png" alt="TNI AU" class="logo" onerror="this.src='<?= BASE_URL ?>/assets/img/logo-tniau.png'">
        <div class="brand-text">TNI Angkatan<br>Udara</div>
    </div>
    <div class="sidebar-user">
        <div class="name"><?= e($user['full_name']) ?></div>
        <div class="role"><span class="role-dot"></span> <?= e($roleName) ?></div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/news_list.php" class="<?= in_array($current,['list','draft','view','edit']) ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Daftar Berita</span>
        </a>
        <a href="<?= BASE_URL ?>/dashboard.php" class="<?= $current==='dashboard' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/statistics.php" class="<?= $current==='statistics' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Statistik</span>
        </a>
        <a href="<?= BASE_URL ?>/gallery.php" class="<?= $current==='gallery' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Galeri Media</span>
        </a>
        <?php if ($user['role'] === 'A'): ?>
        <a href="<?= BASE_URL ?>/news_create.php" class="<?= $current==='create' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Buat Berita</span>
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/report.php" class="<?= $current==='report' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Laporan</span>
        </a>
        <a href="<?= BASE_URL ?>/notifications.php" class="<?= $current==='notif' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Notifikasi</span>
            <?php if ($unread > 0): ?><span class="badge-count"><?= $unread ?></span><?php endif; ?>
        </a>
        <div class="nav-divider"></div>
        <a href="<?= BASE_URL ?>/profile.php" class="<?= $current==='profile' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Profil</span>
        </a>
        <a href="<?= BASE_URL ?>/support.php" class="<?= $current==='support' ? 'active' : '' ?>">
            <span class="icon"> </span><span class="label">Bantuan</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout.php"><span class="icon">🚪</span> <span>Keluar</span></a>
        <div class="onebox">DISPEN · TNIAU</div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ── Hamburger toggle ── */
    const hamburgerBtns = document.querySelectorAll('.hamburger-btn');
    const sidebar       = document.querySelector('.sidebar');
    const mainContent   = document.querySelector('.main-content');

    hamburgerBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    });

    /* ── Sidebar nav: hover micro-animation ── */
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
        link.addEventListener('mouseenter', function () {
            this.style.transition = 'all 0.28s cubic-bezier(0.34,1.56,0.64,1)';
        });
    });

    /* ── Workspace tab close button (non-functional UI decoration) ── */
    document.querySelectorAll('.close-tab').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            // Tabs are static; just visual feedback
        });
    });
});

</script>
