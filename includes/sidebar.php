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
        <?php if ($user['role'] === 'A'): ?>
        <a href="<?= BASE_URL ?>/news_create.php" class="<?= $current==='create' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Buat Berita</span>
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/statistics.php" class="<?= $current==='statistics' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Statistik</span>
        </a>
        <div class="nav-divider"></div>
        <a href="<?= BASE_URL ?>/gallery.php" class="<?= $current==='gallery' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Galeri Media</span>
        </a>
        <a href="<?= BASE_URL ?>/berita_wilayah.php" class="<?= $current==='berita_wilayah' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Berita Wilayah</span>
        </a>
        <a href="<?= BASE_URL ?>/media_online.php" class="<?= $current==='media_online' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Media Online</span>
        </a>
        <a href="<?= BASE_URL ?>/media_sosial.php" class="<?= $current==='media_sosial' ? 'active' : '' ?>">
            <span class="icon"></span><span class="label">Media Sosial</span>
        </a>
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
            <span class="icon"></span><span class="label">Bantuan</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout.php"><span class="icon"></span> Keluar</a>
        <div class="onebox">onebox●</div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtns = document.querySelectorAll('.hamburger-btn');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    hamburgerBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    });
});
</script>
