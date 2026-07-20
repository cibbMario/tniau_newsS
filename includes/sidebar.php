<?php
$user = currentUser();
$unread = countUnreadNotifications($user['id']);
$current = $current ?? '';
$roleName = ['A'=>'Reporter','B'=>'Editor','C'=>'Petinggi / Approver'][$user['role']] ?? '';
?>

<!-- Global Loader Screen -->
<div id="global-loader">
    <div class="loader-spinner"></div>
</div>

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
        <div class="onebox">DISPEN - TNIAU</div>
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

window.addEventListener('load', function() {
    const loader = document.getElementById('global-loader');
    if(loader) {
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.visibility = 'hidden';
            loader.style.display = 'none';
        }, 500);
    }
});
</script>
