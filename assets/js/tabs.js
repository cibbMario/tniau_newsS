/**
 * Google Chrome-Style Workspace Tabs Manager
 * Handles dynamic tab creation, activation, closing, smooth animations, and state persistence.
 */

(function () {
    const STORAGE_KEY = 'tniau_workspace_tabs';

    // Default predefined tabs catalog
    const TAB_CATALOG = {
        'dashboard': { id: 'dashboard', title: 'Semua Sumber', icon: '🏠', view: 'semua' },
        'semua': { id: 'dashboard', title: 'Semua Sumber', icon: '🏠', view: 'semua' },
        'wilayah': { id: 'wilayah', title: 'Berita Wilayah', icon: '📍', view: 'wilayah' },
        'online': { id: 'online', title: 'Media Online', icon: '🌐', view: 'online' },
        'sosial': { id: 'sosial', title: 'Media Sosial', icon: '📱', view: 'sosial' },
        'statistics': { id: 'statistics', title: 'Statistik', icon: '📊', view: 'statistics' },
        'report': { id: 'report', title: 'Report Monitoring', icon: '📄', view: 'report' },
        'gallery': { id: 'gallery', title: 'Galeri Media', icon: '🖼️', view: 'gallery' },
        'list': { id: 'list', title: 'Daftar Berita', icon: '📰', view: 'list' }
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
            { id: 'dashboard', title: 'Semua Sumber', icon: '🏠', url: 'dashboard.php?view=semua' }
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
                icon: cat.icon,
                url: window.location.pathname + window.location.search
            };
        }

        if (currentPath.includes('statistics.php')) {
            return { id: 'statistics', title: 'Statistik', icon: '📊', url: 'dashboard.php?view=statistics' };
        }
        if (currentPath.includes('report.php')) {
            return { id: 'report', title: 'Report Monitoring', icon: '📄', url: 'dashboard.php?view=report' };
        }
        if (currentPath.includes('gallery.php')) {
            return { id: 'gallery', title: 'Galeri Media', icon: '🖼️', url: 'dashboard.php?view=gallery' };
        }
        if (currentPath.includes('news_list.php')) {
            return { id: 'list', title: 'Daftar Berita', icon: '📰', url: 'news_list.php' };
        }

        return { id: 'dashboard', title: 'Semua Sumber', icon: '🏠', url: 'dashboard.php?view=semua' };
    }

    function currentPathIsDashboard() {
        return window.location.pathname.includes('dashboard.php');
    }

    function renderWorkspaceTabs() {
        const container = document.querySelector('.workspace-tabs-row');
        if (!container) return;

        const currentTab = detectCurrentTabInfo();
        let tabs = getStoredTabs();

        // Check if current tab is in the list, if not add it
        const exists = tabs.find(t => t.id === currentTab.id);
        if (!exists) {
            tabs.push(currentTab);
        } else {
            exists.url = currentTab.url;
        }
        saveTabs(tabs);

        // Clear existing tabs content
        container.innerHTML = '';

        // Render each tab Chrome style
        tabs.forEach(tab => {
            const tabEl = document.createElement('div');
            const isActive = (tab.id === currentTab.id);
            tabEl.className = 'workspace-tab' + (isActive ? ' active' : '');
            tabEl.dataset.tabId = tab.id;

            tabEl.innerHTML = `
                <span class="tab-icon">${tab.icon || '📄'}</span>
                <span class="tab-title">${tab.title}</span>
                <button class="close-tab" title="Tutup tab" aria-label="Tutup tab">&times;</button>
            `;

            // Click tab -> Switch tab / view smoothly
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

            // Close button click
            const closeBtn = tabEl.querySelector('.close-tab');
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeTab(tab.id, tabEl);
            });

            container.appendChild(tabEl);
        });

        // Add Chrome "+" New Tab Button
        const addBtn = document.createElement('button');
        addBtn.className = 'add-tab-btn';
        addBtn.title = 'Buka Tab Baru';
        addBtn.innerHTML = '+';
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

        // Smooth shrink animation
        tabEl.style.transform = 'scale(0.85)';
        tabEl.style.opacity = '0';
        tabEl.style.maxWidth = '0px';
        tabEl.style.padding = '0px';
        tabEl.style.margin = '0px';

        setTimeout(() => {
            tabs.splice(tabIndex, 1);
            
            // If all tabs removed, reset to default Dashboard
            if (tabs.length === 0) {
                tabs = [{ id: 'dashboard', title: 'Semua Sumber', icon: '🏠', url: 'dashboard.php?view=semua' }];
            }

            saveTabs(tabs);

            if (isCurrentActive) {
                // Switch to adjacent tab
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
            icon: tabInfo.icon,
            url: targetUrl
        });

        saveTabs(tabs);

        if (window.switchDashboardTab && currentPathIsDashboard()) {
            window.switchDashboardTab(tabInfo.id);
        } else {
            window.location.href = targetUrl;
        }
    }

    // Expose Tab Manager globally
    window.WorkspaceTabs = {
        render: renderWorkspaceTabs,
        getTabs: getStoredTabs,
        saveTabs: saveTabs
    };

    document.addEventListener('DOMContentLoaded', function () {
        renderWorkspaceTabs();
    });
})();
