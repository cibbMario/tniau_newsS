/**
 * Real-time notification & sound alert handler for TNI AU News
 */
(function () {
    const navEl = document.querySelector('.top-navbar');
    const baseUrl = window.BASE_URL || (navEl ? navEl.getAttribute('data-base-url') : '') || '';
    const bell = document.getElementById('notificationBell');
    const dropdown = document.getElementById('notificationDropdown');
    const badge = document.getElementById('notificationBadge');
    const list = document.getElementById('notificationList');
    const inlineHint = document.getElementById('notificationInlineHint');
    const storageKey = 'tniau_notification_broadcast';
    const seenKey = 'tniau_last_seen_notification';
    const broadcastChannel = ('BroadcastChannel' in window) ? new BroadcastChannel('tniau_notification_channel') : null;
    let lastUnreadCount = 0;
    let lastItemId = 0;

    function formatShortDate(value) {
        if (!value) return '';
        const date = new Date(value);
        return date.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function renderNotificationList(items) {
        if (!list) return;
        if (!items.length) {
            list.innerHTML = '<div class="notification-empty">Belum ada notifikasi baru.</div>';
            return;
        }

        list.innerHTML = items.map(item => `
            <a class="notification-item ${item.is_read ? '' : 'unread'}" href="${baseUrl}/news_view.php?id=${item.news_id}&mark_read=${item.id}">
                <div class="notification-item-copy">
                    <strong>${item.message}</strong>
                    <span>${formatShortDate(item.created_at)}</span>
                </div>
            </a>
        `).join('');
    }

    function updateBadge(count) {
        if (!badge) return;
        const unread = Number(count || 0);
        badge.textContent = unread > 99 ? '99+' : unread;
        badge.style.display = unread > 0 ? 'inline-flex' : 'none';
    }

    function playAudioAlert() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } catch (e) {
            // Web Audio not permitted until user interaction
        }
    }

    function showInlineNotice(item) {
        if (!inlineHint) return;
        inlineHint.textContent = item.message;
        inlineHint.hidden = false;
        playAudioAlert();

        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification('Notifikasi Baru - TNI AU News', {
                    body: item.message,
                    icon: baseUrl + '/assets/img/logo-tniau-transparent.png',
                    tag: 'tniau-notif-' + (item.id || Date.now())
                });
            } catch (error) {
                console.warn('Notification API failed:', error);
            }
        }

        clearTimeout(showInlineNotice.timer);
        showInlineNotice.timer = setTimeout(() => {
            inlineHint.hidden = true;
        }, 3200);
    }

    function publishNotificationPayload(payload) {
        if (broadcastChannel) {
            broadcastChannel.postMessage(payload);
        }

        if (typeof localStorage !== 'undefined') {
            localStorage.setItem(storageKey, JSON.stringify(payload));
        }
    }

    function handleIncoming(data) {
        const unreadCount = Number(data.unread_count || 0);
        const items = Array.isArray(data.items) ? data.items : [];
        updateBadge(unreadCount);
        renderNotificationList(items);

        const newestId = items.reduce((maxId, item) => Math.max(maxId, Number(item.id || 0)), 0);
        const seenId = Number(localStorage.getItem(seenKey) || 0);
        const shouldNotify = newestId > seenId;

        if (shouldNotify && items.length) {
            const nextItems = items.filter(item => Number(item.id || 0) > seenId);
            const firstNewItem = nextItems[0];

            if (firstNewItem) {
                showInlineNotice(firstNewItem);
                publishNotificationPayload({
                    id: firstNewItem.id,
                    message: firstNewItem.message,
                    ts: Date.now()
                });
                localStorage.setItem(seenKey, String(firstNewItem.id));
            }
        }

        lastUnreadCount = unreadCount;
        lastItemId = newestId;
    }

    async function fetchNotifications() {
        try {
            const response = await fetch(baseUrl + '/notifications_feed.php', { cache: 'no-store' });
            const data = await response.json();
            handleIncoming(data);
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    if (bell && dropdown) {
        bell.addEventListener('click', function (event) {
            event.preventDefault();
            const willShow = dropdown.hidden;
            dropdown.hidden = !willShow;
            bell.classList.toggle('active', willShow);
        });

        document.addEventListener('click', function (event) {
            if (!dropdown.hidden && !dropdown.contains(event.target) && !bell.contains(event.target)) {
                dropdown.hidden = true;
                bell.classList.remove('active');
            }
        });
    }

    if (typeof window !== 'undefined') {
        if (broadcastChannel) {
            broadcastChannel.addEventListener('message', function (event) {
                const payload = event.data;
                if (payload && payload.message) {
                    showInlineNotice(payload);
                    localStorage.setItem(seenKey, String(payload.id || 0));
                }
            });
        }

        window.addEventListener('storage', function (event) {
            if (event.key === storageKey && event.newValue) {
                const payload = JSON.parse(event.newValue);
                if (payload && payload.message) {
                    showInlineNotice(payload);
                    localStorage.setItem(seenKey, String(payload.id || 0));
                }
            }
        });
    }

    function init() {
        fetchNotifications();
        setInterval(fetchNotifications, 15000);

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().catch(function () {});
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                fetchNotifications();
            }
        });

        window.addEventListener('focus', fetchNotifications);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
