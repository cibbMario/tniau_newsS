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

    // Default predefined tabs catalog
    const TAB_CATALOG = {
        'dashboard': { id: 'dashboard', title: 'Semua Sumber', iconType: 'dashboard', view: 'semua' },
        'semua': { id: 'dashboard', title: 'Semua Sumber', iconType: 'dashboard', view: 'semua' },
        'harian': { id: 'dashboard-harian', title: 'Dashboard Harian', iconType: 'dashboard', view: 'harian' },
        'negatif': { id: 'dashboard-negatif', title: 'Berita Negatif', iconType: 'dashboard', view: 'negatif' },
        'inspiratif': { id: 'dashboard-inspiratif', title: 'Inspiratif', iconType: 'dashboard', view: 'inspiratif' },
        'konten': { id: 'dashboard-konten', title: 'Konten', iconType: 'dashboard', view: 'konten' },
        'sentimen': { id: 'dashboard-sentimen', title: 'Sentimen', iconType: 'dashboard', view: 'sentimen' },
        'wilayah': { id: 'wilayah', title: 'Berita Wilayah', iconType: 'wilayah', view: 'wilayah' },
        'online': { id: 'online', title: 'Media Online', iconType: 'online', view: 'online' },
        'sosial': { id: 'sosial', title: 'Media Sosial', iconType: 'sosial', view: 'sosial' },
        'statistics': { id: 'statistics', title: 'Statistik', iconType: 'statistics', view: 'statistics' },
        'report': { id: 'report', title: 'Report Monitoring', iconType: 'report', view: 'report' },
        'gallery': { id: 'gallery', title: 'Galeri Media', iconType: 'gallery', view: 'gallery' },
        'list': { id: 'list', title: 'Daftar Berita', iconType: 'list', view: 'list' }
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
            { id: 'dashboard', title: 'Semua Sumber', iconType: 'dashboard', url: 'dashboard.php?view=semua' }
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
        const view = params.get('view') || params.get('media');

        if (view && TAB_CATALOG[view.toLowerCase()]) {
            const cat = TAB_CATALOG[view.toLowerCase()];
            return {
                id: cat.id,
                title: cat.title,
                iconType: cat.iconType,
                url: window.location.pathname + window.location.search
            };
        }

        if (currentPath.includes('statistics.php')) {
            return { id: 'statistics', title: 'Statistik', iconType: 'statistics', url: 'dashboard.php?view=statistics' };
        }
        if (currentPath.includes('report.php')) {
            return { id: 'report', title: 'Report Monitoring', iconType: 'report', url: 'dashboard.php?view=report' };
        }
        if (currentPath.includes('gallery.php')) {
            return { id: 'gallery', title: 'Galeri Media', iconType: 'gallery', url: 'dashboard.php?view=gallery' };
        }
        if (currentPath.includes('news_list.php')) {
            return { id: 'list', title: 'Daftar Berita', iconType: 'list', url: 'news_list.php' };
        }

        return { id: 'dashboard', title: 'Semua Sumber', iconType: 'dashboard', url: 'dashboard.php?view=semua' };
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
                    if (window.switchDashboardTab && currentPathIsDashboard()) {
                        window.switchDashboardTab(tab.id);
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

        tabEl.style.transform = 'scale(0.85)';
        tabEl.style.opacity = '0';
        tabEl.style.maxWidth = '0px';
        tabEl.style.padding = '0px';
        tabEl.style.margin = '0px';

        setTimeout(() => {
            tabs.splice(tabIndex, 1);
            if (tabs.length === 0) {
                tabs = [{ id: 'dashboard', title: 'Semua Sumber', iconType: 'dashboard', url: 'dashboard.php?view=semua' }];
            }

            saveTabs(tabs);

            if (isCurrentActive) {
                const nextTab = tabs[Math.max(0, tabIndex - 1)];
                if (window.switchDashboardTab && currentPathIsDashboard()) {
                    window.switchDashboardTab(nextTab.id);
                } else {
                    window.location.href = nextTab.url;
                }
            } else {
                renderWorkspaceTabs();
            }
        }, 200);
    }

    function openNewTabQuickMenu() {
        let tabs = getStoredTabs();
        const openIds = tabs.map(t => t.id);
        const catalogKeys = Object.keys(TAB_CATALOG);
        const remaining = catalogKeys.filter(k => !openIds.includes(k));

        if (remaining.length === 0) {
            window.location.href = 'dashboard.php?view=semua';
            return;
        }

        const nextId = remaining[0];
        const tabInfo = TAB_CATALOG[nextId];
        const targetUrl = 'dashboard.php?view=' + tabInfo.view;

        tabs.push({
            id: tabInfo.id,
            title: tabInfo.title,
            iconType: tabInfo.iconType,
            url: targetUrl
        });

        saveTabs(tabs);

        if (window.switchDashboardTab && currentPathIsDashboard()) {
            window.switchDashboardTab(tabInfo.id);
        } else {
            window.location.href = targetUrl;
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
