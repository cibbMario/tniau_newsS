/**
 * Google Chrome-Style Workspace Tabs Manager
 * Handles dynamic tab creation, activation, closing, smooth animations, and state persistence.
 * Uses SVG vector icons (No Emojis).
 */

(function () {
    const STORAGE_KEY = 'tniau_workspace_tabs';

    const SVG_ICONS = {
        dashboard: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>`,
        wilayah: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`,
        online: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>`,
        sosial: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>`,
        statistics: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>`,
        report: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>`,
        gallery: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`,
        list: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2m-4-3H9M7 16h6M7 8h6m-6 4h6"></path></svg>`
    };

    function normalizeDashboardView(viewName) {
        const normalized = String(viewName || '').trim().toLowerCase();
        if (!normalized) return 'harian';
        if (normalized === 'dashboard' || normalized === 'semua') return 'harian';
        return normalized;
    }

    // Default predefined tabs catalog
    const TAB_CATALOG = {
        'dashboard': { id: 'dashboard-harian', title: 'Semua Sumber', iconType: 'dashboard', url: 'dashboard.php?view=harian' },
        'semua': { id: 'dashboard-harian', title: 'Semua Sumber', iconType: 'dashboard', url: 'dashboard.php?view=harian' },
        'harian': { id: 'dashboard-harian', title: 'Dashboard Harian', iconType: 'dashboard', url: 'dashboard.php?view=harian' },
        'negatif': { id: 'dashboard-negatif', title: 'Berita Negatif', iconType: 'dashboard', url: 'dashboard.php?view=negatif' },
        'inspiratif': { id: 'dashboard-inspiratif', title: 'Inspiratif', iconType: 'dashboard', url: 'dashboard.php?view=inspiratif' },
        'konten': { id: 'dashboard-konten', title: 'Konten', iconType: 'dashboard', url: 'dashboard.php?view=konten' },
        'sentimen': { id: 'dashboard-sentimen', title: 'Sentimen', iconType: 'dashboard', url: 'dashboard.php?view=sentimen' },
        'wilayah': { id: 'wilayah', title: 'Berita Wilayah', iconType: 'wilayah', url: 'dashboard.php?view=wilayah' },
        'online': { id: 'online', title: 'Media Online', iconType: 'online', url: 'news_list.php?media=Media Online' },
        'sosial': { id: 'sosial', title: 'Media Sosial', iconType: 'sosial', url: 'news_list.php?media=Media Sosial' },
        'statistics': { id: 'statistics', title: 'Statistik', iconType: 'statistics', url: 'statistics.php' },
        'report': { id: 'report', title: 'Report Monitoring', iconType: 'report', url: 'report.php' },
        'gallery': { id: 'gallery', title: 'Galeri Media', iconType: 'gallery', url: 'gallery.php' },
        'list': { id: 'list', title: 'Daftar Berita', iconType: 'list', url: 'news_list.php' }
    };

    function getStoredTabs() {
        try {
            const data = sessionStorage.getItem(STORAGE_KEY);
            if (data) {
                const parsed = JSON.parse(data);
                if (Array.isArray(parsed) && parsed.length > 0) return parsed;
            }
        } catch (e) {
            console.error('Failed to parse workspace tabs:', e);
        }
        return [
            { id: 'dashboard-harian', title: 'Semua Sumber', iconType: 'dashboard', url: 'dashboard.php?view=harian' }
        ];
    }

    function saveTabs(tabs) {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(tabs));
        } catch (e) {
            console.error('Failed to save workspace tabs:', e);
        }
    }

    function detectCurrentTabInfo() {
        const params = new URLSearchParams(window.location.search);
        const currentPath = window.location.pathname;

        if (currentPath.includes('dashboard.php')) {
            const view = params.get('view') || 'harian';
            const cat = TAB_CATALOG[view] || TAB_CATALOG['dashboard'];
            return {
                id: cat.id,
                title: cat.title,
                iconType: cat.iconType,
                url: 'dashboard.php?view=' + (view === 'semua' ? 'harian' : view)
            };
        }

        if (currentPath.includes('statistics.php')) {
            const view = params.get('view') || 'berita';
            let title = 'Statistik Berita';
            if (view === 'tren') title = 'Tren';
            if (view === 'aktor') title = 'Top Aktor';
            return { id: 'statistics', title: title, iconType: 'statistics', url: 'statistics.php?view=' + view };
        }
        if (currentPath.includes('report.php')) {
            return { id: 'report', title: 'Report Monitoring', iconType: 'report', url: 'report.php' };
        }
        if (currentPath.includes('gallery.php')) {
            return { id: 'gallery', title: 'Galeri Media', iconType: 'gallery', url: 'gallery.php' };
        }
        if (currentPath.includes('news_list.php')) {
            const status = params.get('status');
            const media = params.get('media');
            if (status === 'draft') {
                return { id: 'draft', title: 'Draft Berita', iconType: 'list', url: 'news_list.php?status=draft' };
            }
            if (media === 'Media Online') {
                return { id: 'online', title: 'Media Online', iconType: 'online', url: 'news_list.php?media=Media Online' };
            }
            if (media === 'Media Sosial') {
                return { id: 'sosial', title: 'Media Sosial', iconType: 'sosial', url: 'news_list.php?media=Media Sosial' };
            }
            return { id: 'list', title: 'Daftar Berita', iconType: 'list', url: 'news_list.php' };
        }
        if (currentPath.includes('news_edit.php')) {
            return { id: 'edit-' + (params.get('id') || '0'), title: 'Edit Berita', iconType: 'list', url: window.location.search ? 'news_edit.php' + window.location.search : 'news_edit.php' };
        }
        if (currentPath.includes('news_create.php')) {
            return { id: 'create', title: 'Buat Berita', iconType: 'list', url: 'news_create.php' };
        }

        return { id: 'dashboard-harian', title: 'Semua Sumber', iconType: 'dashboard', url: 'dashboard.php?view=harian' };
    }

    function currentPathIsDashboard() {
        return window.location.pathname.includes('dashboard.php');
    }

    function renderWorkspaceTabs() {
        const container = document.querySelector('.workspace-tabs-row');
        if (!container) return;

        const currentTab = detectCurrentTabInfo();
        let tabs = getStoredTabs();

        const exists = tabs.find(t => t.id === currentTab.id);
        if (!exists) {
            tabs.push(currentTab);
        } else {
            exists.url = currentTab.url;
        }
        saveTabs(tabs);

        container.innerHTML = '';

        tabs.forEach(tab => {
            const tabEl = document.createElement('div');
            const isActive = (tab.id === currentTab.id);
            tabEl.className = 'workspace-tab' + (isActive ? ' active' : '');
            tabEl.dataset.tabId = tab.id;

            const iconSvg = SVG_ICONS[tab.iconType] || SVG_ICONS.dashboard;

            tabEl.innerHTML = `
                <span class="tab-icon">${iconSvg}</span>
                <span class="tab-title">${tab.title}</span>
                <button class="close-tab" title="Tutup tab" aria-label="Tutup tab">&times;</button>
            `;

            tabEl.addEventListener('click', function (e) {
                if (e.target.classList.contains('close-tab')) return;
                if (!isActive) {
                    const dashboardIds = ['dashboard-harian', 'dashboard-negatif', 'dashboard-inspiratif', 'dashboard-konten', 'dashboard-sentimen', 'wilayah'];
                    if (dashboardIds.includes(tab.id) && currentPathIsDashboard() && window.switchDashboardTab) {
                        window.switchDashboardTab(tab.id.replace('dashboard-', ''));
                    } else {
                        window.location.href = tab.url;
                    }
                }
            });

            const closeBtn = tabEl.querySelector('.close-tab');
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeTab(tab.id, tabEl);
            });

            container.appendChild(tabEl);
        });

        // Add "+" New Tab Button with SVG Icon
        const addBtn = document.createElement('button');
        addBtn.className = 'add-tab-btn';
        addBtn.title = 'Buka Tab Baru';
        addBtn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>`;
        addBtn.addEventListener('click', function () {
            openNewTabQuickMenu();
        });
        container.appendChild(addBtn);
    }

    function closeTab(tabId, tabEl) {
        let tabs = getStoredTabs();
        const tabIndex = tabs.findIndex(t => t.id === tabId);
        if (tabIndex === -1) return;

        const isCurrentActive = tabEl.classList.contains('active');
        tabEl.classList.add('is-removing');

        setTimeout(() => {
            tabs.splice(tabIndex, 1);
            if (tabs.length === 0) {
                tabs = [{ id: 'dashboard-harian', title: 'Semua Sumber', iconType: 'dashboard', url: 'dashboard.php?view=harian' }];
            }

            saveTabs(tabs);

            if (isCurrentActive) {
                const nextTab = tabs[Math.max(0, tabIndex - 1)];
                const dashboardIds = ['dashboard-harian', 'dashboard-negatif', 'dashboard-inspiratif', 'dashboard-konten', 'dashboard-sentimen', 'wilayah'];
                if (dashboardIds.includes(nextTab.id) && currentPathIsDashboard() && window.switchDashboardTab) {
                    window.switchDashboardTab(nextTab.id.replace('dashboard-', ''));
                } else {
                    window.location.href = nextTab.url;
                }
            } else {
                renderWorkspaceTabs();
            }
        }, 240);
    }

    function openNewTabQuickMenu() {
        let tabs = getStoredTabs();
        const openIds = tabs.map(t => t.id);
        const catalogKeys = Object.keys(TAB_CATALOG);
        const remaining = catalogKeys.filter(k => {
            const cat = TAB_CATALOG[k];
            return !openIds.includes(cat.id);
        });

        if (remaining.length === 0) {
            window.location.href = 'dashboard.php?view=harian';
            return;
        }

        const nextKey = remaining[0];
        const tabInfo = TAB_CATALOG[nextKey];

        tabs.push({
            id: tabInfo.id,
            title: tabInfo.title,
            iconType: tabInfo.iconType,
            url: tabInfo.url
        });

        saveTabs(tabs);

        const dashboardIds = ['dashboard-harian', 'dashboard-negatif', 'dashboard-inspiratif', 'dashboard-konten', 'dashboard-sentimen', 'wilayah'];
        if (dashboardIds.includes(tabInfo.id) && currentPathIsDashboard() && window.switchDashboardTab) {
            window.switchDashboardTab(tabInfo.id.replace('dashboard-', ''));
        } else {
            window.location.href = tabInfo.url;
        }
    }

    window.WorkspaceTabs = {
        render: renderWorkspaceTabs,
        getTabs: getStoredTabs,
        saveTabs: saveTabs
    };

    document.addEventListener('DOMContentLoaded', function () {
        renderWorkspaceTabs();
    });
})();
