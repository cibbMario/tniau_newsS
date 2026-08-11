<?php
// Determine active page
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="public-navbar" id="publicNavbar">
    <div class="navbar-container">
        <a href="<?= BASE_URL ?>/" class="navbar-brand">
            <img src="<?= BASE_URL ?>/assets/img/logo-new.png" alt="Logo TNI AU" class="navbar-logo" onerror="this.src='<?= BASE_URL ?>/assets/img/logo-tniau.png'">
            <div class="navbar-brand-text">
                <span class="brand-title">TNI Angkatan Udara</span>
                <span class="brand-subtitle">Portal Publikasi &amp; Berita Resmi</span>
            </div>
        </a>
        
        <!-- Hamburger Menu Button -->
        <button class="navbar-toggle" aria-label="Toggle Navigation" id="navbarToggle">
            <span class="toggle-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <nav class="navbar-menu" id="navbarMenu">
            <a href="<?= BASE_URL ?>/" class="menu-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <span>Beranda</span>
            </a>
            <a href="https://tni-au.mil.id/" target="_blank" rel="noopener noreferrer" class="menu-link external-link">
                <span>Web Utama TNI AU</span>
                <svg class="external-icon" viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
            </a>
            <a href="<?= BASE_URL ?>/login.php" class="btn-login-cta">
                <span>Masuk Sistem</span>
            </a>
        </nav>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.getElementById('publicNavbar');
    const toggle = document.getElementById('navbarToggle');
    const menu = document.getElementById('navbarMenu');
    
    // 1. Scroll styling effect
    const handleScroll = () => {
        if (window.scrollY > 20) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    };
    
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll(); // Initial check in case of page refresh
    
    // 2. Mobile Menu Toggle
    if (toggle && menu) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggle.classList.toggle('active');
            menu.classList.toggle('active');
            document.body.classList.toggle('menu-open'); // Prevent background scroll when mobile menu is open
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (menu.classList.contains('active') && !menu.contains(e.target) && !toggle.contains(e.target)) {
                toggle.classList.remove('active');
                menu.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
        
        // Close menu when resizing screen to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && menu.classList.contains('active')) {
                toggle.classList.remove('active');
                menu.classList.remove('active');
                document.body.classList.remove('menu-open');
            }
        });
    }
});
</script>
